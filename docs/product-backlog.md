# Product Backlog — Forge Board

> Carte des capacités fonctionnelles et backlog priorisé dérivé de `docs/vision.md`.

_Document vivant — enrichi/édité au fil du cycle de vie, refondu lors d'un pivot. Date de dernière mise à jour : 2026-07-04._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture chronologique. Détails fins dans `git log`.

| Date | Nature | Éléments | Motif |
|------|--------|----------|-------|
| 2026-07-04 | Création | — | Backlog initial dérivé de la vision |

## Domaines fonctionnels

| # | Domaine | Résumé en une ligne |
|---|---------|---------------------|
| D1 | Accès | Protéger l'app et les tokens stockés derrière une connexion locale. |
| D2 | Projets | Déclarer, vérifier, lister et maintenir les repos forge suivis. |
| D3 | Synchronisation | Lire `docs/story/` à distance et en déduire l'état de chaque story. |
| D4 | Tableau kanban | Projeter les stories en cartes le long du pipeline visuel unifié. |

## Capacités

### D1 — Accès

- **C1.1** — L'utilisateur peut se connecter localement pour accéder à ses projets et tableaux (et protéger les tokens stockés).
- **C1.2** — L'utilisateur peut se déconnecter.

### D2 — Projets

- **C2.1** — L'utilisateur peut déclarer un projet en fournissant l'URL de son repo (GitHub/GitLab) et un token d'accès en lecture.
- **C2.2** — Le système peut déduire le nom du projet depuis le repo (pas de saisie manuelle du nom).
- **C2.3** — Le système peut vérifier qu'un repo utilise forge (présence du dossier `docs/story/`) et refuser/signaler un repo non éligible.
- **C2.4** — L'utilisateur peut consulter la liste de ses projets déclarés et ouvrir l'un d'eux.
- **C2.5** — L'utilisateur peut éditer un projet (renouveler le token, corriger l'URL) ou le retirer.

### D3 — Synchronisation

- **C3.1** — Le système peut lire l'arborescence et le contenu de `docs/story/` d'un repo distant.
- **C3.2** — Le système peut déduire l'étape (colonne) de chaque story à partir des fichiers présents (moteur de mapping, 3 tracks, pipeline unifié).
- **C3.3** — L'utilisateur peut déclencher une synchronisation (rafraîchir le tableau depuis l'état réel du repo).
- **C3.4** — Le système peut signaler les écarts et erreurs de sync (repo injoignable, token invalide, story ambiguë).

### D4 — Tableau kanban

- **C4.1** — L'utilisateur peut consulter le kanban d'un projet (colonnes = étapes du pipeline, cartes = stories).
- **C4.2** — L'utilisateur peut lire l'identité d'une carte : badge de track (feature / refacto / tech), identifiant `NNN-<slug>`, titre.
- **C4.3** — L'utilisateur peut ouvrir un document d'une story depuis sa carte.
- **C4.4** — L'utilisateur peut filtrer les cartes par track.
- **C4.5** — L'utilisateur peut consulter une vue consolidée sur plusieurs projets.

## Parcours utilisateurs principaux

### P1 — Se resituer sur un projet

- **Acteur** : développeur / product owner solo (utilisateur principal de la vision).
- **Déclencheur** : « où en est le projet X ? » en cours de journée.
- **Étapes** : C1.1 → C2.4 → C4.1 → (C3.3 si besoin de fraîcheur) → C4.3.
- **État final** : l'avancement de chaque story est connu en quelques secondes.
- **Fréquence** : plusieurs fois par jour en phase active. **C'est le parcours du North Star.**

### P2 — Déclarer un nouveau projet forge

- **Acteur** : développeur solo.
- **Déclencheur** : un nouveau repo à suivre.
- **Étapes** : C1.1 → C2.1 → C2.2 → C2.3 → C3.1 → C3.2 → C4.1.
- **État final** : le projet est suivi, son kanban est affiché.
- **Fréquence** : rare (quelques fois — à chaque nouveau repo).

### P3 — Rafraîchir après avancement

- **Acteur** : développeur solo.
- **Déclencheur** : les skills forge ont produit de nouveaux documents dans `docs/story/`.
- **Étapes** : C3.3 → C3.1 → C3.2 → C4.1.
- **État final** : les cartes sont repositionnées selon l'état réel du repo.
- **Fréquence** : quotidienne en phase active.

### P4 — Réparer un accès cassé

