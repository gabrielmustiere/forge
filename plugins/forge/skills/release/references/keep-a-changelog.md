# Format Keep a Changelog

`CHANGELOG.md` à la racine du repo. Structure de référence :

```markdown
# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.5.0] - 2026-04-27

### Added
- Description courte à l'impératif passé/présent

### Changed
- ...

### Fixed
- ...

### Removed
- ...

### Deprecated
- ...

### Security
- ...

[Unreleased]: https://github.com/owner/repo/compare/v1.5.0...HEAD
[1.5.0]: https://github.com/owner/repo/compare/v1.4.2...v1.5.0
[1.4.2]: https://github.com/owner/repo/releases/tag/v1.4.2
```

## Mapping Conventional Commits → sections Keep a Changelog

| Type commit                              | Section CHANGELOG |
|------------------------------------------|-------------------|
| `feat`                                   | Added             |
| `fix`                                    | Fixed             |
| `perf`, `refactor`                       | Changed           |
| `BREAKING CHANGE` ou `type!`             | Changed (+ noter "BREAKING:" en préfixe de la ligne) |
| `docs`, `chore`, `style`, `test`, `ci`   | Omis du CHANGELOG (sauf si l'utilisateur insiste) |

Suppressions explicites → `Removed`. Dépréciations annoncées → `Deprecated`. Failles corrigées → `Security`.

## Règles de rédaction

- **Une ligne = un changement utilisateur-perceptible**, à l'impératif présent en français.
- **Pas de hash de commit** dans le CHANGELOG — c'est de la doc humaine, pas un git log déguisé.
- **Regrouper** plusieurs commits qui touchent la même feature en une ligne lisible.
- **Ignorer** les commits triviaux (typos, fix CI, bump deps cosmétique) sauf s'ils sont visibles utilisateur.

## Ordre d'insertion

Toujours insérer les nouvelles entrées **en haut** (après `[Unreleased]`), pas en bas. Les humains lisent du plus récent au plus ancien.
