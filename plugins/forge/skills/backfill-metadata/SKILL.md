---
name: backfill-metadata
description: Reconstruit le `metadata.json` manquant des stories antérieures — déduit le titre du H1, les dates `created`/`updated` de l'historique git, la timeline `changelog` de l'apparition de chaque artifact, la livraison (`delivery.commit`/`release`) des commits et tags, propose des `tags` à valider. Déclenche sur « générer les métadonnées des anciennes stories », « backfill metadata.json », « reconstruire les métadonnées ».
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug|chemin de story] [--force] [--all]"
allowed-tools:
  - Read
  - Write
  - Glob
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(git log:*)
  - Bash(git tag --contains:*)
  - Bash(git rev-parse:*)
  - Bash(git status:*)
  - Bash(git for-each-ref:*)
  - Bash(grep:*)
---

# /backfill-metadata — Reconstruction rétro du `metadata.json`

La notion de `metadata.json` est arrivée **après** que plusieurs stories aient été écrites (cf.
`${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`). Les skills de cadrage produisent ce
fichier pour les stories **à venir** ; ce skill le **reconstruit a posteriori** pour les stories
**antérieures** qui n'en ont pas, en s'appuyant sur ce qui est déjà traçable : le contenu des `.md`
et l'historique git du dépôt.

