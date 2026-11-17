# Standard de la documentation fonctionnelle — version 1

Le contrat du dossier `documentation/` d'un projet forge. Il lie trois parties qui ne se
connaissent pas :

- **le producteur** : `/forge:user-doc`, qui écrit les pages ;
- **le projet** : ses thèmes, ses profils, ses gabarits et sa façon de produire les captures ;
- **les outils de lecture et de relecture** : un site, une application de relecture, quelle que
  soit leur techno. Ils lisent des fichiers Markdown et YAML, sans exécuter de code.

Un outil qui respecte ce contrat lit la documentation de n'importe quel projet forge. Ce que le
standard ne fixe pas appartient au projet, qui le décrit dans `documentation/_conventions.md`.

## Sommaire

- [Principes](#principes)
- [Arborescence](#arborescence)
- [Frontmatter d'une page](#frontmatter-dune-page)
- [Frontmatter de la racine](#frontmatter-de-la-racine)
- [Statuts](#statuts)
- [Versions publiées](#versions-publiées)
- [Blocs et empreintes](#blocs-et-empreintes)
- [Trace de relecture](#trace-de-relecture)
- [Navigation calculée](#navigation-calculée)
- [Captures](#captures)
- [Ce qui appartient au projet](#ce-qui-appartient-au-projet)
- [Détecter le standard](#détecter-le-standard)

## Principes

1. **Frontmatter au maximum.** Tout ce qui concerne une page est dans son en-tête. Un fichier à
   part n'existe que si la donnée ne tient pas dans un en-tête (la trace de relecture par bloc).
2. **Ne stocker que ce qui est rédigé.** Ce qui se déduit des pages ne s'écrit pas dans les pages :
   navigation, sommaires, listes par profil, état de la documentation, historique (git le donne).
3. **Aucun code à exécuter.** L'ordre, la hiérarchie et les paramètres du projet se lisent dans
   des fichiers de données, jamais dans un fichier JavaScript ou de configuration d'outil.
4. **Rien de propre à un outil.** Ni `sidebars.js`, ni `mkdocs.yml`, ni champ réservé à un
   générateur de site. Un outil traduit le standard dans son format, au moment de publier.
5. **Git est la source de vérité.** L'état d'une page à un commit se lit dans son en-tête à ce
   commit, sans calcul. Toute la documentation, avec toutes les versions publiées de toutes les
   pages et leurs images, se reconstruit depuis le seul dépôt (§Versions publiées).

## Arborescence

```
documentation/
├── index.md                     ← racine : accueil de la doc + paramètres du projet
├── _conventions.md              ← règles propres au projet (non publié)
├── _relecture/                  ← traces de relecture, une par page relue (non publié)
│   └── equipe/
│       └── inscrire-une-personne.yml
└── equipe/                      ← un dossier = un niveau de hiérarchie
    ├── index.md                 ← présente le dossier (type: sommaire)
    ├── img/                     ← images des pages de ce dossier et de ses sous-dossiers
    └── personnes-et-acces/
        ├── index.md
        ├── roles-et-positions.md
        └── inscrire-une-personne.md
```

- **Une page** est un fichier `.md`. Son identifiant est son chemin sans `documentation/` ni
  `.md` (`equipe/personnes-et-acces/inscrire-une-personne`).
- **Un dossier** est un niveau de hiérarchie. Il a toujours un `index.md`, qui le présente.
- **L'organisation** (par thème, par module, par parcours) est un choix du projet. Le standard ne
  fixe que le mécanisme : des dossiers et un ordre.
- **Les noms** de fichiers et de dossiers sont en kebab-case, sans accents.
- **Préfixe `_`** : un fichier ou un dossier dont le nom commence par `_` n'est jamais publié, à
  quelque niveau qu'il soit.
- **Les images** vont dans un dossier `img/`, celui du dossier de la page ou d'un dossier parent.
  Elles se référencent en relatif (`![…](../img/nom.png)`). Elles sont versionnées dans git, ni
  LFS ni URL externe, et entrent dans le même commit que la page qui les utilise.

## Frontmatter d'une page

```yaml
---
title: Inscrire une personne
description: Créer le compte d'un nouvel arrivant et lui donner son rôle.
type: guide
public: [direction]
ordre: 20
statut: a-relire
titre_court: Inscrire
avant_de_lire: [equipe/personnes-et-acces/roles-et-positions]
et_ensuite: equipe/profils/index
features: [inscription-personnes]
stories: [012-f-inscription]
cases: [C01, C02, C05]
---
```

| Champ | Obligatoire | Règle |
|---|---|---|
| `title` | oui | Le titre de la page. Le `# H1` du corps, s'il existe, est identique. |
| `description` | oui | Une phrase. Elle sert aux sommaires, au « Et ensuite ? » et aux moteurs de recherche. |
| `type` | oui | `explication`, `guide`, `reference`, `tutoriel` ou `sommaire` (voir ci-dessous). |
| `public` | oui | Les profils visés, pris dans `profils` de la racine. Une page pour tous liste tous les profils de base. |
| `ordre` | oui, sauf la racine | Un entier, par ordre croissant. Pour une page : sa place dans son dossier. Pour le `index.md` d'un dossier : la place du dossier parmi les pages et dossiers de son dossier parent (dans son propre dossier, l'`index.md` vient toujours en premier). Laisser des trous (10, 20, 30) pour insérer sans renuméroter. |
| `statut` | oui | `brouillon`, `a-relire`, `a-reprendre` ou `validee` (voir §Statuts). |
| `titre_court` | non | Le libellé dans un menu, quand `title` y est trop long. |
| `avant_de_lire` | non | Des identifiants de pages à lire d'abord. |
| `et_ensuite` | non | Un identifiant de page, qui remplace la page suivante calculée. |
| `features`, `stories`, `cases` | non | La traçabilité vers le pipeline forge, écrite par `/forge:user-doc` (voir sa référence `page-metadata.md`). Une page écrite hors d'une story ne les a pas. |
| `validee_par`, `validee_le` | si `validee` | Écrits avec `statut: validee` par qui valide (outil de relecture, commande du projet, personne) : son nom, la date `AAAA-MM-JJ`. Retirés quand la page quitte `validee`. |

**Les types** suivent Diátaxis, plus un type de navigation :

| Valeur | Diátaxis | La page… |
|---|---|---|
| `tutoriel` | Tutoriel | fait apprendre en faisant, sur un parcours complet. |
| `guide` | Guide pratique | mène à un objectif précis, par étapes. |
| `reference` | Référence | décrit un écran ou une notion de façon exhaustive. |
| `explication` | Explication | fait comprendre le pourquoi. |
| `sommaire` | — | présente un dossier (son `index.md`) ou la documentation (la racine). |

**Les listes** (`public`, `avant_de_lire`, `features`, `stories`, `cases`) s'écrivent sur une seule
ligne, en notation `[a, b]`. Une recherche ligne à ligne (`^stories:.*042-f-`) suffit alors à
retrouver les pages.

**Champs interdits** : tout champ propre à un générateur de site (`sidebar_label`,
`sidebar_position`, `pagination_prev`, `slug`…), tout historique (`created`, `updated`,
`history`) et tout numéro de version : git les donne, et l'outil de lecture les affiche. Le champ
`empreinte`, retiré du standard, n'a plus de sens : un outil l'ignore.

## Frontmatter de la racine

`documentation/index.md` est une page de type `sommaire`. Son en-tête porte en plus les paramètres
du projet :

```yaml
---
title: CrewDesk, la documentation
description: À quoi sert CrewDesk, comment la documentation est organisée et par où commencer.
type: sommaire
public: [direction, lead, prod]
statut: a-relire
standard: 1
projet: CrewDesk
profils:
  direction: { libelle: Direction, description: "administre l'équipe, pilote et arbitre" }
  lead: { libelle: Lead, description: "anime une équipe et tient ses lots" }
  prod: { libelle: Prod, description: "réalise le travail et déclare son temps" }
  responsable: { libelle: Responsable de lot, description: "tient les chiffres d'un lot", position: true }
---
```

| Champ | Règle |
|---|---|
| `standard` | La version de ce standard que suit la documentation (`1`). C'est le marqueur qui la fait reconnaître. |
| `projet` | Le nom du produit documenté, tel que le lecteur le connaît. |
| `profils` | Les profils de lecteurs : identifiant (kebab-case) → `libelle`, `description`, et `position: true` pour un profil qui s'ajoute à un autre (un responsable de lot est aussi lead ou prod). Les profils sans `position` sont les profils de base. |

Un projet qui ne distingue pas ses lecteurs déclare un seul profil (`tous: { libelle: Tous }`).

## Statuts

| Statut | Écrit par | Sens |
|---|---|---|
| `brouillon` | le producteur | En cours d'écriture. Pas proposé à la relecture. |
| `a-relire` | le producteur | Écrite, elle attend sa relecture. Statut d'une page nouvelle, d'une page reprise, et d'une page validée que l'on modifie. |
| `a-reprendre` | la relecture | Relue, avec des demandes de réécriture que le producteur doit traiter. |
| `validee` | la validation | Validée, avec `validee_par` et `validee_le`. |

**Le statut écrit fait foi.** Une page est validée à un commit si son en-tête y porte
`statut: validee`, quel que soit le circuit qui l'a écrit : outil de relecture, commande de secours
du projet, ou personne. Aucun outil ne calcule un autre état.

**Une page validée que l'on modifie repasse à `a-relire`**, et perd ses champs de validation. Sa
dernière version validée reste en ligne jusqu'à la validation suivante (§Versions publiées). Le
producteur n'écrit jamais `validee`.

## Versions publiées

**Une version publiée** d'une page est un commit où la page est `validee` et dont le texte lu
diffère de celui de la version publiée précédente. Le texte lu est le corps de la page, sans
en-tête ni commentaires HTML, espaces consécutifs réduits à un seul, espaces de début et de fin
retirés.

- Les commits où la page n'est pas `validee` (`brouillon`, `a-relire`, `a-reprendre`, allers-retours
  de relecture) ne sont pas des versions.
- Un changement d'en-tête seul (statut, profils, ordre) ne crée pas de version.
- Les versions se numérotent dans l'ordre de git, de V1 à Vn, et suivent la page à travers ses
  renommages (`git log --follow`).
- **La version en ligne** est la dernière, Vn. Une page qui n'est plus `validee` au commit publié
  reste lisible dans sa dernière version publiée. Une page jamais validée n'est pas publiée.
- Chaque version se lit avec les images de son propre commit.

Un outil de lecture régénère ainsi toute la documentation, versions comprises, à partir du seul
dépôt : rien de ce qu'il publie n'existe ailleurs que dans git.

## Blocs et empreintes

**Un bloc** est un nœud de premier niveau du corps Markdown : un titre, un paragraphe, une liste,
un tableau, une image (avec sa légende), un encadré `:::`, un bloc de code ou un schéma. C'est
l'unité de la relecture. Le `title` et la `description` de l'en-tête sont aussi des blocs, puisque
le lecteur les voit.

**L'empreinte d'un bloc** est un condensat (SHA-256, tronqué à 12 caractères hexadécimaux) de son
texte normalisé : espaces consécutifs réduits à un seul, espaces de début et de fin retirés. Deux
blocs au texte identique ont la même empreinte : c'est ce qui permet de reconnaître un bloc
inchangé quand la page bouge autour de lui.

Les empreintes ne servent qu'à la relecture (§Trace de relecture). Une page n'a pas d'empreinte :
ce qui a changé depuis sa validation se lit dans git.

## Trace de relecture

Une relecture laisse une trace par page : `documentation/_relecture/<identifiant de la page>.yml`.
Elle est écrite par l'outil de relecture (dans la PR qui porte la relecture), et mise à jour par
le producteur seulement pour noter qu'une demande de réécriture est traitée.

```yaml
page: equipe/personnes-et-acces/inscrire-une-personne
relue_par: "Thomas Cerqueus"
relue_le: 2026-11-20
blocs:
  - empreinte: 3f9a1c2b7d40
    decision: validee
  - empreinte: 77be02aa91c3
    decision: modifiee
  - empreinte: c41d8e0f12ab
    decision: refusee
    motif: "Doublon de la page Rôles et positions"
  - empreinte: 0a52f7e3c9d1
    decision: annotee
    annotation: "Le bouton s'appelle « Archiver », pas « Supprimer »."
    reprise: a-faire
```

| Champ | Règle |
|---|---|
| `page` | L'identifiant de la page. |
| `relue_par`, `relue_le` | Le relecteur et la date de la relecture. |
| `blocs[].empreinte` | L'empreinte du bloc **après** la relecture (pour `modifiee`, celle du nouveau texte ; pour `refusee`, celle du bloc retiré). |
| `blocs[].decision` | `validee`, `modifiee` (le nouveau texte est dans la page), `refusee` (le bloc est retiré de la page), `annotee` (demande de réécriture, le bloc est inchangé). |
| `blocs[].motif` | Facultatif, pour un refus. |
| `blocs[].annotation` | Obligatoire pour `annotee` : ce que le relecteur demande. |
| `blocs[].reprise` | Pour `annotee` : `a-faire`, puis `faite` quand le producteur a réécrit le bloc. |

Une nouvelle relecture **remplace** la trace : la précédente reste dans l'historique git. Une page
dont la trace contient une `reprise: a-faire` est `a-reprendre`.

**Relecture suivante** : un bloc dont l'empreinte figure dans la trace avec `validee` ou `modifiee`
est présenté comme déjà validé. Seuls les blocs nouveaux ou changés attendent une décision.

## Navigation calculée

Aucune navigation n'est écrite dans les pages. L'outil de lecture la calcule à partir des dossiers,
de `ordre` et du frontmatter :

- le fil d'Ariane (les `index.md` des dossiers parents) ;
- la page précédente et la page suivante (parcours en profondeur, `index.md` d'abord, puis les pages
  par `ordre`), sauf `et_ensuite` qui remplace la suivante ;
- le sommaire d'un dossier (ses pages et sous-dossiers, avec leur `description`) ;
- les pages par profil (celles dont `public` contient le profil, dans l'ordre de lecture) ;
- l'état de la documentation (les pages par statut) ;
- les versions publiées d'une page (§Versions publiées).

Un `index.md` de dossier contient donc un texte de présentation, et **pas** la liste de ses pages.

## Captures

- Une capture d'écran **se régénère, elle ne se retouche pas** : il existe toujours un moyen
  reproductible de la refaire, et c'est lui qu'on modifie.
- Ce moyen est **déclaré par le projet** dans `_conventions.md` (specs Playwright, script…). À
  défaut, c'est le manifeste `captures.yml` de `/forge:user-doc`, rangé à côté des images
  (`img/captures.yml`).
- Une image reprise **garde son nom** : le diff GitHub montre alors l'avant et l'après, et chaque
  version publiée de la page lit l'image de son propre commit. Elle part dans le même commit que
  la page qui l'utilise.
- Le texte alternatif décrit ce que montre l'image, pour qui ne la voit pas.

## Ce qui appartient au projet

`documentation/_conventions.md` décrit, pour les rédacteurs et pour `/forge:user-doc` :

- l'organisation en dossiers (thèmes, modules…) et où ranger une nouvelle page ;
- les gabarits propres au projet, quand ils précisent ceux du skill ;
- le vocabulaire imposé (renvoi au glossaire, mots interdits) ;
- le moyen de produire les captures et leurs règles (taille, cadrage, données) ;
- la commande de validation de secours, s'il y en a une.

Il ne redit pas le standard : il y renvoie.

## Détecter le standard

| Ce qu'on trouve | Conclusion |
|---|---|
| `documentation/index.md` avec `standard:` dans son en-tête | Documentation au standard, dans cette version. |
| `documentation/README.md` qui commence par `<!-- forge:user-doc -->` | Ancien format de `/forge:user-doc` (dossiers par type) : à migrer avant d'écrire. |
| `documentation/` sans l'un ni l'autre | Documentation tierce : ne pas la restructurer sans accord. |
| Pas de `documentation/` | Rien à migrer : la première passe crée la racine. |
