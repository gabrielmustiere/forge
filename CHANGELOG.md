# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.3.1] - 2026-06-29

### Changed
- `estimate` : adaptation au **workflow solo**. Les phases **Intégration** et **Coordination & échanges** sont retirées de la décomposition (un développeur seul ne suit ni le merge multi-contributeurs ni les réunions/recette comme postes facturables distincts), et **Release & déploiement** devient un **forfait fixe de 30 min** (0,5 h) — opération routinière de durée constante, qu'on ne ré-estime pas et que l'IA n'accélère pas (identique dans les deux colonnes). La décomposition « tout compris » passe ainsi à six phases : cadrage, implémentation, tests, review, documentation, release. Propagé à `SKILL.md`, `references/method.md` (table des phases + note contexte solo, barème d'accélération IA, pièges du sous-chiffrage) et `references/template.md`, ainsi qu'aux descriptions (`/workflow:help`, `documentation/workflow.md`, README, `plugin.json`).

## [3.3.0] - 2026-06-29

### Added
- Skill `estimate` (transversal **optionnel**, applicable à n'importe quelle story — feature `f-`, refacto `r-`, évolution technique `t-`) : chiffre le temps **« tout compris »** d'une story à facturer, pas seulement le code. Compte les huit phases que les devs sous-estiment systématiquement (cadrage, implémentation, tests, review & corrections, intégration, documentation de clôture, release & déploiement, coordination & échanges) plus une **marge d'incertitude** assumée (barème +15 / +30 / +50 % selon le flou réel). Entrée **flexible** : lit `brief.md`, `pitch.md` et/ou `plan.md` selon ce qui existe dans le dossier de story — plus la matière est riche, plus l'estimation est fiable (brief seul → fourchette large à reconfirmer ; plan détaillé → estimation affinée par les fichiers/migrations/tests listés). Chaque chiffre est **justifié par un signal** lu dans les artifacts ou le code, et calé sur le **vécu de l'utilisateur** (point de comparaison demandé — la vélocité réelle n'est pas dans le code). Spécificités par track prises en compte (tests de caractérisation amont en refacto, baseline/kill switch en tech, phases `déjà fait`/`reste` quand le pitch ou le plan existent déjà). Produit `docs/story/NNN-<f|r|t>-<slug>/estimate.md` **en heures** (facturation horaire), sans jamais convertir en montant — la conversion par le taux horaire reste à la charge de l'utilisateur. **Double chiffrage** : chaque phase est estimée dans deux colonnes — temps de référence (réalisation classique, à la main) et temps réel avec un **assistant IA** (type Claude Code) — via un facteur d'accélération **par phase** (fort sur implem/tests/doc, nul sur les phases humaines incompressibles comme la coordination et la recette client). L'écart entre les deux totaux éclaire la marge. Méthode complète (phases par track, accélération IA, signaux de complexité, barème de marge, pièges du sous-chiffrage) dans `references/method.md`. Câblé au sommaire `/workflow:help`, à `documentation/workflow.md` et au README.

## [3.2.1] - 2026-06-23

### Changed
- `feature-interview` : le `brief.md` produit est désormais explicitement **100% fonctionnel**. La reconnaissance du code reste (elle informe les questions et la compréhension du produit), mais toute trouvaille technique est traduite en capacité vécue par l'utilisateur avant d'entrer dans le brief — plus aucun nom d'entité, de service, de fichier, de framework ni de stack. Ajout d'une « règle d'or » au `SKILL.md` (règle de traduction technique→fonctionnel + exemples), recadrage de la Phase 1 (« comprendre le produit » plutôt que « documenter la technique »), et remplacement de la section « Reconnaissance du code existant » du template par « Ce que le produit fait déjà » (capacités vues par l'utilisateur). Conséquence : le stack n'est plus transporté par le brief — l'optimisation de réutilisation par `/feature-pitch` introduite en 3.2.0 est retirée, le pitch re-détecte le stack lui-même.

## [3.2.0] - 2026-06-23

### Added
- Skill `feature-interview` (amont **optionnel** du track feature) : interview de découverte pour les besoins trop flous pour être pitchés directement — exactement les cas que `/feature-pitch` refuse aujourd'hui en Phase 0 (« améliorer les commandes », « il manque un truc côté relances »). Posture inverse du pitch : bienveillante, sans jargon, ne refuse jamais le vague (c'est la matière de départ). Déroule une interview guidée (exemple récent concret, 5 pourquoi, baguette magique, contraste, reformulation-miroir — détaillée dans `references/techniques.md`) ancrée sur une **reconnaissance ciblée du code existant** (détection stack + grep/glob autour du vocabulaire métier) pour éviter de réinventer une brique native. Produit `docs/story/NNN-f-<slug>/brief.md` (besoin en une phrase, irritant, qui, résultat attendu, reconnaissance code, hors-sujet entrevu, zones de flou). Le brief alimente `/feature-pitch`, qui le détecte et le reprend comme pitch initial riche (sautant son refus de Phase 0) en écrivant `pitch.md` dans le même dossier `NNN-f-<slug>/`. Compteur global partagé avec features/refactos/évolutions techniques. Câblage propagé à `/feature-pitch` (détection du brief amont), au sommaire `/workflow:help` (diagramme du track feature + tableau), à `documentation/workflow.md` et au README.

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
- **BREAKING** — Renommage des trois skills d'exécution terminaux pour rétablir la symétrie verbale avec les skills de cadrage (`*-plan`) : `feature` → `feature-implem`, `refactor` → `refactor-implem`, `tech` → `tech-implem`. Les invocations changent en conséquence : `/workflow:feature` → `/workflow:feature-implem`, `/workflow:refactor` → `/workflow:refactor-implem`, `/workflow:tech` → `/workflow:tech-implem`. Mise à jour propagée aux agents (`autopilot`, `report-and-sync`), au sommaire `/workflow:help` (diagrammes ASCII redessinés, tableaux de tracks), aux templates de cadrage, à `_detection.md`, aux skills `import-external` / `test-scenario` / `review`, au README et à `documentation/workflow.md`. **Action requise** : les utilisateurs qui invoquaient `/workflow:feature`, `/workflow:refactor` ou `/workflow:tech` doivent utiliser les nouveaux noms suffixés `-implem`.

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

[Unreleased]: https://github.com/gabrielmustiere/forge/compare/v3.3.1...HEAD
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
