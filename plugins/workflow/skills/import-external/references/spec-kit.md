# Mapping Spec Kit → workflow

Spec Kit (https://github.com/github/spec-kit) est un framework de Spec-Driven Development de GitHub. Il génère des dossiers `specs/<NNN>-<slug>/` contenant des fichiers Markdown structurés.

## Indices de détection

- Présence d'un dossier `.specify/` à la racine du repo (templates, scripts).
- Dossiers `specs/<NNN>-<slug>/` avec `<NNN>` séquentiel sur 3 chiffres (ex: `specs/003-checkout-express/`).
- Variante avec timestamp : `specs/20250101-143022-<slug>/` si le projet a utilisé `--use-timestamp`.

## Structure d'un dossier source

```
specs/003-checkout-express/
├── spec.md         # Feature Specification (user stories, acceptance criteria)
├── plan.md         # Implementation Plan (tech context, architecture, phases)
├── tasks.md        # Task breakdown (étapes d'implémentation détaillées)
├── checklist.md    # Optional - revue qualité
└── research.md     # Optional - notes de research
```

## Mapping fichiers

| Source           | Destination                            | Note |
|------------------|----------------------------------------|------|
| `spec.md`        | `pitch.md`                              | Renommer simplement, garder le contenu intégral. La structure « User Story / Acceptance Scenarios » de Spec Kit est compatible avec le `pitch.md` du workflow. |
| `plan.md`        | `plan.md`                               | Conserver le nom. Les sections « Technical Context », « Architecture », « Phases » deviennent les sections du plan technique. |
| `tasks.md`       | `_archive/spec-kit/<NNN>-<slug>/tasks.md` | Pas d'équivalent direct dans le workflow (qui exécute les tâches via `/feature`). On archive pour référence. |
| `checklist.md`   | `_archive/spec-kit/<NNN>-<slug>/checklist.md` | Idem — archive. |
| `research.md`    | `_archive/spec-kit/<NNN>-<slug>/research.md` | Idem. |

## Numérotation

Spec Kit numérote déjà séquentiellement (ou via timestamp). **Réutiliser les numéros** :
- `specs/001-x/` → `docs/story/001-<tag>-x/`
- `specs/008-y/` → `docs/story/008-<tag>-y/`

**Si timestamps** (ex: `20250101-143022-x`) : proposer la renumérotation séquentielle, sinon les noms de dossiers seront longs et le tri ne sera plus chronologique au sens du compteur global du workflow.

**Vérifier collisions** : si `docs/story/003-r-old-thing/` existe déjà avant l'import, alerter et demander à l'utilisateur de choisir (décaler les numéros importés ou renuméroter l'existant).

## Heuristique de tagging

Spec Kit ne distingue pas feature/refacto/tech. Heuristique sur le contenu de `spec.md` :

- Lire la section **Feature Specification** (titre principal) et les **User Stories** (sections P1, P2…).
- Si le titre/scenarios mentionnent du *refactoring*, *cleanup*, *technical debt*, *extract*, *modernize* sans value user-facing → `r`.
- Si le titre mentionne *cache*, *latency*, *retry*, *index*, *health*, *observability*, *security hardening* → `t`.
- Sinon → `f` (par défaut, le plus probable car Spec Kit cible explicitement les features).

Toujours **demander confirmation** à l'utilisateur, ne pas appliquer le tag aveuglément.

## Exemple complet

Avant :

```
specs/
├── 001-user-login/
│   ├── spec.md
│   ├── plan.md
│   └── tasks.md
└── 002-extract-auth-service/
    ├── spec.md
    └── plan.md
```

Après :

```
docs/story/
├── 001-f-user-login/
│   ├── pitch.md            # ex spec.md
│   └── plan.md             # ex plan.md (conservé)
└── 002-r-extract-auth-service/
    └── plan.md             # ex plan.md (refacto : pas de pitch)

_archive/spec-kit/
├── 001-user-login/
│   └── tasks.md
└── (002 n'avait pas de tasks.md, rien à archiver)
```

## Pièges spécifiques Spec Kit

- **Branches Git associées** : Spec Kit crée des branches au format `001-user-login`. Après import, les branches existantes restent valides (le mapping est juste sur les dossiers). Pas besoin de renommer les branches sauf si l'utilisateur le veut.
- **`.specify/` à la racine** : ce dossier (templates, scripts) est l'infra Spec Kit, pas du contenu projet. Ne pas le toucher pendant l'import. L'utilisateur peut le retirer après si Spec Kit n'est plus utilisé.
- **Liens internes dans les fichiers** : `spec.md` peut contenir `[plan](plan.md)` — après renommage en `pitch.md`/`plan.md`, les liens vers `plan` restent valides ; ceux vers `spec` doivent être mis à jour : `[spec](spec.md)` → `[pitch](pitch.md)`.
