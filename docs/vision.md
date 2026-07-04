# Vision — Forge Board

> Pitch en une phrase : un **tableau kanban de suivi** pour le développeur qui pilote ses projets avec le plugin forge, qui résout **l'invisibilité de l'avancement des stories** en scannant les documents produits par les skills et en les affichant comme des cartes qui progressent le long d'un pipeline visuel.

_Document vivant — enrichi au fil du cycle de vie, refondu lors d'un pivot stratégique. Date de dernière mise à jour : 2026-07-04._

_Nom « Forge Board » provisoire — à confirmer._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture du haut vers le bas = ordre chronologique. Détails fins dans `git log`.

| Date | Nature | Axe | Motif |
|------|--------|-----|-------|
| 2026-07-04 | Création | — | Vision initiale |

## Le problème

Les skills du plugin forge produisent un flux continu de documents Markdown — `pitch.md`, `plan.md`, `estimate.md`, `report.md`, etc. — rangés dans une arborescence `docs/story/NNN-<f|r|t>-<slug>/`. Chaque document marque une étape franchie dans le cycle d'une story. Mais cette progression n'est lisible **nulle part de façon synthétique** : pour savoir où en sont ses chantiers, l'utilisateur doit ouvrir les dossiers un par un et déduire l'état à partir des fichiers présents.

**Comment c'est résolu aujourd'hui** : exploration manuelle de l'arborescence `docs/story/`, dossier par dossier, story par story — et de tête pour agréger plusieurs projets.
**Pourquoi c'est insuffisant** : ça ne scale pas dès qu'il y a plusieurs stories ou plusieurs projets ; l'état vit dans la tête de l'utilisateur, pas dans un support partageable ; aucun coup d'œil ne dit « qu'est-ce qui est bloqué, qu'est-ce qui attend une review, qu'est-ce qui est livré ».
**Ampleur** : à chaque fois que l'utilisateur veut se resituer sur un projet — plusieurs fois par jour en phase active, sur potentiellement plusieurs repos en parallèle.

## L'audience

### Utilisateur principal

- **Persona** : développeur / product owner solo qui utilise le workflow forge pour cadrer et livrer ses stories, et qui jongle avec plusieurs chantiers, parfois répartis sur plusieurs repos.
- **Volume cible** : un seul utilisateur en V1 (outil personnel). Ordre de grandeur : quelques repos, quelques dizaines de stories actives.
- **Ce qui le bloque aujourd'hui** : impossible de répondre en quelques secondes à « où en est le projet X ? » sans ouvrir l'arborescence à la main.

### Utilisateurs secondaires

- Aucun en V1. (Un futur usage « livrable pour les utilisateurs de la marketplace forge » est envisageable mais explicitement hors périmètre — cf. anti-objectifs.)

### Hors cible explicite

- Les équipes multi-utilisateurs travaillant sur un kanban partagé (pas de backend collaboratif en V1).
- Les développeurs qui n'utilisent pas forge : sans les documents produits par les skills, l'app n'a rien à afficher.

## La proposition de valeur

### Bénéfice utilisateur

Passer de **plusieurs minutes d'exploration manuelle** à **quelques secondes de lecture** pour savoir où en est chaque story de chaque projet. Un tableau kanban unique, alimenté automatiquement, où chaque carte progresse de colonne en colonne au fur et à mesure que les skills produisent leurs documents.

### Pourquoi nous, plutôt qu'eux

Un outil kanban générique (Trello, Linear, GitHub Projects) exigerait une saisie manuelle de l'état, qui dériverait immédiatement de la réalité des fichiers. Forge Board **déduit** l'état depuis les documents réels produits par les skills : zéro double-saisie, zéro dérive.

### Unfair advantage

La connaissance intime de la convention forge (`docs/story/NNN-<f|r|t>-<slug>/`, noms de fichiers par skill, tracks feature/refacto/tech). C'est cette convention qui permet de mapper fichiers → étape → colonne de façon fiable — ce qu'aucun outil générique ne sait faire.

## Métriques de succès

### North Star

**Temps pour se resituer** : délai entre « je veux savoir où en est le projet X » et « je le sais ». Objectif : quelques secondes (lecture du tableau) contre plusieurs minutes (exploration manuelle). Mesurable par observation directe de l'usage.

### Métriques secondaires

- **Acquisition** : sans objet en V1 (usage personnel).
- **Activation** : nombre de projets déclarés et scannés avec succès.
- **Rétention** : l'app est-elle ouverte dans le flux de travail réel plutôt que de retourner explorer `docs/story/` à la main.
- **Monétisation** : sans objet.

### Fidélité (métrique de qualité clé)

**Zéro écart** entre l'état affiché sur une carte et l'état réel des fichiers du repo. La sync ne doit jamais mentir : une carte affichée en colonne « Review » doit correspondre à une story dont les fichiers attestent réellement cette étape.

### Seuils

