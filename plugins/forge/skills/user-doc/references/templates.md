# Templates des pages

Squelettes des quatre types de pages, de l'index et des emplacements de capture. Les règles de
chaque type sont dans [`diataxis.md`](diataxis.md), le frontmatter et le manifeste dans
[`page-metadata.md`](page-metadata.md). Les commentaires `<!-- … -->` sont des consignes : ils ne
restent pas dans la page produite, sauf le marqueur d'index.

Les exemples prennent une feature fictive, « import de clients depuis un fichier CSV ».

## Sommaire

- [Guide pratique](#guide-pratique)
- [Référence](#référence)
- [Tutoriel](#tutoriel)
- [Explication](#explication)
- [Capture](#capture)
- [Capture manquante](#capture-manquante)
- [Index](#index)

## Guide pratique

`documentation/how-to/<feature>-<objectif>.md`

```markdown
---
title: Importer des clients depuis un fichier CSV
type: how-to
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C01, C02, C05, C06]
created: 2026-11-14
updated: 2026-11-14
history:
  - date: 2026-11-14
    story: 042-f-import-clients-csv
    change: Création — import nominal, en-têtes facultatifs, correction d'un rejet.
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

![Écran d'import : un champ de sélection de fichier et le bouton « Importer », désactivé tant qu'aucun fichier n'est choisi](../assets/import-clients-csv/01-ecran-import.png)
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
Voir [C05 dans la référence](../reference/import-clients-csv.md#cas-et-comportements).

## Voir aussi

- [Import de clients — référence](../reference/import-clients-csv.md) : tous les cas et tous les
  messages.
- [La détection des doublons](../explanation/detection-des-doublons.md)
```

## Référence

`documentation/reference/<feature>.md` : une par feature. La structure suit celle de l'interface,
et elle est identique d'une feature à l'autre.

```markdown
---
title: Import de clients — référence
type: reference
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C01, C02, C03, C04, C05, C06, C07, C08, C10]
created: 2026-11-14
updated: 2026-11-14
history:
  - date: 2026-11-14
    story: 042-f-import-clients-csv
    change: Création — 9 cas documentés (C09 exclu).
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

![Bandeau d'erreur sous le formulaire : « 1 ligne ignorée : e-mail manquant (ligne 7) »](../assets/import-clients-csv/04-erreur-colonne-email.png)
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

- L'import ne met pas à jour un client existant : voir [La détection des doublons](../explanation/detection-des-doublons.md).
```

## Tutoriel

`documentation/tutorials/<feature>.md` : seulement si la feature ouvre un nouveau parcours.

```markdown
---
title: Votre premier import de clients
type: tutorial
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C01]
created: 2026-11-14
updated: 2026-11-14
history:
  - date: 2026-11-14
    story: 042-f-import-clients-csv
    change: Création.
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

![…](../assets/import-clients-csv/01-ecran-import.png)

Remarquez que le bouton **Importer** reste grisé tant qu'aucun fichier n'est choisi.

## Étape 2 : …

## Ce que nous avons fait

Nous avons importé trois clients et vérifié leur présence dans la liste.

## Pour aller plus loin

- [Importer des clients depuis un fichier CSV](../how-to/import-clients-csv-importer.md)
```

## Explication

`documentation/explanation/<sujet>.md` : un sujet, pas un écran.

```markdown
---
title: La détection des doublons
type: explanation
features: [import-clients-csv]
stories: [042-f-import-clients-csv]
cases: [C07, C08]
created: 2026-11-14
updated: 2026-11-14
history:
  - date: 2026-11-14
    story: 042-f-import-clients-csv
    change: Création.
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

- [Import de clients — référence](../reference/import-clients-csv.md), cas C07 et C08.
```

## Capture

```markdown
![<ce que montre l'écran, et ce qu'il faut y remarquer>](../assets/<feature>/<NN>-<description>.png)
*<légende courte, ID du cas si la page en couvre plusieurs>*
```

Le texte alternatif suffit à quelqu'un qui ne voit pas l'image : il nomme l'écran, l'état et le
texte important visible.

## Capture manquante

Quand une capture n'a pas pu être prise (`--no-capture`, environnement indisponible) :

```markdown
> **Capture à venir** — C06 : bandeau d'erreur après l'import d'un fichier dont une ligne n'a pas d'e-mail.
<!-- user-doc:capture-a-faire 04-erreur-colonne-email.png -->
```

L'entrée du manifeste porte `status: à-faire`. `/user-doc --refresh` remplace le bloc par l'image.

## Index

`documentation/README.md` : créé à la première passe, mis à jour à chaque passe. Le marqueur de
la première ligne signale que l'index est tenu par `/user-doc`.

```markdown
<!-- forge:user-doc -->
# Documentation

Documentation fonctionnelle du produit, organisée selon [Diataxis](https://diataxis.fr/) :

- **Tutoriels** : pour apprendre, en faisant un premier parcours guidé.
- **Guides pratiques** : pour atteindre un objectif précis.
- **Référence** : pour vérifier un comportement, un champ, un message.
- **Explications** : pour comprendre pourquoi le produit fonctionne ainsi.

## Par fonctionnalité

| Fonctionnalité | Tutoriel | Guides | Référence | Explications | Mise à jour |
|---|---|---|---|---|---|
| Import de clients | [Premier import](tutorials/import-clients-csv.md) | [Importer](how-to/import-clients-csv-importer.md) · [Corriger un rejet](how-to/import-clients-csv-corriger-un-rejet.md) | [Référence](reference/import-clients-csv.md) | [Doublons](explanation/detection-des-doublons.md) | 2026-11-14 |

## Tutoriels

- [Votre premier import de clients](tutorials/import-clients-csv.md)

## Guides pratiques

- [Importer des clients depuis un fichier CSV](how-to/import-clients-csv-importer.md)

## Référence

- [Import de clients — référence](reference/import-clients-csv.md)

## Explications

- [La détection des doublons](explanation/detection-des-doublons.md)
```

La colonne « Mise à jour » reprend le `updated` le plus récent parmi les pages de la feature. Les
lignes sont triées par nom de fonctionnalité ; dans chaque section, les entrées sont triées par
titre.
