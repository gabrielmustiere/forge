![Forge](documentation/banner.png)

**Forge** est une marketplace de plugins Claude Code. Elle publie un plugin : `forge`, un pipeline de développement stack-agnostique qui pilote tout le cycle — de la vision projet jusqu'au commit — en étapes courtes, validées une à une.

- **Marketplace** : `forge`
- **Source** : `gabrielmustiere/forge`

> Les skills Symfony, Sylius et éditoriales vivent dans une marketplace séparée : [`gabrielmustiere/skills`](https://github.com/gabrielmustiere/skills).

## Installation

Dans une session Claude Code, sur n'importe quel projet :

```
/plugin marketplace add gabrielmustiere/forge
/plugin install forge@forge
/reload-plugins
```

Les skills sont namespacées par le nom du plugin : `/forge:help`, `/forge:feature-pitch`, etc.

Mettre à jour : `/plugin marketplace update forge` puis `/reload-plugins`.

## Principe

Chaque étape produit un artefact markdown (`pitch.md`, `plan.md`, `review.md`, `report.md`) qui alimente la suivante. **On ne passe jamais à l'étape d'après sans validation explicite** (`ok`, `go`, `validé`). Trois tracks symétriques selon la nature du changement, un même pipeline.

```
PHASE 0 (une fois, documents vivants)
  vision           → docs/vision.md            (problème, audience, North Star)
  product-backlog  → docs/product-backlog.md   (domaines, capacités, MVP/V2/V3)
  stack            → docs/stack.md             (langages, infra, CI — phase 0 technique)

TRACK selon le changement
  Feature (user-facing)        : (feature-interview) → feature-pitch → feature-plan → feature-implem
  Refacto (comportement figé)  : refactor-plan → refactor-implem
  Tech (perf/sécu/observabilité) : tech-plan → tech-implem

CLÔTURE (commune aux 3 tracks)
  review → commit → report → sync
```

Tout vit dans `docs/story/NNN-<f|r|t>-<slug>/` — compteur global, donc le tri lexicographique donne la timeline du projet. Exemple : `docs/story/042-f-checkout-express/`.

Perdu en cours de route ? `/forge:help` est le GPS du pipeline.

## Skills

### Phase 0 — Poser le décor (documents vivants, 4 modes : Création / Enrichir / Éditer / Pivot)

| Skill | Rôle |
| --- | --- |
| `/forge:vision` | Cadre la vision : problème, audience, valeur, North Star, principes, anti-objectifs → `docs/vision.md` |
| `/forge:product-backlog` | Traduit la vision en domaines, capacités, parcours et backlog priorisé MVP/V2/V3 → `docs/product-backlog.md` |
| `/forge:stack` | Cartographie la stack technique (langages, backend, frontend, données, ops, CI) → `docs/stack.md`. Chaque techno prouvée par un fichier source |

### Track feature — Valeur utilisateur

| Skill | Rôle |
| --- | --- |
| `/forge:feature-interview` | *(optionnel, amont)* Découvre un besoin flou par interview guidée, ancrée sur le code existant → `brief.md` (alimente `feature-pitch`) |
| `/forge:feature-pitch` | Cadre l'idée et challenge l'alignement (vision/backlog) → `pitch.md` |
| `/forge:feature-plan` | Plan technique : archi, données, contrats, migration, tests → `plan.md` |
| `/forge:feature-implem` | Implémentation guidée sous-tâche par sous-tâche, QA continue |

### Track refacto — Comportement figé, code restructuré

| Skill | Rôle |
| --- | --- |
| `/forge:refactor-plan` | Cadrage + tests de caractérisation à poser comme verrou → `plan.md` |
| `/forge:refactor-implem` | Exécution verrou-tests-d'abord, étapes incrémentales réversibles |

### Track tech — Perf, résilience, observabilité, sécu (non user-facing)

| Skill | Rôle |
| --- | --- |
| `/forge:tech-plan` | Cadrage avec métrique cible chiffrée + baseline + kill switch → `plan.md` |
| `/forge:tech-implem` | Exécution : baseline, kill switch, mesure après chaque étape |

### Clôture — Commune aux trois tracks

| Skill | Rôle |
| --- | --- |
| `/forge:review` | Code review du diff : sécu, qualité, conformité au plan, non-régression → `review.md` |
| `/forge:commit` | Message Conventional Commits en français (l'intention), commit + push |
| `/forge:report` | Compte rendu honnête : ce qui a été fait vs prévu, écarts, dettes → `report.md` |
| `/forge:sync` | Réaligne `pitch.md` / `plan.md` sur le code livré, avec changelog |

### Utilitaires (hors pipeline)

| Skill | Rôle |
| --- | --- |
| `/forge:help` | Sommaire du pipeline, tracks, skills et artifacts |
| `/forge:claude-md` | Génère ou met à jour le `CLAUDE.md` à la racine : analyse du codebase (prouvée par fichier) + principes comportementaux Karpathy. Réutilise `docs/stack.md` / `docs/vision.md` |
| `/forge:test-scenario` | Joue un scénario utilisateur en live via Playwright MCP |
| `/forge:adr` | Rédige un Architecture Decision Record MADR léger → `docs/adr/NNNN-slug.md` |
| `/forge:estimate` | Chiffre le temps « tout compris » d'une story à facturer (feature, refacto, tech) : cadrage, implem, tests, review, doc, release (forfait fixe 30 min) → `estimate.md` (en heures, marge incluse, deux colonnes réf./avec IA) |
| `/forge:doc-feature` | Cartographie une feature existante (legacy) → `docs/feature-map/NNN-slug/overview.md` |
| `/forge:migrate-legacy` | Migre les anciens formats workflow via `git mv` (historique préservé) |
| `/forge:import-external` | Importe une doc Spec Kit / BMAD-METHOD / GSD vers le format workflow |
| `/forge:release` | Tag SemVer annoté + `CHANGELOG.md` Keep a Changelog + release GitHub |

### Orchestrateurs (en contexte isolé)

| Skill | Rôle |
| --- | --- |
| `/forge:autopilot` | Pilote autonome bout-en-bout d'une story — délègue chaque sous-tâche à un subagent isolé, trace dans `.autopilot.json` (reprise possible), s'arrête aux stop-points stratégiques |
| `/forge:report-and-sync` | Enchaîne `report` puis `sync` en une passe, en contexte isolé |

## Track fast — Bugfix express (hors pipeline)

Pour les modifs qui cochent **toutes** ces cases : moins de 3 fichiers, pas de migration, pas de nouveau service/entité, pas d'impact transverse. On code, on lance la QA du stack, puis `/forge:review` (optionnel) et `/forge:commit`. Pas de pitch ni de plan pour un typo.

## Stack-aware

Le workflow détecte le stack (Symfony, Sylius…) via `composer.json` / `package.json` et charge les bonnes conventions de QA, sécu et perf au bon moment. Les conventions propres au projet (commandes QA exactes, credentials de test, branches…) vivent dans le `CLAUDE.md` à la racine.

## En savoir plus

- Inventaire complet et détaillé : [`documentation/forge.md`](documentation/forge.md)
- Sommaire interactif dans Claude Code : `/forge:help`

## Licence

Distribué sous licence [Apache 2.0](LICENSE). © 2026 Gabriel Mustiere.
