# Métadonnées de la documentation fonctionnelle

Contrat des métadonnées écrites par `/user-doc` sous `documentation/` : le frontmatter de chaque
page, le manifeste de captures de chaque feature, et la manière dont les deux permettent de
retracer l'histoire de la documentation dans GitHub.

Le pendant, côté story, est [`story-metadata.md`](../../../references/story-metadata.md) : la
story porte sa timeline dans `metadata.json`, la page porte la sienne dans son frontmatter, et
chacune pointe vers l'autre.

## Sommaire

- [Arborescence](#arborescence)
- [Frontmatter](#frontmatter)
- [Manifeste de captures](#manifeste-de-captures)
- [Identifiants de cas](#identifiants-de-cas)
- [Procédure de mise à jour](#procédure-de-mise-à-jour)
- [Traçabilité GitHub](#traçabilité-github)

## Arborescence

```
documentation/
├── README.md                              ← index, marqué <!-- forge:user-doc -->
├── tutorials/<feature>.md
├── how-to/<feature>-<objectif>.md         ← un guide par objectif
├── reference/<feature>.md                 ← un par feature, registre des cas
├── explanation/<sujet>.md                 ← un par sujet, peut servir plusieurs features
└── assets/<feature>/
    ├── captures.yml                       ← manifeste de captures de la feature
    ├── 01-<description>.png
    └── 02-<description>.png
```

- `<feature>` est la clé de feature (slug de backlog, sinon slug de la story sans `NNN-f-`), en
  kebab-case. Elle ne change jamais, même si le titre évolue : c'est elle qui relie les pages.
- `<objectif>` et `<sujet>` sont en kebab-case, dans la langue du pitch (`corriger-un-rejet`).
- `documentation/` est distinct de `docs/`. `docs/` est l'atelier interne du pipeline (stories,
  ADR, vision) ; `documentation/` s'adresse aux utilisateurs du produit et peut être publié tel
  quel (rendu GitHub, mkdocs, site statique).

## Frontmatter

Chaque page ouvre sur un bloc YAML. GitHub l'affiche sous forme de tableau en tête du fichier :
il reste donc court, et tout ce qui sert à reproduire les captures vit dans le manifeste.

```yaml
---
title: Importer des clients depuis un fichier CSV
type: how-to
features: [import-clients-csv]
stories: [042-f-import-clients-csv, 057-f-import-clients-xlsx]
cases: [C01, C02, C04, C11]
created: 2026-11-14
updated: 2026-12-02
history:
  - date: 2026-11-14
    story: 042-f-import-clients-csv
    change: Création — import nominal, en-têtes facultatifs, correction d'un rejet.
  - date: 2026-12-02
    story: 057-f-import-clients-xlsx
    change: Ajout du format XLSX (C11), capture 02 reprise.
---
```

| Champ | Type | Règle |
|---|---|---|
| `title` | chaîne | Identique au `# H1` de la page. |
| `type` | énum | `tutorial` · `how-to` · `reference` · `explanation`. Cohérent avec le dossier. |
| `features` | liste | Clés de feature couvertes. Une seule en général ; plusieurs pour une explication transverse. |
| `stories` | liste | Dossiers de story (sans `docs/story/`) qui ont façonné la page, ordre chronologique. Append-only. |
| `cases` | liste | IDs des cas de la matrice que la page documente. Pour la référence : tous les cas non exclus. |
| `created` | `YYYY-MM-DD` | Écrit une fois, jamais modifié. |
| `updated` | `YYYY-MM-DD` | Date de la dernière passe qui a **modifié** la page. |
| `history` | liste | Une entrée par passe qui a modifié la page : `date`, `story` (ou `—` pour un rafraîchissement sans story), `change` (une phrase : le résultat, IDs de cas compris). En mode Rafraîchir, `story` vaut `—`. Append-only, ordre chronologique. |

**Listes sur une seule ligne** (`features`, `stories`, `cases`, en notation `[a, b]`) : le skill
retrouve les pages d'une feature ou d'une story par une recherche ligne à ligne
(`^features:.*<clé>`). Une liste écrite sur plusieurs lignes ne serait plus trouvée. Seul
`history`, qui n'est jamais recherché, s'écrit en bloc.

**Bannis** : SHA de commit, numéro de version, nom d'auteur. Ils ont déjà une source de vérité :
le `delivery` du `metadata.json` de la story pour le commit et la version, `git log` pour
l'auteur. Avant le commit, le SHA n'existe d'ailleurs pas encore (voir §Traçabilité).

## Manifeste de captures

Un fichier `documentation/assets/<feature>/captures.yml` par feature. Il décrit **comment
reproduire** chaque capture, pour qu'un rafraîchissement donne la même image, avec le même
cadrage et les mêmes données, sur l'interface à jour.

```yaml
viewport: 1280x800
captures:
  - file: 01-ecran-import.png
    case: C01
    page: how-to/import-clients-csv-importer.md
    url: /admin/customers/import
    account: admin
    state: aucun import en cours
    steps: Ouvrir Clients › Importer
    frame: page
    status: ok
    captured: 2026-11-14
  - file: 04-erreur-colonne-email.png
    case: C06
    page: reference/import-clients-csv.md
    url: /admin/customers/import
    account: admin
    state: fichier de test avec une ligne sans e-mail
    steps: Choisir le fichier, cliquer sur « Importer »
    frame: "élément : bandeau d'erreur sous le formulaire"
    status: à-faire
    captured: null
```

| Champ | Règle |
|---|---|
| `viewport` | Commun à la feature. Une capture peut le surcharger (`viewport: 390x844`) si le pitch vise le mobile. |
| `file` | Nom du fichier dans le dossier, `NN-<description>.png`. **Stable** : une capture reprise garde son nom. |
| `case` | ID du cas illustré. Une capture illustre un seul cas. |
| `page` | Page qui l'affiche, relative à `documentation/`. |
| `url` | Chemin relatif à l'URL de base de l'environnement (lue dans l'`AGENTS.md`), jamais une URL complète ni un hôte. |
| `account` | Rôle du compte de test (`admin`, `client`), **jamais** un identifiant ni un mot de passe. |
| `state` | État des données nécessaire avant la prise de vue. |
| `steps` | Actions pour arriver à l'écran, en termes d'interface. |
| `frame` | `page`, ou `élément : <description>`. |
| `status` | `ok` · `à-faire` (pas encore prise, ou à reprendre). |
| `captured` | Date de la dernière prise de vue, `null` si à faire. |

## Identifiants de cas

- `C01`, `C02`… : uniques **au sein d'une feature**, attribués dans l'ordre de la matrice.
- La table « Cas et comportements » de `reference/<feature>.md` est le registre : elle donne pour
  chaque ID la situation et le comportement.
- Un ID n'est **jamais réutilisé**. Un cas supprimé par une story disparaît de la table, et
  l'entrée `history` de la passe le nomme (« C07 retiré : la limite de 500 lignes est levée »).
- Un cas modifié garde son ID. Sa ligne est mise à jour et l'entrée `history` le cite.

## Procédure de mise à jour

À chaque passe, pour chaque page **réellement modifiée** :

1. Lis le frontmatter existant.
2. Ajoute la story à `stories` si elle n'y est pas.
3. Mets à jour `cases` (ajouts et retraits).
4. Rebouge `updated` à la date du jour (`YYYY-MM-DD`, jamais inventée : déduite de
   l'environnement ou demandée).
5. Ajoute une entrée à `history`.
6. Applique les changements de contenu par `Edit` ciblé.

Une page que la passe ne modifie pas n'est pas touchée, pas même pour sa date. Le manifeste est mis
à jour à chaque capture prise, reprise ou ajoutée.

## Traçabilité GitHub

Trois mécanismes se complètent, sans qu'aucun SHA ne soit recopié dans un fichier.

**1. La page et le code partent dans le même commit.** `/user-doc` passe avant `/forge:commit` :
le commit de livraison de la story embarque le code, les documents réalignés **et** les pages.
Donc :

- `git log --follow documentation/how-to/<page>.md` liste exactement les livraisons qui ont fait
  évoluer la page, et chaque commit pointe vers le code qu'elle décrit ;
- sur GitHub, l'historique d'un fichier (« History ») donne la même chose, et « Blame » relie
  chaque ligne de la page à la livraison qui l'a introduite.

**2. La page pointe vers ses stories, et la story vers son commit et sa version.** Le champ
`stories` mène à `docs/story/<NNN>-f-<slug>/metadata.json`, dont `delivery.commit` et
`delivery.release` donnent le commit et le tag de version. La question « depuis quelle version ce
comportement est-il documenté ? » se résout sans dupliquer l'information.

**3. Les images gardent leur nom.** Une capture reprise réécrit le même fichier : le diff GitHub du
commit affiche l'image avant/après (modes « 2-up », « Swipe », « Onion skin »). On voit d'un coup
d'œil ce que l'évolution a changé à l'écran.

**Documentation produite après la livraison** (modes Enrichir et Rafraîchir sur une story déjà
livrée) : la page part dans un commit de documentation à part. Le lien vers le code se lit alors
par `stories`, et l'entrée `history` nomme la story. Le `metadata.json` de la story, déjà livrée,
n'est pas modifié (voir le SKILL.md, phase 6).
