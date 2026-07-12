# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.7.0] - 2026-07-12

### Added
- **Clone local du repo d'un projet** (Forge Board) — depuis le kanban, un bouton clone (ou met à jour via `git pull --ff-only`) le dépôt d'un projet en local, en tâche de fond (job Messenger async), avec bascule d'état en direct (Live Component, sans reload). Auth git par `GIT_ASKPASS` (token jamais en argv ni dans `.git/config`). Premier maillon du pivot productif.
- **Expression d'un besoin en interview de cadrage** (Forge Board) — depuis un projet cloné, l'utilisateur exprime un besoin en langage libre ; le skill `feature-interview` tourne en headless (`claude -p`, ADR-0002) dans le clone local et mène l'interview tour par tour, ancrée sur le code réel. Le `brief.md` produit est présenté pour relecture puis, à validation, poussé sur une branche dédiée et ouvert en **PR draft GitHub** (jamais de merge ni d'écriture sur la branche principale). Parcours asynchrone, une interview active par projet.
- **Serveur MCP `symfony-ai-mate`** — outillage MCP pour le développement du board (config `mcp.json`, worker dédié).

### Changed
- `feature-interview` : le skill signale désormais explicitement quand il est prêt à conclure (Phase 3) et respecte une demande explicite de conclusion sans relancer un tour de questions — pour que l'utilisateur, qui sinon ne sait pas quand l'interview se termine, tienne clairement la fin du dialogue.

## [4.6.0] - 2026-07-05

### Added
- **Colonne « Idée » sur le board** (Forge Board) — une story qui n'a qu'un `brief.md` (idée dégrossie par interview) s'affiche désormais en première colonne du pipeline au lieu d'atterrir en « À vérifier ».

### Changed
- **Colonnes du board alignées sur le cycle de vie** (Forge Board) — le pipeline passe de « Cadrage / Planifié / Review » à cinq colonnes de cycle de vie : **Idée → Besoin → Cadré → Implémenté → Livré**. « À vérifier » ne contient plus que les dossiers réellement non reconnus et gagne une couleur d'anomalie distincte.
- **Filtre par tag en popover recherchable** (Forge Board) — le mur de chips laisse place à un popover recherchable multi-sélection (OR), tags actifs en pills retirables. Les colonnes vidées par le filtre se rétrécissent, et les libellés de colonne se clampent si besoin.

## [4.5.0] - 2026-07-05

### Added
- **Skill `backfill-metadata`** — reconstruit rétroactivement le `metadata.json` des stories écrites **avant** l'introduction du contrat de métadonnées. Déduit le `title` du H1 du document principal, `created`/`updated` de l'historique git du dossier de la story, la timeline `changelog` de la date d'apparition de chaque artifact (`pitch`/`plan`/`review`/`report`) avec **fusion des jalons de même date**, et `delivery` (`commit`/`release`) des commits et tags git. Les `tags` sont proposés puis **validés** par l'utilisateur ; aucune date n'est inventée (toujours issue de git) et `delivery` reste **absent** si la livraison n'est pas identifiable avec certitude. Ne réécrit jamais un `metadata.json` valide sauf `--force`, validation par story avant écriture. Écrit le même schéma v1 que les skills de cadrage (`references/story-metadata.md`).

## [4.4.0] - 2026-07-05

