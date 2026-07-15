# Catalogue de zones

Amorce pour la Phase 1 : les découpages qui reviennent, par famille de projet. **Le dépôt tranche,
pas ce catalogue** — vérifie que la zone existe (`Glob`) et qu'elle porte une vraie convention avant
d'en faire une règle.

## Qu'est-ce qu'une bonne zone

Une zone est un ensemble de fichiers qui **partagent des décisions d'écriture** que le reste du dépôt
ne partage pas. Le test : *une convention vraie ici serait-elle fausse ou hors sujet ailleurs ?* Si
elle est vraie partout, ce n'est pas une zone — c'est le `CLAUDE.md`.

Trois lignes de découpe, par ordre de rendement :

1. **Par sujet** — quand un dépôt porte plusieurs projets étrangers l'un à l'autre (une app et un
   package distribué, un back et un front, plusieurs services d'un monorepo). C'est le découpage qui
   rapporte le plus : chaque sujet cesse de payer le contexte des autres.
2. **Par couche** — la découpe classique à l'intérieur d'un projet (données, présentation, tests,
   infra). C'est le cas courant.
3. **Par nature de fichier** — les fichiers qui ont leurs propres règles où qu'ils soient
   (migrations, fixtures, workflows CI).

## PHP / Symfony / Sylius

| Zone | Globs | Ce qu'on y grave typiquement |
| --- | --- | --- |
| Doctrine | `src/Entity/**/*.php`, `src/Repository/**/*.php` | QueryBuilder confiné au repository, mapping, nommage BDD |
| Migrations | `migrations/**/*.php` | on ne modifie jamais une migration commitée, réversibilité du `down()` |
| Contrôleurs | `src/Controller/**/*.php` | pas de logique métier, pas de QueryBuilder, mappage d'attributs |
| Templates | `templates/**/*.twig` | thème de form, sélecteurs de test, échappement |
| Tests | `tests/**/*.php` | `createStub()` vs `createMock()`, nommage, fixtures |
| E2E | `e2e/**/*.ts`, `tests/e2e/**` | sélecteurs `data-test`, attentes explicites |
| Fixtures | `fixtures/**/*.php` | emplacement, PSR-4, idempotence |

## JavaScript / TypeScript

| Zone | Globs | Ce qu'on y grave typiquement |
| --- | --- | --- |
| Composants | `src/components/**/*.{tsx,jsx,vue,svelte}` | conventions de props, état, accessibilité |
| Hooks / composables | `src/hooks/**/*.ts`, `src/composables/**/*.ts` | règles d'appel, nommage |
| API / serveur | `src/api/**/*.ts`, `app/api/**/route.ts` | validation d'entrée, format d'erreur |
| Styles | `**/*.css`, `assets/**` | design tokens, ordre des classes utilitaires |
| Tests | `**/*.{test,spec}.{ts,tsx}` | organisation, doublures, assertions |

## Python

| Zone | Globs | Ce qu'on y grave typiquement |
| --- | --- | --- |
| Modèles | `**/models/**/*.py` | ORM, migrations, typage |
| Vues / API | `**/views/**/*.py`, `**/routers/**/*.py` | validation, sérialisation, erreurs |
| Tests | `tests/**/*.py` | fixtures pytest, paramétrage, doublures |

## Transverses (toutes stacks)

| Zone | Globs | Ce qu'on y grave typiquement |
| --- | --- | --- |
| CI | `.github/workflows/**/*.yml` | jobs obligatoires, cache, secrets |
| Infra | `**/*.tf`, `docker-compose*.yml`, `Dockerfile*` | conventions de nommage, pas de secret en dur |
| Docs | `docs/**/*.md` | registre, langue, format |
| Plugins / skills Claude Code | `**/skills/**/SKILL.md`, `.claude-plugin/**` | frontmatter, nommage, structure |

## Ce qui n'est pas une zone

Les pièges les plus fréquents, tous recalés par le test du `paths` :

- **L'outillage** — « toujours le binaire `symfony` », « passer par le Makefile », « `pnpm` pas
  `npm` ». Lancer une commande n'est pas lire un fichier. → `CLAUDE.md`, ou un hook si on veut la
  garantie.
- **Le comportement de l'agent** — « demander avant de refactorer », « ne pas sur-concevoir ». Vrai
  partout, donc aucun glob honnête. → `CLAUDE.md` (couche Karpathy de `/claude-md`).
- **L'architecture globale** — « Request → Controller → Service → Repository ». Chaque couche a sa
  règle scopée ; le schéma d'ensemble, lui, est vrai partout. → `CLAUDE.md`.
- **La stack** — « on est en PHP 8.5 avec Symfony 8 ». Un constat, pas une prescription. →
  `docs/stack.md` via `/stack`.
- **`vendor/`, `node_modules/`, `var/`** — on n'y écrit pas, une règle n'y sert à rien.
