# Métadonnées de story (`metadata.json`)

Référence partagée par tous les skills qui écrivent dans un dossier de story
(`/feature-pitch`, `/feature-plan`, `/refactor-plan`, `/tech-plan`, les trois `*-implem`,
`/sync`, `/user-doc`, `/adr`, `/estimate`, `/review`, `/commit`, `/release`). Elle décrit le
**contrat unique** du fichier de métadonnées d'une story : son schéma, ses formats, et la
**procédure d'écriture/mise à jour** à appliquer.

Objectif : le Forge Board (app de visualisation) lit ce fichier pour afficher des cartes
riches (vrai titre, âge, dernière activité, tags, badge de livraison), sans jamais l'écrire.
**La source de vérité est ce fichier, produit par les skills.** L'app ne fait que lire.

Il sert aussi de **preuve d'avancement** : c'est de lui (champ `backlog` + `delivery`) que
`/sync` et `/product-backlog` dérivent l'état coché des lignes de `forge/product-backlog.md`.
Un état d'avancement ne s'y saisit donc jamais à la main — il se dérive d'ici.

## Emplacement et nom

Un fichier **`metadata.json`** à la racine du dossier de la story :

```
forge/story/NNN-<f|r|t>-<slug>/metadata.json
```

Un seul par story, quel que soit le track (`f`, `r`, `t`). Il cohabite avec `pitch.md`,
`plan.md`, `report.md`, etc. Il n'est **jamais** compté comme un document de pipeline : le
Board déduit l'étape des `.md` présents (`brief`/`pitch`/`plan`/`review`/`report`) et n'emprunte au
`metadata.json` que la livraison — une story est **livrée** quand `delivery` est renseigné, jamais
parce qu'un `report.md` existe (il est écrit *avant* le commit). Ne le référence pas comme un
livrable dans les autres documents.

## Schéma v3

Le schéma est **embarqué chez tous les utilisateurs forge** : il doit rester stable. Toute
évolution incrémente `version` et reste rétrocompatible côté lecture.

```json
{
  "version": 3,
  "title": "Afficher le kanban d'un projet",
  "created": "2026-07-01",
  "updated": "2026-07-05",
  "tags": ["board", "kanban"],
  "backlog": "kanban-projet",
  "changelog": [
    { "date": "2026-07-01", "type": "Création", "description": "Pitch initial rédigé." },
    { "date": "2026-07-03", "type": "Planification", "description": "Plan technique validé." }
  ],
  "delivery": { "date": "2026-07-05", "release": "v4.3.0" }
}
```

| Champ       | Type        | Règle                                                                                     |
|-------------|-------------|-------------------------------------------------------------------------------------------|
| `version`   | entier      | Version du schéma. **`3`** depuis que `delivery` porte une date au lieu d'un SHA. Ne pas omettre. |
| `title`     | chaîne      | Le **vrai titre** de la story (le `# H1` du document principal), pas le slug.              |
| `created`   | `YYYY-MM-DD`| Date de création. **Écrite une seule fois**, jamais modifiée ensuite.                      |
| `updated`   | `YYYY-MM-DD`| Date de la **dernière** passe d'un skill sur la story. Rebougée à chaque écriture.         |
| `tags`      | liste       | Étiquettes **kebab-case**, proposées par le skill, **validées par l'utilisateur**. Dédupliquées. |
| `backlog`   | chaîne/absent| Slug de la ligne de `forge/product-backlog.md` dont la story est issue. **Absent** si la story ne vient d'aucune ligne de backlog. Voir §Rattachement au backlog. |
| `changelog` | liste       | Timeline consolidée, ordre chronologique. Chaque entrée : `{ date, type, description }`.   |
| `delivery`  | objet/absent| `{ date, release }`. `date` = jour de la livraison ; `release` = tag de version, `null` tant qu'aucune release ne l'a publiée. Absent tant que non livrée. `commit` (SHA) n'est plus écrit à la livraison — voir §Livraison dans le commit qui livre. |

**Formats** : dates toujours `YYYY-MM-DD` (aligné sur les changelogs existants). `type` d'une
entrée de changelog = un mot court capitalisé (`Création`, `Planification`, `Implémentation`,
`Review`, `Sync`, `Documentation`, `ADR`, `Estimation`, `Livraison`…). `Report` reste valide en lecture : les
stories antérieures à la v7 du plugin en portent une ; aucune passe n'en produit plus.

**`description` = une phrase courte** (repère : 200 caractères) qui dit le **résultat** de la passe,
pas son détail — le détail vit dans le document produit (`plan.md`, `report.md`…). Elle ne répète
pas ce qu'une entrée précédente dit déjà : si l'implem a énuméré ses écarts, la passe `Sync` dit
combien elle en a réalignés, pas lesquels. **Deux passes dérogent**, parce qu'elles transmettent un
*pourquoi* à l'aval et qu'aucun document ne le porte à leur place :