### Added
- **Métadonnées de story (`metadata.json`)** — chaque story forge porte désormais un fichier `metadata.json` (schéma v1 versionné : `title`, `created`, `updated`, `tags`, `changelog`, `delivery`) **produit et maintenu par les skills** via une référence partagée (`plugins/forge/references/story-metadata.md`). Les skills de création écrivent `title`/`created`/`tags`/première entrée ; chaque passe rebouge `updated` et append au changelog ; `commit`/`release` renseignent `delivery`. La timeline consolidée vit dans ce fichier — les tables de changelog en pied de `pitch.md`/`plan.md` sont abandonnées.
- **Cartes de board enrichies** (Forge Board) — les cartes affichent le vrai titre, la date de dernière activité, les tags et un badge de livraison (release/commit), lus depuis le `metadata.json` des stories en **un seul appel groupé** (GraphQL GitHub, nombre d'appels indépendant du nombre de stories). Le drawer expose le changelog consolidé.
- **Filtre par tag et tri par activité** (Forge Board) — barre d'outils client-side pour isoler un thème à travers le pipeline et réordonner les cartes par date de mise à jour, sans round-trip.

### Changed
- Le lecteur de dépôt (`RepositoryReaderInterface`) expose `readStoryMetadata()` (lecture groupée du metadata) ; l'implémentation GitHub devient bi-protocole REST + GraphQL. Une story sans `metadata.json`, ou avec un fichier invalide, dégrade gracieusement vers le slug humanisé — aucune régression. `StoryStageMapper` ignore `metadata.json` : la colonne reste déduite des seuls `.md`.

## [4.3.0] - 2026-07-05

### Added
- **Kanban d'un projet forge** (Forge Board) — à l'ouverture d'un projet, ses stories sont scannées en direct dans le dépôt et projetées en tableau lecture seule à quatre colonnes ordonnées par étape du pipeline, triées par numéro décroissant, avec un bandeau « À vérifier ». Un drawer permet de consulter les documents markdown de chaque story (pitch, plan…), rendus sanitizés. Aucun état persisté : le tableau est recalculé à chaque affichage.
- **Déduction de l'étape d'une story depuis ses fichiers** (Forge Board) — l'étape de chaque story (pitch → plan → livraison → vérification) est déduite automatiquement des fichiers présents dans son dossier `docs/story/`, sans aucune saisie manuelle.
- **Vérification d'accès et d'éligibilité d'un dépôt** (Forge Board) — à la déclaration ou l'édition d'un projet, l'application teste l'accès GitHub (token valide, dépôt atteignable) et confirme que le dépôt suit le workflow forge avant de l'accepter.

## [4.2.0] - 2026-07-04

### Added
- **Gestion des projets forge** (Forge Board) — déclarer un dépôt à suivre (provider GitHub/GitLab, URL, token de lecture chiffré au repos), consulter la liste, ouvrir un projet, éditer l'URL / renouveler le token et retirer un projet derrière confirmation. Le token n'est jamais réaffiché ni renvoyé au navigateur. Liste en Live Component (suppression sans rechargement) et sélecteur de provider aux couleurs de marque.
- **Connexion locale mono-utilisateur** (Forge Board) — l'application est protégée derrière une authentification : formulaire de login, option « rester connecté », déconnexion.
- **Direction artistique « Nova · Midnight »** (Forge Board) — design system sombre de référence (tokens de thème, kit de composants Flowbite remappés) pilotant toutes les interfaces.

## [4.1.0] - 2026-07-04

### Added
- **Forge Board** — application Symfony 8 (kanban de suivi des stories du workflow forge) instanciée à la racine du dépôt. Le repo héberge désormais deux sujets : la marketplace `forge` (`plugins/`) et l'app Forge Board (racine). Stack : Symfony 8 / PHP 8.5, Doctrine + SQLite, Symfony UX (Live Component, Turbo, Stimulus), Tailwind 4 + Flowbite 4, PHPUnit 13 + Playwright. Docs : `docs/vision.md`, `docs/stack.md`, `docs/adr/0001`.

### Changed
- Levée du doublon documentaire `documentation/` ↔ `docs/` : l'inventaire des skills passe de `documentation/forge.md` à `plugins/forge/SKILLS.md` (au plus près du plugin), le banner à `.github/banner.png`. README réorganisé (skills en tête, puis section Forge Board), `CLAUDE.md` scindé en deux parties (marketplace / app Symfony).

## [4.0.0] - 2026-07-04

### Changed
- **BREAKING** — Renommage du plugin `workflow` → `forge` pour aligner le nom du plugin sur celui de la marketplace. Le **préfixe d'invocation des skills change** : `/workflow:help` → `/forge:help`, `/workflow:feature-implem` → `/forge:feature-implem`, etc. (tous les skills). Le **namespace des agents** change de même : `workflow:autopilot` → `forge:autopilot`, `workflow:report-and-sync` → `forge:report-and-sync`. Le dossier du plugin passe de `plugins/workflow/` à `plugins/forge/` et la `source` du `marketplace.json` suit. Propagé à l'ensemble des `SKILL.md`, agents, templates de référence, `documentation/forge.md` (ex-`workflow.md`), README et CLAUDE.md. **Action requise** : après `/plugin marketplace update forge`, réinstaller avec `/plugin install forge@forge` puis utiliser les commandes préfixées `/forge:` — les anciennes `/workflow:` n'existent plus.

## [3.3.3] - 2026-06-29

### Changed
- `estimate` : le **barème de marge d'incertitude** descend de **+15 / +30 / +50 %** à **+10 / +20 / +35 %** (faible / moyenne / élevée). Dans le prolongement du fix v3.3.2 (base = médiane réaliste), ça allège encore le haut de fourchette : une story « moyenne » passe d'un total `base × 1,30` à `base × 1,20`, sur une base déjà dégonflée. Mis à jour dans `references/method.md` §4 et `references/template.md`.

## [3.3.2] - 2026-06-29

### Changed
- `estimate` : correction d'un **biais systématique de sur-chiffrage** (~30 % trop haut, constaté sur des stories réelles). La cause : le skill gonflait les **durées de base** par réflexe défensif *puis* ajoutait la marge d'incertitude par-dessus — l'aléa était donc compté deux fois. Introduction du principe directeur **« ne jamais compter l'incertitude deux fois »** : la durée de base de chaque phase est désormais la **médiane réaliste** (le temps le plus probable si le déroulé est normal), et l'aléa est porté **uniquement par la marge**. Concrètement : nouveau principe en tête de `references/method.md` §4, nouveau piège « Doubler le matelas » + **test du miroir** en §5, somme des phases recadrée en « médiane, pas borne haute » en §6 ; dans `SKILL.md`, la règle d'or distingue **périmètre** (« tout compris » = compter chaque phase) et **magnitude** (chiffrer au plus probable, sans coussin), la règle #5 passe de « être lucide, pas optimiste » à **« viser juste, ni optimiste ni défensif »** (sur-estimer coûte aussi ; dans le doute, prendre la valeur basse), et la Phase 4 demande de **recalibrer toute la décomposition sur le réalisé passé de l'utilisateur** dès qu'il le donne, au lieu de tenir des chiffres hauts rognés ligne à ligne ; `references/template.md` aligné (bloc-guide + synthèse). Les facteurs d'accélération IA et le barème de marge sont inchangés : corriger la base dégonfle mécaniquement les deux colonnes (réf. et avec IA).

## [3.3.1] - 2026-06-29

### Changed
- `estimate` : adaptation au **workflow solo**. Les phases **Intégration** et **Coordination & échanges** sont retirées de la décomposition (un développeur seul ne suit ni le merge multi-contributeurs ni les réunions/recette comme postes facturables distincts), et **Release & déploiement** devient un **forfait fixe de 30 min** (0,5 h) — opération routinière de durée constante, qu'on ne ré-estime pas et que l'IA n'accélère pas (identique dans les deux colonnes). La décomposition « tout compris » passe ainsi à six phases : cadrage, implémentation, tests, review, documentation, release. Propagé à `SKILL.md`, `references/method.md` (table des phases + note contexte solo, barème d'accélération IA, pièges du sous-chiffrage) et `references/template.md`, ainsi qu'aux descriptions (`/forge:help`, `documentation/workflow.md`, README, `plugin.json`).

