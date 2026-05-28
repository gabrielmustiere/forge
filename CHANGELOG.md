# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/gabrielmustiere/forge/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/gabrielmustiere/forge/compare/v2.0.1...v2.1.0
[2.0.1]: https://github.com/gabrielmustiere/forge/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/gabrielmustiere/forge/releases/tag/v2.0.0
