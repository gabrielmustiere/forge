---
name: migrate-legacy
description: Migre les anciens formats workflow — dossiers `<f|r|t>-NNN-<slug>/` → `NNN-<f|r|t>-<slug>/`, `feature.md`/`design.md` → `pitch.md`/`plan.md`, puis reformate optionnellement chaque artifact au gabarit unifié.
user_invocable: true
disable-model-invocation: true
argument-hint: "[chemin docs/story custom]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Glob
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(git status:*)
  - Bash(git mv:*)
  - Bash(git log:*)
  - Bash(grep:*)
---

# /migrate-legacy — Migration des anciens formats workflow

Le plugin `forge` a évolué trois fois sur le format des stories :

1. **v0.8** — ordre dans le nom du dossier : `<f|r|t>-NNN-<slug>/` → `NNN-<f|r|t>-<slug>/` (compteur en tête pour que le tri lexicographique de `ls` corresponde à l'ordre chronologique).
2. **v1.9** — homogénéisation des fichiers d'intention pour les 3 tracks :
   - track feature : `feature.md` → `pitch.md`, `design.md` → `plan.md`
   - track refacto / tech : déjà `plan.md`, rien à changer
   - cartographie rétro (`docs/feature-map/NNN-slug/feature.md`) → `overview.md`
3. **v1.1.0 (workflow plugin)** — gabarit unifié pour chaque artifact (`pitch.md`, `plan.md`, `report.md`, `review.md`) avec squelette de sections, guides `> _Skill : ..._` et commentaires HTML retirables, table de changelog en pied. Les templates de référence sont fournis par chaque skill de cadrage (`feature-pitch`, `feature-plan`, `refactor-plan`, `tech-plan`, `report`, `review`) — voir la table de mapping ci-dessous pour les chemins exacts.

Ce skill détecte les trois types de legacy. Étapes 1 et 2 : migration via `git mv` (préserve l'historique), aucune modif de contenu. Étape 3 : reformatage **interactif fichier par fichier** du contenu vers le gabarit unifié (l'utilisateur valide chaque proposition).

## Périmètre

**Renommages structurels (phases 1–3)** :

- les dossiers `docs/story/<f|r|t>-NNN-<slug>/` → `docs/story/NNN-<f|r|t>-<slug>/`
- dans chaque story de track feature (`docs/story/NNN-f-*/`) : `feature.md` → `pitch.md` et `design.md` → `plan.md`
- dans chaque dossier `docs/feature-map/NNN-slug/` : `feature.md` → `overview.md`

**Mise au format unifié (phase 4, optionnelle, après validation utilisateur)** — pour chaque story `docs/story/NNN-<f|r|t>-slug/` :

| Fichier        | Tracks concernés | Template de référence                                                |
|----------------|------------------|----------------------------------------------------------------------|
| `pitch.md`     | `f-` uniquement  | `${CLAUDE_SKILL_DIR}/../feature-pitch/references/template.md`        |
| `plan.md`      | `f-`             | `${CLAUDE_SKILL_DIR}/../feature-plan/references/template.md`         |
| `plan.md`      | `r-`             | `${CLAUDE_SKILL_DIR}/../refactor-plan/references/template.md`        |
| `plan.md`      | `t-`             | `${CLAUDE_SKILL_DIR}/../tech-plan/references/template.md`            |
| `report.md`    | `f-`, `r-`, `t-` | `${CLAUDE_SKILL_DIR}/../report/references/template.md`               |
| `review.md`    | `f-`, `r-`, `t-` | `${CLAUDE_SKILL_DIR}/../review/references/template.md`               |

`overview.md` dans `docs/feature-map/` n'est pas couvert par le gabarit unifié et reste tel quel.

Pour les références textuelles (`docs/story/f-042-...`, `feature.md`, `design.md`) dans le code, le CHANGELOG, le README ou ailleurs : le skill les **signale** mais ne les modifie pas automatiquement. L'utilisateur décide au cas par cas (les commits historiques resteront avec l'ancien chemin, c'est normal).

## Règles du mode interactif

1. **Ne jamais exécuter `git mv` ni `Write` tant que l'utilisateur n'a pas validé** le plan complet ("go", "exécute", "c'est bon").
2. **Lister tous les renommages prévus** sous forme de tableau avant exécution. L'utilisateur doit pouvoir relire la liste d'un coup d'œil.
3. **Reformatage fichier par fichier** — en phase 4, présenter le diff proposé d'un fichier et attendre validation (`oui` / `non` / `modifier`) avant de l'écrire. Jamais de batch silencieux.
4. **Vérifier l'état du repo** avant de commencer — abandonner si l'index n'est pas propre (modifs non commitées) pour ne pas mélanger une migration avec d'autres changements.
5. **Bloquer en cas de collision** — si un renommage écraserait un fichier ou dossier qui existerait déjà, signaler et arrêter.

## Déroulement

### Phase 1 — Détection

Vérifie d'abord que le repo est propre :

```bash
git status --short
```

S'il y a des modifs en cours, **stoppe** : « Le working tree n'est pas propre. Commit ou stash tes changements avant de migrer, pour que la migration soit un commit isolé reviewable. »

Détecte les trois types de legacy :

**A — Dossiers à l'ancien format** (`<X>-NNN-<slug>/`) :

```bash
ls -1d docs/story/[frt]-[0-9][0-9][0-9]-* 2>/dev/null
```

**B — Artifacts feature à l'ancien nom** (`feature.md`/`design.md` dans une story feature) :

```bash
find docs/story -type f \( -path '*-f-*/feature.md' -o -path 'docs/story/f-*/feature.md' \
                          -o -path '*-f-*/design.md' -o -path 'docs/story/f-*/design.md' \) 2>/dev/null
```

**C — Feature-map à l'ancien nom** (`feature.md` dans `docs/feature-map/`) :

```bash
find docs/feature-map -type f -name 'feature.md' 2>/dev/null
```

Si les trois listes sont vides : « Aucun élément à migrer. Le projet est déjà au format actuel. »

### Phase 2 — Plan de renommage

Construis le mapping pour les trois catégories et présente-les en tableaux séparés.

**A — Dossiers** : pour chaque `<X>-<NNN>-<slug>/`, nouveau nom = `<NNN>-<X>-<slug>/`.

```
| Avant                              | Après                              |
|------------------------------------|------------------------------------|
| docs/story/f-007-checkout-express  | docs/story/007-f-checkout-express  |
| docs/story/r-013-extract-pricing   | docs/story/013-r-extract-pricing   |
```

**B — Artifacts feature** (à appliquer **après** A, sur les chemins déjà au nouveau format de dossier) :

```
| Avant                                       | Après                                     |
|---------------------------------------------|-------------------------------------------|
| docs/story/007-f-checkout-express/feature.md | docs/story/007-f-checkout-express/pitch.md |
| docs/story/007-f-checkout-express/design.md  | docs/story/007-f-checkout-express/plan.md  |
```

**C — Feature-map** :

```
| Avant                                  | Après                                   |
|----------------------------------------|-----------------------------------------|
| docs/feature-map/001-promotions/feature.md | docs/feature-map/001-promotions/overview.md |
```

**Vérifie qu'aucune destination n'existe déjà** pour chaque renommage. En cas de collision, **stoppe** et demande à l'utilisateur de résoudre manuellement.

Cherche aussi les **références textuelles** ailleurs dans le repo, pour les signaler (sans les modifier) :

```bash
grep -rnE "docs/story/[frt]-[0-9]{3}-|feature\.md|design\.md" \
  --include="*.md" --include="*.txt" . 2>/dev/null \
  | grep -vE "^(docs/story|docs/feature-map|plugins/forge)/"
```

Présente la liste : « Ces fichiers contiennent des références aux anciens noms. Une fois la migration faite, ces liens seront cassés. Veux-tu que je les mette à jour aussi, ou tu préfères les laisser (ex: dans un CHANGELOG historique) ? »

### Phase 3 — Validation et exécution

Récapitule :

> Migration prête :
> - **N dossiers** à renommer (compteur en tête)
> - **M artifacts feature** à renommer (`feature.md`/`design.md` → `pitch.md`/`plan.md`)
> - **K feature-maps** à renommer (`feature.md` → `overview.md`)
> - **L références textuelles** dans X fichiers (à mettre à jour ou ignorer selon ta réponse)
> - Working tree propre, prêt pour un commit isolé
>
> Confirme pour exécuter ("go", "exécute", "c'est bon").

À la validation, exécute **dans l'ordre** :

**Étape A — Renommer les dossiers** (avant les fichiers, sinon les chemins B sont obsolètes) :

```bash
for src in docs/story/[frt]-[0-9][0-9][0-9]-*; do
  base=$(basename "$src")
  letter=${base:0:1}
  rest=${base:2}
  num=${rest:0:3}
  slug=${rest:4}
  dst="docs/story/${num}-${letter}-${slug}"
  git mv "$src" "$dst"
done
```

**Étape B — Renommer les artifacts dans les stories feature** :

```bash
for f in docs/story/*-f-*/feature.md; do
  [ -e "$f" ] || continue
  git mv "$f" "$(dirname "$f")/pitch.md"
done

for d in docs/story/*-f-*/design.md; do
  [ -e "$d" ] || continue
  git mv "$d" "$(dirname "$d")/plan.md"
done
```

**Étape C — Renommer feature.md dans feature-map** :

```bash
for f in docs/feature-map/*/feature.md; do
  [ -e "$f" ] || continue
  git mv "$f" "$(dirname "$f")/overview.md"
done
```

Si l'utilisateur a demandé la mise à jour des références textuelles, applique les `Edit` un par un.

### Phase 4 — Mise au format unifié (optionnelle)

Une fois les renommages terminés, propose à l'utilisateur de reformater le contenu de chaque artifact pour qu'il colle au gabarit unifié (sections, guides, table de changelog en pied).

> Renames terminés. Veux-tu maintenant reformater le contenu des artifacts au gabarit unifié ?
> - Tu peux skipper cette phase si les contenus actuels te conviennent (oui/non).
> - Si oui, je traite **fichier par fichier** et tu valides chaque proposition.

Si l'utilisateur refuse, saute à la Phase 5.

#### 4.1 — Inventaire des fichiers candidats

Liste les artifacts par story :

```bash
ls -1 docs/story/*/pitch.md docs/story/*/plan.md docs/story/*/report.md docs/story/*/review.md 2>/dev/null
```

Pour chaque fichier, détermine le **template applicable** en croisant le préfixe de dossier (`-f-`, `-r-`, `-t-`) avec le nom de fichier — voir la table dans §Périmètre.

Présente un récap :

```
| Story                                | Fichier     | Template appliqué                |
|--------------------------------------|-------------|----------------------------------|
| docs/story/007-f-checkout-express    | pitch.md    | feature-pitch/.../template.md    |
| docs/story/007-f-checkout-express    | plan.md     | feature-plan/.../template.md     |
| docs/story/013-r-extract-pricing     | plan.md     | refactor-plan/.../template.md    |
| docs/story/044-t-redis-cache         | plan.md     | tech-plan/.../template.md        |
| docs/story/007-f-checkout-express    | report.md   | report/.../template.md           |
```

#### 4.2 — Boucle fichier par fichier

Pour chaque fichier :

1. Lis le fichier existant.
2. Lis le template de référence correspondant (charger à la demande, pas tous d'avance).
3. **Mappe le contenu existant aux sections du template** :
   - Reprends les titres et l'ordre du template.
   - Pour chaque section du template, cherche dans le legacy un bloc qui couvre le même sujet (peu importe le titre exact) et reprends-le.
   - Conserve **tout** le contenu utile du legacy — ne supprime rien sans validation. Si une info legacy n'a pas de section claire dans le template, mets-la dans `## Notes pour le plan technique` (pitch) ou `## Questions ouvertes`, et signale-le.
   - Pour les sections du template absentes du legacy, garde le placeholder du template (`<…>`) en l'état — c'est l'utilisateur qui le complétera plus tard.
   - Retire les guides `> _Skill : ..._` et le bloc HTML `<!-- guide: ... -->` du template (ils servent à la rédaction initiale, pas au document final).
   - Ajoute la table de changelog en pied avec une ligne :
     ```
     | YYYY-MM-DD | Migration legacy | Reformatage au gabarit unifié v1.1.0 (contenu legacy préservé, sections réorganisées). |
     ```
4. Présente le diff à l'utilisateur :

   ```
   docs/story/007-f-checkout-express/pitch.md
   - Sections legacy : Contexte, Stories, Critères, Hors scope
   - Sections cible (gabarit) : Contexte, Utilisateurs concernés, User Stories, Règles métier, Critères d'acceptation, Hors scope, Impacts transverses, Notes pour le plan technique, Questions ouvertes
   - Sections ajoutées vides à compléter : Utilisateurs concernés, Règles métier, Impacts transverses
   - Sections legacy sans correspondance : « Notes brouillon » → déplacée en Notes pour le plan technique
   - Changelog : ajouté avec entrée du jour
   → Appliquer ce reformatage ? (oui / non / modifier)
   ```

5. Sur `oui` : `Write` le nouveau contenu.
   Sur `non` : laisse le fichier en l'état et passe au suivant.
   Sur `modifier` : demande à l'utilisateur ce qu'il veut ajuster, applique, reboucle sur la validation.

**Ne traite jamais plusieurs fichiers en parallèle sans validation** — un fichier, une validation, fichier suivant.

#### 4.3 — Bilan reformatage

À la fin de la boucle :

```
Reformatage terminé :
- N fichiers reformatés au gabarit unifié
- M fichiers laissés en l'état (refus utilisateur)
- K sections vides ajoutées (placeholders du template) à compléter ultérieurement
```

### Phase 5 — Vérification post-migration

Confirme :

```bash
git status --short                            # voir tous les renames + modifs de contenu
ls -1 docs/story/ | head -10                  # tri lexico = tri chrono
ls -1 docs/story/*-f-*/ 2>/dev/null | sort -u # plus de feature.md/design.md
```

Affiche le résultat :

> Migration terminée :
> - N dossiers renommés
> - M artifacts feature renommés (pitch.md + plan.md)
> - K feature-maps renommés (overview.md)
> - P artifacts reformatés au gabarit unifié (phase 4)
> - L références textuelles mises à jour
>
> Prochaine étape : commit avec un message clair, type `chore(story): migration vers le format unifié (NNN-tag, pitch.md/plan.md, overview.md, gabarit v1.1.0)`. Je peux faire le commit si tu veux.

**Ne fais pas le commit toi-même** sans validation explicite — c'est une action visible.

## Pièges courants

- **Migration partielle** : si tu trouves un mix `f-042-...` et `043-r-...`, ou un dossier feature avec à la fois `feature.md` et `pitch.md`, c'est qu'une migration précédente a été interrompue. Liste l'état et propose de finir le job en évitant les écrasements.
- **Numéros en doublon entre types** : si l'ancien format avait `f-042-x` ET `r-042-y` (compteur global pas respecté), après migration tu auras `042-f-x` et `042-r-y` — deux dossiers avec le même numéro. C'est moche mais pas cassé. Signale-le et demande à l'utilisateur s'il veut renuméroter (hors scope ici).
- **Slugs avec digits** : un slug genre `f-042-fix-bug-1234` doit donner `042-f-fix-bug-1234`. Le parsing `${num:0:3}` puis `${slug:4}` est fiable car le slug commence forcément par une lettre (kebab-case lowercase).
- **Stories non-feature avec un `design.md` orphelin** : si tu vois un `design.md` dans une story `r-` ou `t-`, c'est inhabituel — le track refacto/tech ne devait jamais en avoir. Signale-le à l'utilisateur, ne le renomme pas automatiquement.
- **Artifacts déjà au format unifié** : avant de reformater un fichier en phase 4, vérifie s'il contient déjà la table de changelog en pied et les sections nominales du template — si c'est le cas, propose de skipper plutôt que de tout réécrire (un reformatage inutile rend le diff illisible).
- **Frontmatter de tête variable** : les anciens artifacts ont parfois des `>` lines différentes (Date, Auteur, etc.). Préserve les infos utiles (Date, ADR liée) dans le frontmatter du template ; déplace le reste dans une section dédiée ou supprime après validation utilisateur.

## Substitutions disponibles

`$ARGUMENTS` — chemin custom à `docs/story/` si le projet utilise une convention différente (rare).