- **Acteur** : développeur solo.
- **Déclencheur** : token expiré / révoqué, ou repo déplacé.
- **Étapes** : C3.4 (signalement) → C2.5 → C3.3.
- **État final** : la synchronisation est rétablie.
- **Fréquence** : rare.

## Règles métier transverses

### Permissions et rôles

- **Accès local unique** : toute l'application vit derrière la connexion locale (C1.1). Outil mono-utilisateur — pas de rôles multiples, pas de partage multi-utilisateur en V1 (anti-objectif vision « backend partagé / multi-utilisateur »).

### Workflows et états

- **État déduit, jamais saisi** : la colonne d'une carte se calcule à partir des fichiers présents dans le dossier de la story, jamais renseignée à la main (principe produit #2). Aucune transition d'état pilotée depuis l'app.
- **Fidélité avant confort** : une story dont les fichiers ne permettent pas de trancher l'étape est *signalée comme ambiguë* (C3.4), jamais devinée ni placée arbitrairement (principe #3, métrique Fidélité « zéro écart »).

### Contraintes de gestion

- **Lecture seule absolue** : l'app ne modifie jamais `docs/story/` ni le repo distant. La vérité vit dans les fichiers ; le tableau n'en est qu'une projection (principe #1, anti-objectif « éditer / déclencher des skills depuis l'app »).
- **Éligibilité forge** : un repo n'est suivable que s'il expose l'arborescence `docs/story/NNN-<f|r|t>-<slug>/`. Un repo sans `docs/story/` est refusé à la déclaration (C2.3).
- **Mapping centralisé** : la table de correspondance fichiers → colonne (C3.2) vit en un point unique, facile à mettre à jour quand la convention forge évolue (risque externe vision : renommage de livrable, changement de convention).

### Exigences réglementaires

- **Sécurité des tokens d'accès** : le token de lecture fourni à la déclaration (C2.1) est stocké chiffré au repos, n'est jamais réaffiché en clair après saisie, et n'apparaît jamais dans les logs. Sensible même en usage personnel.

### Conventions transverses

- **Convention forge comme contrat** : identité d'une story = `NNN-<f|r|t>-<slug>` ; track déduit de la lettre (`f`=feature, `r`=refacto, `t`=tech) ; pipeline unifié (colonnes communes aux 3 tracks, track = badge sur la carte — hypothèse vision #4).
- **Connecteurs distants** : GitHub au MVP, GitLab en V2 ; l'accès distant sert *uniquement à lire* `docs/story/` (anti-objectif « intégration profonde GitHub/GitLab »).

## Backlog priorisé

### MVP — Lancement initial

| Slug | Pitch | Capacités | Parcours | Dépendances | Justification vision |
|------|-------|-----------|----------|-------------|----------------------|
| `acces-local` | Se connecter/déconnecter localement pour protéger l'app et les tokens stockés | C1.1, C1.2 | P1, P2, P3, P4 | — | Anti-objectif « outil personnel » + sécurité des tokens |
| `declaration-projet` | Déclarer un projet via URL de repo + token de lecture, nom déduit du repo | C2.1, C2.2 | P2 | `acces-local` | Horizon 3-6 mois « Déclaration de projet » ; principe « zéro friction d'ouverture » |
| `connecteur-github-lecture` | Lire l'arborescence et le contenu de `docs/story/` d'un repo GitHub | C3.1 | P2, P3 | `declaration-projet` | Horizon 3-6 mois « Connecteur de lecture repo » ; hypothèse critique #3 |
| `verification-forge` | Vérifier la présence de `docs/story/` et refuser un repo non-forge | C2.3 | P2 | `connecteur-github-lecture` | Unfair advantage (convention forge) ; hypothèse critique #1 |
| `mapping-etapes` | Déduire la colonne de chaque story depuis les fichiers présents (3 tracks) | C3.2 | P1, P2, P3 | `connecteur-github-lecture` | Unfair advantage « moteur de mapping » ; principe #2 ; hypothèses #1 et #4 |
| `kanban-projet` | Afficher colonnes / cartes / badges de track et ouvrir un document | C4.1, C4.2, C4.3 | P1, P2 | `mapping-etapes` | North Star ; horizon 3-6 mois « rendu kanban à pipeline unifié » |
| `sync-manuelle` | Bouton rafraîchir + signalement des erreurs de synchronisation | C3.3, C3.4 | P1, P3, P4 | `mapping-etapes` | North Star « temps pour se resituer » ; principe #3 « sync fidèle » |
| `projets-liste` | Lister ses projets déclarés et ouvrir l'un d'eux | C2.4 | P1 | `declaration-projet` | Métrique d'activation « projets déclarés et scannés » |