- À 6 mois : l'app remplace effectivement l'exploration manuelle de `docs/story/` pour l'usage quotidien de l'utilisateur.
- À 1 an : sans objet formel (outil personnel — le succès se juge à l'usage réel, pas à un volume).
- À 3 ans : horizon non planifié.

### Signal d'arrêt

L'utilisateur retourne systématiquement explorer les dossiers à la main parce que le tableau est trop souvent faux, incomplet ou pénible à synchroniser.

## Principes produit

1. **Lecture seule — la vérité vit dans les fichiers.** L'app ne modifie jamais `docs/story/` ni le repo. Les documents produits par les skills restent la source unique de vérité ; le tableau n'en est qu'une projection.
2. **État déduit, jamais saisi.** La colonne d'une carte se calcule à partir des fichiers présents dans le dossier de la story, jamais renseignée à la main. Pas de double-saisie possible, donc pas de dérive.
3. **Sync fidèle avant tout.** En cas de doute entre « joli » et « exact », l'exactitude gagne. Une carte affichée doit toujours refléter l'état réel du repo scanné.
4. **Zéro friction d'ouverture.** On lance, on voit. Déclarer un projet et obtenir son tableau doit rester une opération de quelques secondes.

## Anti-objectifs

Ce qu'on **refuse explicitement** de faire en V1, et pourquoi :

- **Éditer des documents ou déclencher des skills depuis l'app** — le périmètre V1 est strictement la *représentation visuelle*. L'app observe, elle n'agit pas.
- **Backend partagé / multi-utilisateur** — pas de collaboration temps réel, pas de comptes ; c'est un outil personnel.
- **Intégration profonde GitHub/GitLab (issues, PR, CI)** — l'accès distant sert uniquement à *lire* `docs/story/`, pas à gérer le cycle de vie git.
- **Application mobile native** — hors sujet pour un outil de pilotage sur poste de travail.
- **Livrable public pour la marketplace forge** — envisageable plus tard, mais ne doit pas contraindre les choix de la V1.

## Hypothèses critiques

| # | Hypothèse | Comment l'invalider | Statut |
|---|-----------|---------------------|--------|
| 1 | La structure `docs/story/NNN-<f|r|t>-<slug>/` et les noms de fichiers produits par les skills sont assez réguliers pour déduire l'étape d'une story de façon fiable | Scanner plusieurs stories réelles et vérifier que le mapping fichiers → colonne est déterministe et sans ambiguïté | À tester |
| 2 | La correspondance fichier → étape reste stable dans le temps (les skills ne renomment pas leurs livrables sans prévenir) | Suivre les évolutions du plugin forge ; casser le mapping = signal | À tester |
| 3 | Un accès en lecture via API/MCP GitHub/GitLab suffit à récupérer le contenu de `docs/story/` sans friction (auth, quotas, latence acceptables) | Prototyper la lecture d'un `docs/story/` distant sur un vrai repo GitHub et un vrai repo GitLab | À tester |
| 4 | Un pipeline unifié (colonnes communes à tous les tracks, track = badge) reste lisible malgré des tracks aux étapes hétérogènes | Poser des stories des 3 tracks sur le même tableau et vérifier que la lecture reste claire | À tester |

## Risques externes

- **Évolution du plugin forge** : un renommage de livrable ou un changement de convention `docs/story/` casse le mapping. Mitigation : centraliser le mapping fichiers → colonnes en un point unique, facile à mettre à jour.
- **API/MCP GitHub/GitLab** : changements d'API, quotas de rate-limit, exigences d'authentification qui compliquent la lecture distante. Mitigation : abstraire l'accès repo derrière une interface, prévoir un fallback.

## Horizons

### 3-6 mois

- Déclarer un projet en fournissant un accès en lecture à son repo (GitHub/GitLab, via API ou MCP).
- Scanner `docs/story/` du repo distant et en extraire les stories.
- Afficher un kanban à pipeline unifié : colonnes = étapes du cycle d'une story, cartes = stories, badge de track (feature / refacto / tech) sur chaque carte.
- Rafraîchir le tableau depuis l'état réel du repo (sync).

### 1 an

- Vue multi-projets consolidée.
- Confort de lecture : filtres par track, ouverture rapide d'un document depuis une carte.

### 3 ans

Non planifié à ce stade (outil personnel).

## Notes pour les features à venir

Pointeurs bruts pour `/product-backlog` et `/feature-pitch` — **ne pas concevoir ici, juste lister** :

- **Déclaration de projet** : formulaire / mécanisme pour enregistrer un repo + son accès en lecture (token, MCP…).
- **Connecteur de lecture repo** : couche d'accès distant GitHub/GitLab (API/MCP) qui récupère l'arborescence et le contenu de `docs/story/`.
- **Moteur de mapping fichiers → étape** : le cœur métier — table de correspondance entre présence de fichiers (`pitch.md`, `plan.md`, `report.md`…) et colonne du pipeline unifié, avec gestion des 3 tracks.
- **Rendu kanban** : colonnes, cartes, badges de track, code couleur.
- **Sync / rafraîchissement** : déclenchement (manuel ? périodique ?) et gestion des écarts.
- **Cohabitation dans le repo** : ce repo héberge désormais le plugin forge *et* l'app ; à cadrer (où vit le code de l'app, comment il coexiste avec `plugins/`).
- **Choix de stack** : à trancher au `/stack` avant le backlog (Tauri, Electron, web local Vite+React, TUI…).
