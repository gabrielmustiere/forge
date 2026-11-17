# Templates des pages

Squelettes des quatre types de pages Diátaxis, des sommaires (dossier et racine) et des
emplacements de capture. Les règles de chaque type sont dans [`diataxis.md`](diataxis.md), le
frontmatter commun dans le standard
([`documentation-standard.md`](../../../references/documentation-standard.md)), la traçabilité et
le manifeste dans [`page-metadata.md`](page-metadata.md). Les commentaires `<!-- … -->` sont des
consignes : ils ne restent pas dans la page produite.

Quand `documentation/_conventions.md` du projet précise un gabarit (un encadré obligatoire, l'ordre
des sections), c'est lui qui s'applique.

Les exemples prennent une feature fictive, « import de clients depuis un fichier CSV », rangée par
le projet dans le dossier `clients/import/`.

## Sommaire

- [Guide pratique](#guide-pratique)
- [Référence](#référence)
- [Tutoriel](#tutoriel)
- [Explication](#explication)
- [Sommaire de dossier](#sommaire-de-dossier)
- [Racine](#racine)
- [Capture](#capture)
- [Capture manquante](#capture-manquante)

## Guide pratique

`documentation/<dossier>/<objectif-a-l-infinitif>.md`

```markdown
---
title: Importer des clients depuis un fichier CSV
description: Ajouter plusieurs clients d'un coup à partir d'un fichier CSV.
type: guide
public: [admin]
ordre: 20
statut: a-relire
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C01, C02, C05, C06]
---

# Importer des clients depuis un fichier CSV

Ce guide montre comment ajouter plusieurs clients d'un coup à partir d'un fichier CSV.

## Avant de commencer

- Vous avez le rôle **Administrateur**.
- Votre fichier est au format CSV, encodé en UTF-8, et fait moins de 5 Mo.
  <!-- les prérequis, rien d'autre : pas de pourquoi -->

## Étapes

### 1. Ouvrir l'écran d'import

Dans le menu, choisissez **Clients › Importer**.

![Écran d'import : un champ de sélection de fichier et le bouton « Importer », désactivé tant qu'aucun fichier n'est choisi](../img/import-clients-csv-01-ecran.png)
*L'écran d'import, avant le choix du fichier.*

### 2. Choisir le fichier

…

<!-- les variantes réelles, sous la forme « si… » -->
Si votre fichier n'a pas de ligne d'en-têtes, décochez **La première ligne contient les en-têtes**.

### 3. Lancer l'import

…

Une fois l'import terminé, le message « 42 clients importés » s'affiche et la liste des clients
s'ouvre.

## Si ça ne se passe pas comme prévu

<!-- un échec probable par sous-section, avec la sortie, et un lien vers sa ligne de référence -->

### Le message « Le fichier dépasse la taille maximale autorisée (5 Mo) » s'affiche

Découpez le fichier en plusieurs fichiers plus petits et importez-les l'un après l'autre.
Voir [C05 dans la référence](import-de-clients.md#cas-et-comportements).

## Voir aussi

- [Import de clients — référence](import-de-clients.md) : tous les cas et tous les messages.
- [La détection des doublons](../doublons/detection-des-doublons.md)
```

## Référence

`documentation/<dossier>/<ecran-ou-notion>.md` : une par feature. La structure suit celle de
l'interface, et elle est identique d'une feature à l'autre.

```markdown
---
title: Import de clients — référence
description: Tous les champs, cas et messages de l'import de clients.
type: reference
public: [admin]
ordre: 30
statut: a-relire
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C01, C02, C03, C04, C05, C06, C07, C08, C10]
---

# Import de clients — référence

Import en masse de fiches clients depuis un fichier CSV, dans le back-office.

## Accès

| Rôle | Accès |
|---|---|
| Administrateur | Écran **Clients › Importer**, lancement d'un import |
| Gestionnaire | Pas d'entrée de menu ; l'adresse directe affiche « Accès refusé » |

## Écran « Importer des clients »

| Élément | Type | Obligatoire | Valeurs | Défaut |
|---|---|---|---|---|
| Fichier | sélection de fichier | oui | `.csv`, 5 Mo maximum | — |
| La première ligne contient les en-têtes | case à cocher | non | cochée / décochée | cochée |

## Colonnes attendues

| Colonne | Obligatoire | Format | Exemple |
|---|---|---|---|
| `email` | oui | adresse e-mail | `jeanne@exemple.fr` |

## Cas et comportements

<!-- TOUS les cas de la matrice non exclus, ordre des IDs. Messages mot pour mot. -->

| ID | Situation | Comportement | Message affiché |
|---|---|---|---|
| C01 | Fichier valide | Les clients sont créés, la liste des clients s'ouvre | « 42 clients importés » |
| C05 | Fichier de plus de 5 Mo | L'import est refusé, rien n'est créé | « Le fichier dépasse la taille maximale autorisée (5 Mo) » |
| C06 | Une ligne sans e-mail | La ligne est ignorée, les autres sont importées | « 1 ligne ignorée : e-mail manquant (ligne 7) » |

![Bandeau d'erreur sous le formulaire : « 1 ligne ignorée : e-mail manquant (ligne 7) »](../img/import-clients-csv-04-erreur-email.png)
*C06 — une ligne sans e-mail.*

## États

<!-- si la feature a un cycle de vie : un état par ligne, ce qu'on peut y faire -->

| État | Signification | Actions possibles |
|---|---|---|

## Limites

- 5 Mo par fichier.
- Un seul import à la fois par compte.

## Effets sur le reste de l'application

- Chaque client importé reçoit l'e-mail de bienvenue.

## Hors périmètre

- L'import ne met pas à jour un client existant : voir [La détection des doublons](../doublons/detection-des-doublons.md).
```

## Tutoriel

`documentation/<dossier des tutoriels du projet>/<parcours>.md` : seulement si la feature ouvre un
nouveau parcours.

```markdown
---
title: Votre premier import de clients
description: Importer trois clients depuis un petit fichier, puis les retrouver dans la liste.
type: tutoriel
public: [admin]
ordre: 10
statut: a-relire
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C01]
---

# Votre premier import de clients

Dans ce tutoriel, nous allons importer trois clients depuis un petit fichier, puis les retrouver
dans la liste des clients.

## Ce qu'il vous faut

- Un compte **Administrateur**.
- Le fichier ci-dessous, enregistré sous le nom `clients.csv` :

  ```csv
  email,prenom,nom
  jeanne@exemple.fr,Jeanne,Martin
  …
  ```

## Étape 1 : ouvrir l'écran d'import

Choisissez **Clients › Importer**. Vous devez voir l'écran suivant :

![…](../img/import-clients-csv-01-ecran.png)

Remarquez que le bouton **Importer** reste grisé tant qu'aucun fichier n'est choisi.

## Étape 2 : …

## Ce que nous avons fait

Nous avons importé trois clients et vérifié leur présence dans la liste.

## Pour aller plus loin

- [Importer des clients depuis un fichier CSV](../clients/import/importer-des-clients.md)
```

## Explication

`documentation/<dossier>/<sujet>.md` : un sujet, pas un écran.

```markdown
---
title: La détection des doublons
description: Pourquoi un client déjà connu n'est jamais écrasé par un import.
type: explication
public: [admin]
ordre: 10
statut: a-relire
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C07, C08]
---

# La détection des doublons

<!-- le contexte et la portée du sujet, en un paragraphe -->

## D'où vient cette règle

<!-- le problème d'origine (§Problème du pitch), du point de vue de l'utilisateur -->

## Comment le produit tranche

<!-- la règle, ses compromis, ce qu'elle protège et ce qu'elle coûte -->

## Ce que le produit ne fait pas, et pourquoi

<!-- hors périmètre et alternatives écartées, quand ils éclairent l'usage -->

## Voir aussi

- [Import de clients — référence](../clients/import/import-de-clients.md), cas C07 et C08.
```

## Sommaire de dossier

`documentation/<dossier>/index.md` : créé avec le dossier. Il présente le dossier ; **il ne liste
pas ses pages** (l'outil de lecture calcule le sommaire, standard §Navigation calculée).

```markdown
---
title: Importer des clients
description: Faire entrer des clients en masse, et ce qui se passe pour ceux qui existent déjà.
type: sommaire
public: [admin]
ordre: 20
statut: a-relire
---

# Importer des clients

<!-- un paragraphe : de quoi il s'agit, pour qui, et dans quel ordre lire -->
L'import ajoute d'un coup les clients d'un fichier CSV. Ces pages s'adressent aux administrateurs :
commencez par le guide, gardez la référence pour vérifier un message.
```

Le `ordre` d'un `index.md` place le dossier parmi les pages et dossiers de son dossier parent.

## Racine

`documentation/index.md` : créé à la première passe sur un projet sans documentation. Les
paramètres du projet sont demandés à l'utilisateur, jamais devinés (standard §Frontmatter de la
racine).

```markdown
---
title: <Produit>, la documentation
description: À quoi sert <Produit>, comment la documentation est organisée et par où commencer.
type: sommaire
public: [admin, client]
statut: a-relire
standard: 1
projet: <Produit>
profils:
  admin: { libelle: Administrateur, description: "gère le catalogue et les clients" }
  client: { libelle: Client, description: "passe commande et suit ses livraisons" }
---

# <Produit>

<!-- ce que fait le produit, en quelques phrases, du point de vue de ses utilisateurs -->

## Par où commencer

<!-- un paragraphe par profil : ce qu'il vient chercher, et la première page à lire -->
```

À la même passe, `/user-doc` crée `documentation/_conventions.md`, avec les règles que le projet
doit fixer (standard §Ce qui appartient au projet), remplies de ce que l'utilisateur a tranché et
marquées « à compléter » pour le reste.

## Capture

```markdown
![<ce que montre l'écran, et ce qu'il faut y remarquer>](../img/<fichier>.png)
*<légende courte, ID du cas si la page en couvre plusieurs>*
```

Le texte alternatif suffit à quelqu'un qui ne voit pas l'image : il nomme l'écran, l'état et le
texte important visible.

## Capture manquante

Quand une capture n'a pas pu être prise (`--no-capture`, environnement indisponible) :

```markdown
:::note Capture à venir
C06 : bandeau d'erreur après l'import d'un fichier dont une ligne n'a pas d'e-mail.
:::
<!-- user-doc:capture-a-faire import-clients-csv-04-erreur-email.png -->
```

L'entrée du manifeste porte `status: à-faire` (ou le moyen de capture du projet garde la capture en
attente). `/user-doc --refresh` remplace le bloc par l'image.