## [3.3.0] - 2026-06-29

### Added
- Skill `estimate` (transversal **optionnel**, applicable à n'importe quelle story — feature `f-`, refacto `r-`, évolution technique `t-`) : chiffre le temps **« tout compris »** d'une story à facturer, pas seulement le code. Compte les huit phases que les devs sous-estiment systématiquement (cadrage, implémentation, tests, review & corrections, intégration, documentation de clôture, release & déploiement, coordination & échanges) plus une **marge d'incertitude** assumée (barème +15 / +30 / +50 % selon le flou réel). Entrée **flexible** : lit `brief.md`, `pitch.md` et/ou `plan.md` selon ce qui existe dans le dossier de story — plus la matière est riche, plus l'estimation est fiable (brief seul → fourchette large à reconfirmer ; plan détaillé → estimation affinée par les fichiers/migrations/tests listés). Chaque chiffre est **justifié par un signal** lu dans les artifacts ou le code, et calé sur le **vécu de l'utilisateur** (point de comparaison demandé — la vélocité réelle n'est pas dans le code). Spécificités par track prises en compte (tests de caractérisation amont en refacto, baseline/kill switch en tech, phases `déjà fait`/`reste` quand le pitch ou le plan existent déjà). Produit `docs/story/NNN-<f|r|t>-<slug>/estimate.md` **en heures** (facturation horaire), sans jamais convertir en montant — la conversion par le taux horaire reste à la charge de l'utilisateur. **Double chiffrage** : chaque phase est estimée dans deux colonnes — temps de référence (réalisation classique, à la main) et temps réel avec un **assistant IA** (type Claude Code) — via un facteur d'accélération **par phase** (fort sur implem/tests/doc, nul sur les phases humaines incompressibles comme la coordination et la recette client). L'écart entre les deux totaux éclaire la marge. Méthode complète (phases par track, accélération IA, signaux de complexité, barème de marge, pièges du sous-chiffrage) dans `references/method.md`. Câblé au sommaire `/forge:help`, à `documentation/workflow.md` et au README.