- **`Implémentation`** énumère les écarts avec le plan et leur raison, en résumés courts — `/sync`
  y cherche la raison de chaque écart.
- **`Review`** (entrée de fin de passe) énumère les findings corrigés, tag compris — c'est la seule
  trace de la revue quand `review.md` n'est pas conservé.

Même dans ces deux cas : une énumération de résumés, pas d'explication.

### Livraison dans le commit qui livre

`delivery` est écrit **dans le commit de livraison lui-même**, pas après : `/commit` l'ajoute au
`metadata.json` juste avant `git commit`, et `/release` complète `release` dans le commit qu'il
tague. Aucune livraison ne coûte donc un commit de plus.

Ce choix a une conséquence : le fichier ne peut pas contenir le SHA du commit qui le livre. Un
SHA est calculé sur le contenu du commit ; l'écrire dedans change le contenu, donc le SHA. Un
`--amend` ou un rebase le change aussi. Le SHA n'est donc plus **stocké**, il se **retrouve** :

```bash
# Commit de livraison d'une story : le plus ancien commit qui a touché la clé `delivery`.
git log --follow --format=%h -G'"delivery"' -- forge/story/<NNN>-<f|r|t>-<slug>/metadata.json | tail -1
```

`--follow` traverse les déplacements (`/forge:migrate-forge`). Ne pas y ajouter `--reverse` :
combiné à `--follow`, il ne renvoie rien. `tail -1` prend le plus ancien commit.

**Exception** : quand `delivery.commit` est présent, il fait foi et la commande ne sert pas. C'est
le cas des fichiers v1/v2, et de ceux que `/backfill-metadata` reconstruit : il connaît le SHA d'un
commit **passé**, qu'il peut écrire sans contradiction — et sans lui, la commande désignerait le
commit du backfill.

### Compatibilité v2 → v3

La v3 remplace, dans `delivery`, le `commit` écrit à la livraison par une `date`. Un fichier v2
reste **valide en lecture** : son `delivery.commit` prouve la livraison et désigne le commit. Ne
le supprime jamais ; un fichier passe en `version: 3` à la première passe qui le réécrit, en
gardant ce `commit` tel quel. Côté lecteurs, une story est **livrée** dès que `delivery` est
présent avec une `date`, un `commit` ou une `release` non nuls.

### Compatibilité v1 → v2

`backlog` est le **seul** ajout de la v2, et il est facultatif. Un `metadata.json` en
`version: 1` reste **parfaitement valide en lecture** : il se lit comme une story sans
rattachement backlog. Aucune migration de masse n'est requise ; un fichier passe en
`version: 3` naturellement, à la première passe d'un skill qui le réécrit.

Côté lecteurs (Forge Board, `/status`, réconciliation de `/product-backlog`) : traiter un
`backlog` absent comme « non rattachée », jamais comme une erreur.

### Rattachement au backlog

Ce champ est le **lien exact** entre une ligne de `forge/product-backlog.md` et la story qui
la réalise. Il existe parce que le slug d'une ligne de backlog est *pressenti* : `/feature-pitch`
a le droit de l'affiner au cadrage, et un rapprochement par comparaison de chaînes serait donc
faux un jour sur N. Le lien est porté **par la story**, pas par le backlog — de cette façon
aucun skill de cadrage n'a besoin d'écrire dans `product-backlog.md`, qui reste la propriété
de `/product-backlog` (+ `/sync`, voir `skill-boundaries.md` §3).

Règles :

- **Qui l'écrit** : `/feature-pitch`, à la création, **quand et seulement quand** il a retrouvé
  la ligne de backlog correspondante. `/backfill-metadata` peut le reconstruire rétroactivement.
- **Quelle valeur** : le slug **de la ligne de backlog** (`import-clients-csv`), pas le nom du
  dossier de story (`042-f-import-clients-csv`), pas le slug affiné de la story s'il a divergé.
- **Absent par défaut** : pas de ligne retrouvée, pas de `forge/product-backlog.md` dans le
  projet, story `r-`/`t-` (un refacto ou une évolution technique ne réalise pas une ligne de
  backlog fonctionnel) → le champ **n'est pas écrit**. Jamais de chaîne vide, jamais de valeur
  devinée : c'est ce champ qui fait cocher une case d'avancement, une valeur fausse coche la
  mauvaise ligne.
- **Plusieurs stories pour une ligne** : c'est permis (un MVP minimal puis un enrichissement).
  Elles portent toutes le même `backlog` ; côté backlog, l'avancement affiché est celui de la
  story la plus avancée.

## Procédure d'écriture

À chaque passe d'un skill concerné, **avant de terminer** :

