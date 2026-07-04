# SemVer — règles de bump

Format strict : `MAJOR.MINOR.PATCH[-prerelease][+build]`. Pas de `v1.2`, pas de `1.2.3.4`.

## Règles de bump

| Bump    | Quand                                                              | Exemple        |
|---------|--------------------------------------------------------------------|----------------|
| `MAJOR` | Au moins un `BREAKING CHANGE:` dans le footer ou un type avec `!`  | 1.4.2 → 2.0.0  |
| `MINOR` | Au moins un `feat` (et aucun breaking)                             | 1.4.2 → 1.5.0  |
| `PATCH` | Uniquement `fix`, `perf`, `refactor`, `docs`, `chore`, `style`     | 1.4.2 → 1.4.3  |

## Pré-release

Suffixe `-alpha.N`, `-beta.N`, `-rc.N` (ex: `v1.5.0-rc.1`). Utiliser `--pre <suffix>` pour les générer (ex: `/release minor --pre rc.1`).

## Avant 1.0.0

Projet considéré instable. Toute évolution peut casser. Convention : `0.MINOR.PATCH` où `MINOR` bump pour les features **et** les breaking changes.

## Préfixe `v`

Toujours préfixé `v` : `v1.2.3`, pas `1.2.3`. Convention quasi universelle, et `gh release` la respecte.

## Pièges

- **Bump trop bas malgré un breaking** → un seul `BREAKING CHANGE:` impose `MAJOR` (ou `MINOR` avant 1.0.0). Si l'utilisateur insiste pour un patch, alerter mais respecter — c'est sa décision finale.
- **Re-tagger une version** → refuser. Créer un nouveau patch avec une note "corrige la release vX.Y.Z".