## [3.2.1] - 2026-06-23

### Changed
- `feature-interview` : le `brief.md` produit est désormais explicitement **100% fonctionnel**. La reconnaissance du code reste (elle informe les questions et la compréhension du produit), mais toute trouvaille technique est traduite en capacité vécue par l'utilisateur avant d'entrer dans le brief — plus aucun nom d'entité, de service, de fichier, de framework ni de stack. Ajout d'une « règle d'or » au `SKILL.md` (règle de traduction technique→fonctionnel + exemples), recadrage de la Phase 1 (« comprendre le produit » plutôt que « documenter la technique »), et remplacement de la section « Reconnaissance du code existant » du template par « Ce que le produit fait déjà » (capacités vues par l'utilisateur). Conséquence : le stack n'est plus transporté par le brief — l'optimisation de réutilisation par `/feature-pitch` introduite en 3.2.0 est retirée, le pitch re-détecte le stack lui-même.

## [3.2.0] - 2026-06-23

### Added
- Skill `feature-interview` (amont **optionnel** du track feature) : interview de découverte pour les besoins trop flous pour être pitchés directement — exactement les cas que `/feature-pitch` refuse aujourd'hui en Phase 0 (« améliorer les commandes », « il manque un truc côté relances »). Posture inverse du pitch : bienveillante, sans jargon, ne refuse jamais le vague (c'est la matière de départ). Déroule une interview guidée (exemple récent concret, 5 pourquoi, baguette magique, contraste, reformulation-miroir — détaillée dans `references/techniques.md`) ancrée sur une **reconnaissance ciblée du code existant** (détection stack + grep/glob autour du vocabulaire métier) pour éviter de réinventer une brique native. Produit `docs/story/NNN-f-<slug>/brief.md` (besoin en une phrase, irritant, qui, résultat attendu, reconnaissance code, hors-sujet entrevu, zones de flou). Le brief alimente `/feature-pitch`, qui le détecte et le reprend comme pitch initial riche (sautant son refus de Phase 0) en écrivant `pitch.md` dans le même dossier `NNN-f-<slug>/`. Compteur global partagé avec features/refactos/évolutions techniques. Câblage propagé à `/feature-pitch` (détection du brief amont), au sommaire `/forge:help` (diagramme du track feature + tableau), à `documentation/workflow.md` et au README.

