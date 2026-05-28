# Mapping GSD (get-shit-done) → workflow

GSD (https://github.com/gsd-build/get-shit-done) est un système de meta-prompting et spec-driven development pour Claude Code. Il génère des **fichiers plats** dans `.plans/` plutôt que des dossiers structurés.

## Indices de détection

- Présence d'un dossier `.plans/` à la racine, contenant des fichiers `.md` au format `<id>-<slug>.md`.
- `<id>` peut être un issue GitHub (ex: `1755-install-audit-fix.md`), un timestamp, ou un compteur séquentiel.
- Souvent accompagné d'un dossier `.gsd/` ou `get-shit-done/` (infra GSD elle-même, à ignorer).
- Possiblement aussi `docs/spec.md`, `docs/requirements.md` (templates GSD optionnels).

## Structure source

```
.plans/
├── 1755-install-audit-fix.md
├── 1762-add-redis-cache.md
├── 1789-extract-pricing.md
└── 20251201-checkout-flow.md      # variante timestamp
```

Chaque fichier est **un document complet** (genre PRD condensé) : overview, changes, files affected, tests, etc. Format souple — pas de sections obligatoires.

## Mapping fichiers

GSD = 1 fichier source → 1 dossier workflow contenant 1 fichier (`pitch.md` ou `plan.md`).

| Source                              | Destination                                       | Contenu cible |
|-------------------------------------|---------------------------------------------------|---------------|
| `.plans/<id>-<slug>.md`             | `docs/story/<NNN>-<tag>-<slug>/pitch.md`          | Si tag = `f` |
| `.plans/<id>-<slug>.md`             | `docs/story/<NNN>-<tag>-<slug>/plan.md`           | Si tag = `r` ou `t` |

Le contenu est **copié intégralement** sans transformation. On ajoute juste un header en tête pour la traçabilité :

```markdown
> Importé depuis GSD : `.plans/1755-install-audit-fix.md` (id source : 1755).

# (titre original conservé)

(contenu original conservé)
```

Le fichier source d'origine va dans `_archive/gsd/<NNN>-<slug>.md` (préserve la trace de l'id GSD original).

## Numérotation

GSD n'a **pas de compteur global cohérent** (les ids sont souvent des numéros d'issues GitHub, donc non séquentiels). Allouer séquentiellement :

1. Trier les fichiers `.plans/*.md` par **date de modification** (`ls -1t .plans/*.md | tac` pour ordre chronologique inverse, ou `git log --diff-filter=A --format=%ad --date=iso -- <fichier>` pour la date de création réelle).
2. Allouer `001`, `002`, `003`, … dans cet ordre chronologique de création.
3. L'id source GSD (`1755`) est **conservé dans le header** du fichier importé, pas dans le nom du dossier.

Si le repo a déjà des stories workflow : reprendre à `max(numéros existants) + 1`.

## Heuristique de tagging

GSD ne distingue pas feature/refacto/tech. Heuristique sur le **titre H1** et les premières lignes du fichier :

- Mot-clé `fix`, `audit`, `cleanup`, `refactor`, `extract`, `rename`, `dead code` dans le titre → `r`.
- Mot-clé `cache`, `retry`, `index`, `latency`, `health`, `observability`, `security`, `migration`, `bump`, `CVE` dans le titre → `t`.
- Sinon (titre style "Add X", "Implement Y", "Support Z") → `f`.

Demander confirmation à l'utilisateur, lui afficher le H1 de chaque fichier pour qu'il décide vite.

## Pas de plan.md séparé ?

Le workflow attend `pitch.md` + `plan.md` pour une feature complète. GSD met **tout dans un seul fichier**. Deux choix :

1. **Tout va dans `pitch.md`** : c'est un mix spec + plan. L'utilisateur pourra plus tard exécuter `/feature-plan` pour générer un `plan.md` propre à partir du contenu mixte. **Recommandé** car simple et préserve fidèlement l'origine.
2. **Découpe interactive** : le skill propose un découpage automatique (sections "Overview"/"Why" → `pitch.md`, sections "Changes"/"Implementation"/"Files" → `plan.md`). Plus chirurgical, mais le découpage automatique est faillible — réserver aux cas où l'utilisateur le demande explicitement.

Par défaut, **tout va dans `pitch.md`** (pour `f`) ou **tout dans `plan.md`** (pour `r`/`t`).

## Exemple complet

Avant :

```
.plans/
├── 1755-install-audit-fix.md          # bugfix critique → r ou t selon contenu
├── 1762-add-redis-cache.md            # ajout cache → t
├── 1789-checkout-express.md           # nouvelle feature → f
└── 20251201-extract-pricing.md        # refacto → r
```

Après (allocation séquentielle dans l'ordre chrono de création) :

```
docs/story/
├── 001-r-install-audit-fix/
│   └── plan.md            # ex 1755-install-audit-fix.md
├── 002-t-add-redis-cache/
│   └── plan.md
├── 003-f-checkout-express/
│   └── pitch.md
└── 004-r-extract-pricing/
    └── plan.md

_archive/gsd/
├── 1755-install-audit-fix.md
├── 1762-add-redis-cache.md
├── 1789-checkout-express.md
└── 20251201-extract-pricing.md
```

## Pièges spécifiques GSD

- **Ids très grands (issue numbers)** : ne **pas** essayer de coller `1755` dans le nom du dossier workflow (qui attend 3 chiffres). On renumerote séquentiellement, l'id d'origine va dans le header pour traçabilité.
- **Fichiers très courts** : certains plans GSD font 5 lignes. Le skill ne doit pas paniquer — copier tel quel, c'est valable. L'utilisateur peut enrichir plus tard.
- **`.gsd/` ou `get-shit-done/`** : c'est l'infra du framework, pas du contenu projet. **Ne jamais déplacer**. Ignorer pendant l'import.
- **Liens entre plans** : un fichier GSD peut référencer `[autre plan](./1762-add-redis-cache.md)`. Après import, ce chemin casse. Le skill **doit signaler** ces liens à l'utilisateur (via `grep` après import) et proposer de les réécrire (`docs/story/002-t-add-redis-cache/plan.md`).
- **CHANGELOG ou commits référençant l'id source** : préserver l'id dans le header du fichier importé permet à un `grep -rn 1755` de toujours retrouver le contenu, même après renumérotation.