Objectif : que le Forge Board affiche des cartes riches (vrai titre, âge, dernière activité, tags,
badge de livraison) même pour les stories créées avant l'introduction du contrat, **sans jamais
inventer une donnée**. Toute valeur écrite doit être vraie (déduite d'une source) ou validée par
l'utilisateur. Mieux vaut un champ absent qu'un champ faux.

Ce skill **écrit le même fichier, au même schéma v1, que les skills de cadrage** : la référence
partagée `story-metadata.md` reste la seule source de vérité du format. Ici on ne fait que
**remplir les blancs du passé**.

## Périmètre

- **Cible** : les dossiers `docs/story/NNN-<f|r|t>-<slug>/` qui **n'ont pas** de `metadata.json`,
  ou dont le `metadata.json` est **malformé** (JSON invalide, `version` absente).
- **Ne touche jamais** un `metadata.json` valide existant — sauf `--force` (re-dérivation
  explicite d'une story déjà pourvue, ex. pour corriger un backfill précédent).
- **Ne modifie aucun `.md`** : ce skill lit `pitch.md`/`plan.md`/`review.md`/`report.md`, il ne les
  réécrit pas. Aucune table de changelog n'est retouchée (elles sont abandonnées).
- **Source des valeurs** : contenu des `.md` (titre) + historique git (dates, timeline, livraison).
  Aucun appel réseau. Si le dépôt n'a **pas** d'historique git pour une story (dossier jamais
  commité, ou pas un repo git), voir §Dégradation.

## Ce qui est reconstruit et d'où

| Champ         | Source de reconstruction                                                                                   |
|---------------|------------------------------------------------------------------------------------------------------------|
| `version`     | `1` (constante du schéma).                                                                                  |
| `title`       | Le **H1** (`# …`) du document principal : `pitch.md` pour un track `f`, `plan.md` pour `r`/`t`. Nettoyé des marqueurs de guide. Fallback : slug humanisé (voir §Titre). |
| `created`     | Date du **premier** commit touchant le dossier de la story (`git log --reverse … | head -1`).               |
| `updated`     | Date du **dernier** commit touchant le dossier de la story (`git log -1`).                                  |
| `tags`        | **Proposés** par déduction du titre + contenu + track, en kebab-case, puis **validés** par l'utilisateur. Jamais écrits sans validation. |
| `changelog`   | Timeline reconstruite depuis la date d'apparition des artifacts dans git, **jalons fusionnés par date** (les artifacts d'un même commit → une entrée `Création` de cadrage + une entrée `Livraison`, jamais une ligne par fichier). Détail des dates distinctes conservé. |
| `delivery`    | `commit` = SHA court du commit de livraison **si identifiable avec confiance** ; `release` = plus ancien tag contenant ce commit. Absent si la story n'est pas livrée ou si la livraison n'est pas identifiable de façon fiable. |

## Règles non négociables

1. **Aucune date inventée.** Les dates viennent **toujours** de git. Si git ne peut pas fournir une
   date pour une story (non commitée), ne devine pas : signale-le et propose de demander la date à
   l'utilisateur, ou de sauter la story.
2. **`created` ≠ `updated` reflètent le réel.** `created` = premier commit du dossier, `updated` =
   dernier. S'ils sont égaux (story écrite en un seul commit), c'est correct, on les laisse égaux.
3. **`tags` toujours validés.** Le skill propose, l'utilisateur tranche. Jamais d'étiquette non
   confirmée (même garde-fou anti-dérive que les skills de cadrage).
4. **`delivery` seulement si confiant.** Si tu n'es pas sûr du commit de livraison, laisse
   `delivery` **absent** plutôt que d'y mettre un SHA douteux. Un `commit` sans `release` est un
   état valide (livré sans numéro de version) — ne force pas un tag.
5. **Chaque entrée de changelog reconstruite est datée d'une vraie date git** et sa `description`
   indique la reconstruction (ex. « pitch.md ajouté (reconstruit depuis l'historique git) »).
   N'invente pas d'entrées `Sync`/`ADR`/`Estimation` que rien ne prouve.
6. **Ne réécris jamais un `metadata.json` valide** sans `--force`. Sur `--force`, **préserve**
   `created` d'origine s'il existe (c'est une date écrite une seule fois).
7. **Validation avant écriture.** Présente la proposition complète de chaque story et attends le go
   avant tout `Write`. Jamais de batch silencieux.

## Déroulement

### Phase 1 — Détection

Cadre l'exécution selon `$ARGUMENTS` :

- **Un slug ou un chemin** (`006-f-metadonnees-story`, `docs/story/006-…/`) → une seule story.
- **`--all`** ou aucun argument → toutes les stories `docs/story/NNN-<f|r|t>-<slug>/`.
- **`--force`** → inclut aussi les stories qui ont déjà un `metadata.json` valide (re-dérivation).

Liste les candidats et leur état :

```bash
ls -1d docs/story/[0-9][0-9][0-9]-[frt]-*/ 2>/dev/null
```

Pour chaque dossier, teste la présence et la validité de `metadata.json` (Read + parse JSON) :

- **Absent** → à reconstruire.
- **Présent et valide** (`version` entier + JSON parsable) → **ignoré** (sauf `--force`).
- **Présent mais malformé** → à reconstruire (signalé comme réparation).

Vérifie que le dépôt est un repo git avec de l'historique :

```bash
git rev-parse --is-inside-work-tree 2>/dev/null
```

Si ce n'est **pas** un repo git : préviens que les dates et la livraison ne pourront pas être
reconstruites (seuls titre + tags le seront), et demande si l'utilisateur veut continuer en mode
dégradé (dates à saisir à la main) ou arrêter.

Récapitule :

> N stories sans `metadata.json` (à reconstruire), M avec un fichier malformé (à réparer),
> K déjà pourvues (ignorées — relance avec `--force` pour les re-dériver).

Si rien à faire : « Toutes les stories ont déjà un `metadata.json` valide. »

### Phase 2 — Reconstruction (par story)

Pour **chaque** story retenue, exécute les requêtes ci-dessous (adapte `DIR` au dossier).

**Titre** — lis le document principal et extrais le premier `# H1` :

- Track `f` : `pitch.md` (fallback `plan.md` si pas de pitch).
- Track `r`/`t` : `plan.md`.
- Retire un éventuel marqueur de guide (`> _Skill : …_`, commentaires HTML). Si aucun H1
  exploitable : fallback = slug humanisé (`006-f-metadonnees-story` → « Metadonnees story »),
  **signalé** comme fallback pour que l'utilisateur corrige.

**Dates** :

```bash
# created = premier commit touchant le dossier
git log --reverse --format='%ad' --date=short -- "$DIR" | head -1
# updated = dernier commit touchant le dossier
git log -1 --format='%ad' --date=short -- "$DIR"
```

**Timeline (changelog)** — date de **première apparition** de chaque artifact :

```bash
for f in pitch.md plan.md review.md report.md; do
  d=$(git log --diff-filter=A --reverse --format='%ad' --date=short -- "$DIR$f" 2>/dev/null | head -1)
  [ -n "$d" ] && echo "$d  $f"
done
```

Mappe chaque artifact vers un `type`, dans l'ordre chronologique :

| Artifact ajouté | `type`                                          |
|-----------------|-------------------------------------------------|
| `pitch.md`      | `Création` (track `f`)                          |
| `plan.md`       | `Planification` (`f`) / `Création` (`r`, `t` sans pitch) |
| `review.md`     | `Review`                                        |
| `report.md`     | `Report`                                        |

**Fusion des entrées de même date (obligatoire).** git ne connaît qu'**une date par commit** :
une story importée ou commitée en bloc fait apparaître plusieurs artifacts à la même date. Ne
produis **pas** une entrée par fichier — ce serait 4-5 lignes identiques en date, bruyantes et
faussement précises. Regroupe par date et collapse en **jalons** :

- Date ne portant que du **cadrage** (`pitch` et/ou `plan`) → **une seule** entrée `Création` (ou
  `Planification` si `plan` seul sur un track `r`/`t`), description listant ce qui a été posé
  (ex. « pitch + plan posés »).
- Date portant `review`/`report` et/ou un **commit de livraison** → **une seule** entrée
  `Livraison` (le jalon le plus avancé), description listant les livrables (ex. « implémentation
  livrée, revue, reportée » + release si connue). N'émets pas d'entrées `Review`/`Report`
  séparées quand elles tombent le même jour que la livraison.
- **Dates distinctes** (story écrite au fil du temps) → garde **une entrée par date/jalon**. La
  fusion ne s'applique **qu'à l'intérieur d'une même date**, jamais entre dates différentes.

Résultat attendu pour une story commitée en bloc : deux entrées maximum (`Création` puis
`Livraison`), même date, au lieu d'une par artifact. Chaque `description` mentionne la
reconstruction. Le tableau ci-dessus ne sert qu'à typer les jalons **quand les dates diffèrent**.

**Livraison (`delivery`)** — n'y va que si la story montre des signes de livraison (présence de
`report.md`, ou une entrée de release évidente). Cherche un commit de livraison **fiable** :

```bash
# Commits mentionnant le slug ou son thème (ajuste les mots-clés au slug)
git log --oneline --all --grep="<mots-clés du slug>" | head
```

- Si un commit ressort clairement (message qui décrit la feature de la story) → `delivery.commit`
  = son SHA court, et cherche la release qui l'embarque :

  ```bash
  git tag --contains <sha> --sort=v:refname | head -1     # plus ancien tag contenant le commit
  ```

  Le premier tag renvoyé = la release qui a livré ce commit → `delivery.release`.
- Si **aucun** commit ne ressort avec confiance → **laisse `delivery` absent** et signale-le. Ne
  colle pas un SHA au hasard. L'utilisateur pourra le renseigner à la main ou via `/commit` plus
  tard.

Ajoute alors une entrée de changelog `Livraison` (date du commit) si `delivery.commit` a été trouvé,
et `Release` (date du tag) si `delivery.release` l'a été.

### Phase 3 — Proposition et validation

Présente, **par story**, la proposition reconstruite avant écriture :

```
docs/story/006-f-metadonnees-story/metadata.json  (nouveau)

  title    : « Enrichir chaque story de métadonnées lisibles par le Board »   (H1 de pitch.md)
  created  : 2026-07-05   (premier commit du dossier — 5f7519e)
  updated  : 2026-07-05   (dernier commit du dossier — 04c60db)
  tags     : [metadata, board]   ← PROPOSÉS, à valider
  changelog :   (jalons fusionnés par date — pas une ligne par artifact)
    2026-07-05  Création    pitch + plan posés (reconstruit depuis git)
    2026-07-05  Livraison    implémentation livrée, revue, reportée (reconstruit depuis git)
  delivery : absent   (aucun commit de livraison identifié avec certitude)

→ Valider les tags et écrire ? (oui / change les tags / corrige un champ / saute)
```

Règles de la boucle de validation :

- **Tags** : toujours demandés. L'utilisateur peut les remplacer ou en ajouter/retirer.
- **Titre fallback** (slug humanisé) : signale-le explicitement et invite à corriger.
- **Delivery** : si absent faute de certitude, dis-le ; l'utilisateur peut fournir un SHA/tag.
- **`--all`** : traite story par story. Autorise un « accepte tout » **uniquement** après avoir
  montré au moins la première proposition et fait valider une politique de tags — mais garde une
  sortie par story si une valeur paraît douteuse (titre fallback, dates incohérentes).

### Phase 4 — Écriture

Sur validation d'une story, **écris** son `metadata.json` (`Write`, JSON indenté 2 espaces,
`version: 1`), au schéma exact de `story-metadata.md` :

```json
{
  "version": 1,
  "title": "…",
  "created": "YYYY-MM-DD",
  "updated": "YYYY-MM-DD",
  "tags": ["…"],
  "changelog": [
    { "date": "YYYY-MM-DD", "type": "Création", "description": "…" }
  ],
  "delivery": { "release": "vX.Y.Z", "commit": "abc1234" }
}
```

Omets entièrement la clé `delivery` si la story n'est pas livrée (ne mets pas `delivery: null`).

Sur `--force` avec un fichier existant valide : **conserve** le `created` d'origine et fusionne le
changelog existant avec les entrées reconstruites (ne perds pas d'entrées réelles déjà présentes).

### Phase 5 — Bilan

```
Backfill terminé :
- N metadata.json créés
- M réparés (fichiers malformés)
- P stories avec delivery renseigné, Q laissées sans delivery (livraison non identifiée)
- R titres en fallback slug à revoir (signalés ci-dessus)

Prochaine étape : relis le board, puis commit type
`chore(story): reconstruire les metadata.json des stories antérieures`.
```

**Ne fais pas le commit toi-même** sans demande explicite (`/commit` s'en charge).

## Dégradation

- **Story non commitée** (dossier absent de git) : pas de date fiable. Propose de saisir
  `created`/`updated` à la main, ou de sauter la story. Ne mets **jamais** la date du jour par
  défaut sur une story ancienne — ce serait faux.
- **Pas un repo git** : seuls `title` et `tags` sont reconstructibles ; `created`/`updated`/
  `changelog`/`delivery` nécessitent une saisie manuelle. Préviens et laisse l'utilisateur décider.
- **H1 absent ou générique** : fallback slug humanisé, toujours signalé pour correction.
- **Rappel** : une story sans `metadata.json` s'affiche déjà (slug humanisé). Ce skill est un
  **enrichissement** — en cas de doute sur une valeur, l'absence est préférable à l'erreur.

## Pièges courants

- **Confondre date du dossier et date de l'artifact** : `created` = premier commit du **dossier**
  (peut être un `.gitkeep` ou un premier `.md`), pas forcément l'ajout du pitch. C'est voulu :
  `created` = naissance de la story dans le repo.
- **`git tag --contains` vide** : le commit de livraison n'est dans aucune release taguée → la
  story est livrée mais pas encore releasée. `delivery.commit` seul, `release: null` : c'est valide.
- **Slug ≠ thème** : le `--grep` sur le slug peut ne rien remonter (le commit de livraison ne cite
  pas le slug). N'invente pas de livraison ; laisse `delivery` absent.
- **Rebase/squash** : sur un historique réécrit, la date du « premier commit » peut être plus
  récente que la vraie création. Si l'utilisateur le signale, préfère sa date à celle de git.
- **`--force` destructeur** : ne re-dérive avec `--force` qu'une story explicitement ciblée, et
  préserve toujours `created` + les entrées de changelog réelles déjà présentes.

## Substitutions disponibles

- `$ARGUMENTS` — slug/chemin d'une story, ou flags `--all` / `--force`.
- `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` — contrat de schéma partagé (source de
  vérité du format, à relire avant d'écrire).