### Fixed
- README : le diagramme de flux des tracks portait encore les anciens noms d'exécution (`feature`, `refactor`, `tech`), oubliés lors du renommage `-implem` de la v3.0.0 alors que le tableau juste en dessous était déjà à jour. Corrigés en `feature-implem` / `refactor-implem` / `tech-implem`.

## [3.1.0] - 2026-06-19

### Fixed
- Les subagents des agents `autopilot` et `report-and-sync` ne pouvaient pas écrire (`Write`/`Edit`) lorsque la session de l'utilisateur était en mode de permission `plan` (ou `default` sans règle d'autorisation préalable) : un subagent ne peut pas afficher de prompt de permission interactif, ses écritures étaient donc refusées silencieusement. Ajout de `permissionMode: acceptEdits` au frontmatter des deux agents. `autopilot` propage ce mode aux subagents `general-purpose` qu'il délègue (l'`acceptEdits` du parent prime), et `report-and-sync` écrit directement `report.md` / la doc d'intention. Le fix voyage avec le plugin : aucun réglage manuel requis côté utilisateur. Limitation : les skills d'implémentation invoquées en direct (`feature-implem`, etc.) s'exécutent dans la session principale et restent soumises au mode de permission de l'utilisateur.

## [3.0.1] - 2026-06-14

### Fixed
- Correction des références résiduelles aux anciens noms de tracks (`/feature`, `/refactor`, `/tech`) oubliées lors du renommage `-implem` de la v3.0.0 : les `SKILL.md` des skills de cadrage `feature-plan`, `refactor-plan` et `tech-plan` (mentions « il ne code pas », « prochaine étape », verrou caractérisation) pointaient encore vers les anciennes invocations, ainsi que `adr`, `stack` et la référence `references/stacks/symfony.md`. Toutes les invocations terminales pointent désormais vers `/feature-implem`, `/refactor-implem` et `/tech-implem`.

## [3.0.0] - 2026-06-03

### Changed
- **BREAKING** — Renommage des trois skills d'exécution terminaux pour rétablir la symétrie verbale avec les skills de cadrage (`*-plan`) : `feature` → `feature-implem`, `refactor` → `refactor-implem`, `tech` → `tech-implem`. Les invocations changent en conséquence : `/forge:feature` → `/forge:feature-implem`, `/forge:refactor` → `/forge:refactor-implem`, `/forge:tech` → `/forge:tech-implem`. Mise à jour propagée aux agents (`autopilot`, `report-and-sync`), au sommaire `/forge:help` (diagrammes ASCII redessinés, tableaux de tracks), aux templates de cadrage, à `_detection.md`, aux skills `import-external` / `test-scenario` / `review`, au README et à `documentation/workflow.md`. **Action requise** : les utilisateurs qui invoquaient `/forge:feature`, `/forge:refactor` ou `/forge:tech` doivent utiliser les nouveaux noms suffixés `-implem`.

## [2.2.0] - 2026-05-31

### Added
- Skill `claude-md` : génère ou met à jour le `CLAUDE.md` à la racine d'un projet. Analyse le codebase (nature, stack, architecture, commandes, conventions) avec la discipline « preuve par fichier » du skill `stack` — aucune commande inventée, validation avant écriture — puis injecte les 4 principes comportementaux Karpathy (réflexion avant code, simplicité, changements chirurgicaux, objectif vérifiable), inspirés du repo `multica-ai/andrej-karpathy-skills`. Réutilise `docs/stack.md` et `docs/vision.md` s'ils existent (synthèse + renvoi plutôt que duplication). Modes Création / Mise à jour ; en Mise à jour, propose explicitement d'ajouter la couche comportementale si elle manque, sans l'imposer. Squelette de fichier et bloc de principes dans `references/`.

