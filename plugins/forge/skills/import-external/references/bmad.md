# Mapping BMAD-METHOD → workflow

BMAD (https://github.com/bmad-code-org/BMAD-METHOD) est un framework "Breakthrough Method for Agile AI-Driven Development". Il découpe le projet en niveaux : PRD global, architecture, puis stories au format `<epic>.<story>`.

## Indices de détection

- Présence de `docs/prd.md` **et** `docs/architecture.md` (les deux artefacts BMAD canoniques).
- Dossier `docs/stories/` contenant des fichiers `<epic>.<story>.story.md` (ex: `1.1.story.md`, `2.3.story.md`).
- Variantes : `docs/stories/<epic>.<story>-<title>.md` (avec titre dans le nom).

## Structure d'un projet source

```
docs/
├── prd.md                      # Product Requirements Document (vision globale)
├── architecture.md             # Architecture technique globale
├── stories/
│   ├── 1.1.story.md            # Epic 1, Story 1
│   ├── 1.2.story.md            # Epic 1, Story 2
│   ├── 2.1.story.md            # Epic 2, Story 1
│   └── 2.2-checkout.story.md   # Variante avec titre
└── (parfois epics/<epic>.md séparés)
```

## Mapping fichiers

BMAD est plus délicat à mapper car le PRD et l'architecture sont **partagés** entre toutes les stories, alors que le workflow met `pitch.md` + `plan.md` **par story**. Deux options :

### Option A — Stories indépendantes (recommandée)

Pour **chaque story BMAD**, créer un dossier workflow avec :
- `pitch.md` = contenu de la story BMAD (qui contient déjà user story, acceptance criteria).
- `plan.md` = extrait de l'architecture pertinent pour cette story (à choisir interactivement avec l'utilisateur, ou copie intégrale du `architecture.md` global avec une note "à filtrer").

Le PRD et l'architecture globaux vont dans `_archive/bmad/` pour référence.

### Option B — Premier story = projet complet

Tous les artefacts BMAD (`prd.md`, `architecture.md`, toutes les stories) deviennent **une seule** story workflow géante :
- `docs/story/001-f-<projet>/pitch.md` = `prd.md` + concaténation des stories.
- `docs/story/001-f-<projet>/plan.md` = `architecture.md`.

À utiliser si le projet BMAD est petit ou si l'utilisateur veut tout regrouper.

**Demander à l'utilisateur** quelle option, par défaut suggérer A.

## Mapping fichiers détaillé (Option A)

| Source                            | Destination                                       | Note |
|-----------------------------------|---------------------------------------------------|------|
| `docs/stories/<epic>.<story>.story.md` | `docs/story/<NNN>-<tag>-<slug>/pitch.md`     | Une story BMAD = une story workflow. |
| `docs/prd.md`                     | `_archive/bmad/prd.md`                            | Consultable mais hors compteur global. Référencé en lien depuis chaque `pitch.md` importée. |
| `docs/architecture.md`            | `_archive/bmad/architecture.md` + copie ciblée    | L'utilisateur choisit pour chaque story s'il veut copier les sections architecture pertinentes dans son `plan.md`, ou juste linker. |
| `docs/epics/<epic>.md` (si existe)| `_archive/bmad/epics/<epic>.md`                   | Archive — l'epic est encodé dans le slug. |

## Numérotation

BMAD n'a **pas de compteur global**. Le format `<epic>.<story>` n'est pas séquentiel sur l'ensemble du projet. Allouer séquentiellement :

1. Lister toutes les stories sources triées par : (a) epic, puis (b) story (`1.1`, `1.2`, `1.3`, `2.1`, `2.2`, `3.1`).
2. Allouer `001`, `002`, `003`, … dans cet ordre.
3. Le numéro d'epic+story est **conservé dans le slug** pour traçabilité : `001-f-epic1-story1-user-login`, ou plus court `001-f-e1s1-user-login`.

Demander à l'utilisateur la convention de slug : `epic1-story1-<titre>`, `e1s1-<titre>`, ou `<titre>` seul (perte de la trace BMAD).

**Si le repo a déjà des stories workflow** : reprendre à `max(numéros existants) + 1`.

## Heuristique de tagging

Lire la story BMAD (front-matter ou H1) pour détecter :

- BMAD a un champ `Status: Done | In Progress | …` — peu utile pour le tag.
- Regarder les **Acceptance Criteria** : si elles décrivent un comportement user-facing → `f`. Si elles décrivent une métrique tech (latency < 100ms, taux d'erreur < 1%) → `t`. Si elles décrivent l'absence de changement comportemental + amélioration interne → `r`.

Toujours demander confirmation par story.

## Extraction du titre/slug

Le slug ne peut pas être uniquement `<epic>.<story>` (le `.` n'est pas kebab-case). Extraire le titre :

1. Si le filename contient déjà un titre : `1.2-checkout.story.md` → slug = `e1s2-checkout`.
2. Sinon, lire le H1 du fichier (`# Story 1.2: Express Checkout`) → slug = `e1s2-express-checkout` (kebab-case).

## Exemple complet

Avant :

```
docs/
├── prd.md
├── architecture.md
└── stories/
    ├── 1.1.story.md           # User login
    ├── 1.2.story.md           # User logout
    ├── 2.1.story.md           # Express checkout
    └── 3.1-cache.story.md     # Add Redis cache
```

Après (Option A, slugs `eXsY-<titre>`) :

```
docs/story/
├── 001-f-e1s1-user-login/
│   └── pitch.md
├── 002-f-e1s2-user-logout/
│   └── pitch.md
├── 003-f-e2s1-express-checkout/
│   └── pitch.md
└── 004-t-e3s1-add-redis-cache/
    └── pitch.md

_archive/bmad/
├── prd.md
├── architecture.md
└── epics/        # si présent dans la source
```

Note : pas de `plan.md` créé automatiquement — l'utilisateur peut le générer plus tard via `/feature-plan` en utilisant `_archive/bmad/architecture.md` comme source.

## Pièges spécifiques BMAD

- **PRD global non transposable** : le PRD BMAD est un document de vision (problème, marché, persona). Son contenu n'a pas vraiment d'équivalent dans le workflow (qui démarre directement au pitch d'une feature concrète). **Ne pas essayer de l'éclater dans les `pitch.md`** — laisser dans `_archive/bmad/prd.md` et linker.
- **Architecture globale réutilisée par story** : ne pas dupliquer le contenu de `architecture.md` dans chaque `plan.md`. Soit linker (`Voir _archive/bmad/architecture.md`), soit copier seulement la section pertinente pour cette story.
- **Stories en cours d'exécution (Status: In Progress)** : signaler à l'utilisateur que le format BMAD encode des statuts qui n'ont pas d'équivalent direct dans le workflow (le statut workflow est implicite via la présence de `report.md`). L'info est conservée dans le `pitch.md` importé.
