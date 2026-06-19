# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/gabrielmustiere/forge/compare/v3.1.0...HEAD
[3.1.0]: https://github.com/gabrielmustiere/forge/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/gabrielmustiere/forge/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/gabrielmustiere/forge/compare/v2.2.0...v3.0.0
[2.2.0]: https://github.com/gabrielmustiere/forge/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/gabrielmustiere/forge/compare/v2.0.1...v2.1.0
[2.0.1]: https://github.com/gabrielmustiere/forge/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/gabrielmustiere/forge/releases/tag/v2.0.0