## [2.1.0] - 2026-05-28

### Added
- Skill `stack` (phase 0 technique) : détecte la stack complète d'un projet (langages, backend, frontend, données, ops, devops/CI) et produit `docs/stack.md`. Document vivant à 4 modes (Création, Enrichir, Éditer, Pivot) avec changelog, sur le modèle de `vision`/`product-backlog`. Chaque techno est prouvée par un fichier source ; les couches non détectables (hébergement, monitoring, secrets) sont comblées par questions ciblées ou marquées `_non renseigné_`. Câblé dans `_detection.md` : `feature`/`refactor`/`tech`/`review` lisent `docs/stack.md` en priorité, avec fallback sur la détection légère.

### Changed
- README réécrit en version concise et structurée par tables ; ajout des skills manquants au catalogue (`stack`, `autopilot`, `report-and-sync`) et du skill `stack` dans le sommaire `/help` (phase 0 technique).

### Fixed
- `plugin.json` : `homepage` et `repository` pointaient encore vers `gabrielmustiere/skills` au lieu du repo dédié `gabrielmustiere/forge`.

## [2.0.1] - 2026-05-28

### Fixed
- Références de fichiers bundlés (détection stack, templates de cadrage, mappings d'import) cassées une fois le plugin installé hors du repo source : résolution via `${CLAUDE_SKILL_DIR}` dans les skills, `${CLAUDE_PLUGIN_ROOT}` dans les agents, et pointeurs « même dossier » entre fichiers de référence.

## [2.0.0] - 2026-05-28

### Added
- Extraction du plugin `workflow` dans son repo dédié `gabrielmustiere/forge`, distribué via la marketplace `forge`. L'historique antérieur du plugin reste consultable dans `gabrielmustiere/skills`. Le plugin repart en `2.0.0` pour marquer le nouveau repo dédié.

[Unreleased]: https://github.com/gabrielmustiere/forge/compare/v4.7.0...HEAD
[4.7.0]: https://github.com/gabrielmustiere/forge/compare/v4.6.0...v4.7.0
[4.6.0]: https://github.com/gabrielmustiere/forge/compare/v4.5.0...v4.6.0
[4.5.0]: https://github.com/gabrielmustiere/forge/compare/v4.4.0...v4.5.0
[4.4.0]: https://github.com/gabrielmustiere/forge/compare/v4.3.0...v4.4.0
[4.3.0]: https://github.com/gabrielmustiere/forge/compare/v4.2.0...v4.3.0
[4.2.0]: https://github.com/gabrielmustiere/forge/compare/v4.1.0...v4.2.0
[4.1.0]: https://github.com/gabrielmustiere/forge/compare/v4.0.0...v4.1.0
[4.0.0]: https://github.com/gabrielmustiere/forge/compare/v3.3.3...v4.0.0
[3.3.3]: https://github.com/gabrielmustiere/forge/compare/v3.3.2...v3.3.3
[3.3.2]: https://github.com/gabrielmustiere/forge/compare/v3.3.1...v3.3.2
[3.3.1]: https://github.com/gabrielmustiere/forge/compare/v3.3.0...v3.3.1
[3.3.0]: https://github.com/gabrielmustiere/forge/compare/v3.2.1...v3.3.0
[3.2.1]: https://github.com/gabrielmustiere/forge/compare/v3.2.0...v3.2.1
[3.2.0]: https://github.com/gabrielmustiere/forge/compare/v3.1.0...v3.2.0
[3.1.0]: https://github.com/gabrielmustiere/forge/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/gabrielmustiere/forge/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/gabrielmustiere/forge/compare/v2.2.0...v3.0.0
[2.2.0]: https://github.com/gabrielmustiere/forge/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/gabrielmustiere/forge/compare/v2.0.1...v2.1.0
[2.0.1]: https://github.com/gabrielmustiere/forge/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/gabrielmustiere/forge/releases/tag/v2.0.0
