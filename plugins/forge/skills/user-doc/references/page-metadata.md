# Métadonnées de la documentation fonctionnelle

Ce que `/user-doc` écrit **en plus** du standard dans `documentation/` : les champs de traçabilité
vers le pipeline forge, les identifiants de cas, le manifeste de captures par défaut, et la manière
dont ils permettent de retracer l'histoire de la documentation dans GitHub.

Le socle (arborescence, frontmatter, statuts, blocs, trace de relecture, navigation) est le
standard : [`documentation-standard.md`](../../../references/documentation-standard.md). Cette
référence ne le redit pas. Le pendant, côté story, est
[`story-metadata.md`](../../../references/story-metadata.md) : la story porte sa timeline dans
`metadata.json`, la page pointe vers ses stories par son frontmatter.

## Sommaire

- [Champs de traçabilité](#champs-de-traçabilité)
- [Identifiants de cas](#identifiants-de-cas)
- [Manifeste de captures par défaut](#manifeste-de-captures-par-défaut)
- [Procédure de mise à jour](#procédure-de-mise-à-jour)
- [Traçabilité GitHub](#traçabilité-github)

## Champs de traçabilité

Trois champs facultatifs du standard, que `/user-doc` remplit quand il écrit à partir d'une story :

```yaml
features: [import-clients-csv]
stories: [042-f-import-clients-csv, 057-f-import-clients-xlsx]
cases: [C01, C02, C04, C11]
```

| Champ | Règle |
|---|---|
| `features` | Clés de feature couvertes : le slug de backlog (`backlog` du `metadata.json`), sinon le slug de la story sans `NNN-f-`, en kebab-case. Elle ne change jamais, même si le titre évolue : c'est elle qui relie les pages d'une feature. Une seule en général ; plusieurs pour une explication transverse. |
| `stories` | Dossiers de story (sans `forge/story/`) qui ont façonné la page, ordre chronologique. Append-only. |
| `cases` | IDs des cas de la matrice que la page documente. Pour la référence de la feature : tous les cas non exclus. |

Une page écrite en mode Thème (sans story) n'a ni `stories` ni `features`. Elle a des `cases` si
elle a une matrice.

**Bannis** : `created`, `updated`, `history` (le standard les interdit : git les donne), SHA de
commit, numéro de version, nom d'auteur. Ils ont déjà une source de vérité : `git log` pour la date
et l'auteur, le `delivery` du `metadata.json` de la story pour la version.

## Identifiants de cas

- `C01`, `C02`… : uniques **au sein d'une feature** (ou d'un sujet, en mode Thème), attribués dans
  l'ordre de la matrice.
- La table « Cas et comportements » de la page de référence de la feature est le registre : elle
  donne pour chaque ID la situation et le comportement.
- Un ID n'est **jamais réutilisé**. Un cas supprimé par une story disparaît de la table, et le
  message de commit le nomme (« C07 retiré : la limite de 500 lignes est levée »).
- Un cas modifié garde son ID. Sa ligne est mise à jour.

## Manifeste de captures par défaut

Le standard laisse au projet le moyen de produire ses captures (`documentation/_conventions.md`).
**Quand le projet n'en déclare aucun**, `/user-doc` tient un manifeste `captures.yml` dans le
dossier `img/` où il range les images. Il décrit **comment reproduire** chaque capture, pour qu'un
rafraîchissement donne la même image, avec le même cadrage et les mêmes données, sur l'interface à
jour.

```yaml
viewport: 1280x800
captures:
  - file: import-clients-01-ecran.png
    case: C01
    page: clients/import/importer-des-clients.md
    url: /admin/customers/import
    account: admin
    state: aucun import en cours
    steps: Ouvrir Clients › Importer
    frame: page
    status: ok
    captured: 2026-11-14
  - file: import-clients-04-erreur-email.png
    case: C06
    page: clients/import/import-de-clients.md
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
| `viewport` | Commun au manifeste. Une capture peut le surcharger (`viewport: 390x844`) si le pitch vise le mobile. |
| `file` | Nom du fichier dans `img/`, préfixé de la clé de feature (`<feature>-<NN>-<description>.png`) pour ne pas entrer en collision avec les autres pages du dossier. **Stable** : une capture reprise garde son nom. |
| `case` | ID du cas illustré. Une capture illustre un seul cas. |
| `page` | Page qui l'affiche, relative à `documentation/`. |
| `url` | Chemin relatif à l'URL de base de l'environnement (lue dans l'`AGENTS.md`), jamais une URL complète ni un hôte. |
| `account` | Rôle du compte de test (`admin`, `client`), **jamais** un identifiant ni un mot de passe. |
| `state` | État des données nécessaire avant la prise de vue. |
| `steps` | Actions pour arriver à l'écran, en termes d'interface. |
| `frame` | `page`, ou `élément : <description>`. |
| `status` | `ok` · `à-faire` (pas encore prise, ou à reprendre). |
| `captured` | Date de la dernière prise de vue, `null` si à faire. |

Quand le projet déclare son propre moyen (des specs Playwright, par exemple), `/user-doc` l'utilise
et **n'écrit pas** de manifeste : il ajoute ou modifie la description de la capture dans ce moyen,
selon les conventions du projet, puis lance la commande de régénération qu'elles indiquent.

## Procédure de mise à jour

À chaque passe, pour chaque page **réellement modifiée** :

1. Lis le frontmatter existant.
2. Ajoute la story à `stories` si elle n'y est pas (pas de story en modes Thème et Reprendre).
3. Mets à jour `cases` (ajouts et retraits).
4. Statut : une page **créée** est `a-relire` ; une page `a-reprendre` dont toutes les demandes
   sont traitées repasse à `a-relire` ; une page `validee` repasse à `a-relire` et perd
   `validee_par` et `validee_le` (sa dernière version validée reste en ligne, standard §Versions
   publiées) ; une page `brouillon` ou `a-relire` garde le sien.
5. Applique les changements de contenu par `Edit` ciblé.

Une page que la passe ne modifie pas n'est pas touchée. Le manifeste, s'il existe, est mis à jour à
chaque capture prise, reprise ou ajoutée.

## Traçabilité GitHub

Trois mécanismes se complètent, sans qu'aucun SHA ne soit recopié dans un fichier.

**1. La page et le code partent dans le même commit.** En mode story, `/user-doc` passe avant
`/forge:commit` : le commit de livraison embarque le code, les documents réalignés **et** les
pages. Donc :

- `git log --follow documentation/<page>.md` liste exactement les livraisons qui ont fait évoluer
  la page, et chaque commit pointe vers le code qu'elle décrit ;
- sur GitHub, l'historique d'un fichier (« History ») donne la même chose, et « Blame » relie
  chaque ligne de la page à la livraison qui l'a introduite ;
- l'outil de lecture en tire les versions publiées de la page (standard, §Versions publiées).

**2. La page pointe vers ses stories, et la story vers son commit et sa version.** Le champ
`stories` mène à `forge/story/<NNN>-f-<slug>/metadata.json` : `delivery.release` donne le tag de
version, et le commit de livraison se retrouve par `git log` (voir `story-metadata.md` §Livraison
dans le commit qui livre). La question « depuis quelle version ce comportement est-il documenté ? »
se résout sans dupliquer l'information.

**3. Les images gardent leur nom.** Une capture reprise réécrit le même fichier : le diff GitHub du
commit affiche l'image avant/après (modes « 2-up », « Swipe », « Onion skin »).

**Documentation produite après la livraison** (modes Enrichir, Rafraîchir, Thème, Reprendre) : la
page part dans un commit de documentation à part. Le message de commit dit ce qui a changé ; le lien
vers le code se lit par `stories` quand il existe.