1. **Lis** `metadata.json` s'il existe (`Read` sur le chemin ci-dessus).
   - Absent → tu es en création (voir plus bas).
   - Présent → charge l'objet, tu vas le **fusionner** (jamais l'écraser en aveugle).
2. **Détermine la date du jour** au format `YYYY-MM-DD`. Si tu n'as pas de date fiable en
   contexte, demande-la ou déduis-la de l'environnement — ne l'invente pas.
3. **Applique les mutations propres à ton skill** (tableau ci-dessous).
4. **Réécris le fichier complet** (`Write`), JSON indenté 2 espaces, `version: 3`.

### Qui écrit quoi

| Skill / moment                                              | Mutations                                                                                   |
|------------------------------------------------------------|---------------------------------------------------------------------------------------------|
| **Création** (`feature-pitch` pour `f` ; `refactor-plan` / `tech-plan` pour `r`/`t`) | Crée le fichier : `version:3`, `title` (le H1 réel), `created` = aujourd'hui, `updated` = aujourd'hui, `tags` (proposés puis **validés** par l'utilisateur), `backlog` = slug de la ligne de backlog si elle a été retrouvée (sinon champ absent — `feature-pitch` seul, les tracks `r`/`t` ne l'écrivent pas), `changelog` = une 1re entrée `Création`, `delivery` absent. |
| **Toute passe ultérieure** (`feature-plan`, `refactor-plan`/`tech-plan` en édition, `*-implem`, `sync`, `adr`, `estimate`, `review`) | Rebouge `updated` = aujourd'hui ; **append** une entrée de changelog (`type` = nature de la passe, `description` = ce qui a changé). Ne touche pas `created` ni `backlog`. Peut affiner `tags` (toujours validés). Si le fichier lu est en `version: 1` ou `2`, le réécrire en `version: 3` (sans inventer de `backlog`, sans toucher un `delivery.commit` existant). |
| **`user-doc`**                                             | Comme une passe ultérieure (entrée `Documentation`), **seulement si `delivery` est absent**. Sur une story déjà livrée, ne touche pas le fichier : la timeline de la story s'arrête à sa livraison, et la trace de la documentation faite après coup vit dans le frontmatter des pages. |
| **`commit`**                                               | **Avant** `git commit`, sur chaque story que ce commit livre (voir `commit` §Métadonnées) : crée `delivery` = `{ date: aujourd'hui, release: null }`, rebouge `updated` + entrée `Livraison`, et embarque le fichier dans le commit. N'écrit jamais `delivery.commit`. Ne touche pas une story dont `delivery` existe déjà. |
| **`release`**                                              | **Avant** de taguer, dans le commit qu'il tague (amendé, ou dédié avec `--commit`) : renseigne `delivery.release` = tag de version (ex. `v4.3.0`) sur chaque story livrée depuis la release précédente et encore sans `release`. Ne touche ni `date` ni `commit`. Rebouge `updated` + entrée `Release`. |
| **`backfill-metadata`**                                    | Reconstruit `delivery` d'une story déjà livrée : `date` du commit de livraison, `release` = plus ancien tag qui le contient, **et** `commit` = son SHA court (voir §Livraison dans le commit qui livre, exception). |

**Release en différé** : une story peut être livrée sans être publiée tout de suite. Un `delivery`
avec `release: null` est un état **valide** (la carte affiche « livré » sans numéro de version) ;
la prochaine `/release` le complète.

### Tags : proposés, validés

Le skill de cadrage **propose** des tags déduits du contenu (domaine, thème), en kebab-case.
L'utilisateur **tranche** à la rédaction (garde-fou anti-dérive : pas d'étiquette non validée).
Pas de taxonomie imposée au MVP — la validation utilisateur est le seul garde-fou.

## Changelog : source unique

La timeline vit **uniquement** dans `metadata.json`. Les anciennes tables de changelog en pied
de `pitch.md` / `plan.md` sont **abandonnées** : ne les produis plus, n'y append plus. `/sync`
qui documentait une divergence écrit désormais une entrée dans le changelog du `metadata.json`.

## Chemin d'accès (installation utilisateur)

Le fichier vit dans le **projet** de l'utilisateur (`forge/story/…`), pas dans le plugin. Tu y
accèdes par un chemin **relatif au projet** (`forge/story/NNN-<f|r|t>-<slug>/metadata.json`),
contrairement aux références du plugin qui, elles, se lisent via `${CLAUDE_SKILL_DIR}`.

## Dégradation (rappel côté app)

Une story **sans** `metadata.json`, ou avec un fichier **malformé**, s'affiche quand même : la
carte retombe sur le slug humanisé, sans tags/dates/badge. Mieux vaut pas de donnée qu'une
donnée fausse — d'où l'exigence de n'écrire que des valeurs vraies (surtout `updated`).