### V2 — Court terme post-lancement

| Slug | Pitch | Capacités | Parcours | Dépendances | Justification vision |
|------|-------|-----------|----------|-------------|----------------------|
| `projets-edition` | Renouveler un token, corriger l'URL ou retirer un projet | C2.5 | P4 | `projets-liste` | Rétention (maintenir des accès valides dans la durée) |
| `connecteur-gitlab-lecture` | Étendre la lecture distante aux repos GitLab | C3.1 | P2, P3 | `mapping-etapes` | Audience (repos GitLab) ; risque externe « API GitLab » |
| `sync-periodique` | Rafraîchir automatiquement le tableau à intervalle planifié | C3.3 | P3 | `sync-manuelle` | Confort de lecture ; rétention (tableau toujours à jour) |
| `kanban-filtres-track` | Filtrer les cartes par track (feature / refacto / tech) | C4.4 | P1 | `kanban-projet` | Horizon 1 an « confort de lecture : filtres par track » |
| `vue-multi-projets` | Consulter un tableau consolidé sur plusieurs projets | C4.5 | P1 | `kanban-projet` | Horizon 1 an « vue multi-projets consolidée » |

### V3 — Long terme

_Aucune ligne à ce stade. La vision place l'horizon 3 ans en « non planifié » (outil personnel). Le « livrable public pour la marketplace forge » reste un anti-objectif explicite, hors backlog tant qu'il n'est pas ré-arbitré côté vision._

## Couverture

### Capacités couvertes par horizon

- **MVP** : C1.1, C1.2, C2.1, C2.2, C2.3, C2.4, C3.1 (GitHub), C3.2, C3.3, C3.4, C4.1, C4.2, C4.3.
- **V2** : C2.5, C3.1 (extension GitLab), C3.3 (déclenchement périodique), C4.4, C4.5.
- **V3** : —.

### Capacités non couvertes (à challenger)

- Aucune capacité orpheline : les 4 domaines sont entièrement couverts par le backlog MVP+V2.

### Parcours supportés

- **P1 — Se resituer** : entièrement supporté en MVP (connexion, liste, kanban, sync manuelle, ouverture de document).
- **P2 — Déclarer un projet** : entièrement supporté en MVP (GitHub uniquement ; GitLab arrive en V2 via `connecteur-gitlab-lecture`).
- **P3 — Rafraîchir** : supporté en MVP par la sync manuelle ; le confort du rafraîchissement automatique (`sync-periodique`) arrive en V2.
- **P4 — Réparer un accès** : partiellement supporté en MVP — le signalement d'erreur (C3.4) est présent, mais l'édition/renouvellement fin du token (`projets-edition`, C2.5) est en V2. Contournement MVP : retirer puis redéclarer le projet.

## Notes pour `/feature-pitch`

Pointeurs bruts pour aider le cadrage détaillé. **Ne pas concevoir ici** — juste lister.

- **`connecteur-github-lecture`** : trancher API GitHub REST/GraphQL vs serveur MCP (cf. `docs/stack.md` « décisions à trancher » et hypothèse vision #3). Abstraire l'accès repo derrière une interface pour préparer GitLab (V2) et un éventuel fallback — candidat `/adr`.
- **`mapping-etapes`** : c'est le cœur métier et le principal risque (hypothèse #1). Définir la table fichiers → colonne pour les 3 tracks, et le comportement sur story ambiguë (règle Fidélité). Prévoir un scan de plusieurs stories réelles pour valider le déterminisme avant de figer.
- **`declaration-projet`** : déduction du nom à préciser (nom du repo ? `owner/repo` ? titre lu dans un fichier ?). Gérer les URL GitHub sous plusieurs formes (https, ssh, avec/sans `.git`).
- **Sécurité tokens** (`acces-local` + `declaration-projet`) : stockage chiffré au repos, secret d'app pour la clé — sensibilité à cadrer dans les specs concernées, pas seulement en règle transverse.
- **`sync-manuelle`** : décider si l'état scanné est persisté (SQLite) ou recalculé à la volée à chaque ouverture (cf. `docs/stack.md` « persistance de l'état scanné à trancher »).
- **`kanban-projet`** : la DA « Paper » actuelle est le socle ; une DA plus moderne est un chantier design à part (hors backlog fonctionnel, cf. `docs/stack.md`).
