# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Nature du repo

Ce dépôt héberge **deux sujets distincts** qui cohabitent :

1. **La marketplace `forge`** — un catalogue de plugins Claude Code distribué via GitHub (`plugins/`, `.claude-plugin/`). Pas de build ni de runtime : du JSON et du Markdown consommés chez les utilisateurs qui font `/plugin marketplace add gabrielmustiere/forge`.
2. **L'application Forge Board** — une app Symfony 8 (à la **racine** du repo : `src/`, `config/`, `templates/`, `composer.json`…) qui projette les stories du workflow forge en kanban. C'est un vrai projet buildable/testable. Sa vision : `docs/vision.md`, sa stack : `docs/stack.md`.

Les deux vivent côte à côte : la racine est un projet Symfony **et** contient `plugins/` (la marketplace). Ne pas confondre les deux quand on édite.

---

# Partie 1 — Marketplace `forge` (plugins Claude Code)

La marketplace ne publie qu'**un seul plugin** : `forge`, un pipeline de développement stack-agnostique. Les skills Symfony, Sylius et éditoriales vivent dans une marketplace séparée : `gabrielmustiere/skills`.

Source de vérité :
- `.claude-plugin/marketplace.json` → catalogue (un seul plugin : `forge`)
- `plugins/forge/.claude-plugin/plugin.json` → manifeste du plugin
- `plugins/forge/skills/<skill>/SKILL.md` → une skill (frontmatter YAML + instructions Markdown)
- `plugins/forge/SKILLS.md` → inventaire lisible (2 colonnes skill / rôle). À mettre à jour à chaque ajout/retrait/renommage de skill.

Références externes : [docs plugins](https://code.claude.com/docs/fr/plugins), [docs skills](https://code.claude.com/docs/fr/skills), [docs marketplaces](https://code.claude.com/docs/fr/plugin-marketplaces).

## Architecture du plugin

```
.claude-plugin/marketplace.json        ← catalogue (name: forge, un plugin forge)
plugins/forge/
  .claude-plugin/plugin.json           ← manifeste (name, description, version, author)
  SKILLS.md                            ← inventaire lisible skill / rôle
  skills/<skill-name>/SKILL.md         ← une skill, nom du dossier = nom de la skill
  references/stacks/                    ← règles framework (Symfony, Sylius) chargées par détection
  agents/                              ← subagents (autopilot, report-and-sync)
```

Règle structurelle critique : `skills/`, `commands/`, `agents/`, `hooks/` vont **à la racine du plugin**, jamais dans `.claude-plugin/`. Seul `plugin.json` habite `.claude-plugin/`.

Namespacing : les skills sont toujours invoquées en préfixant par le nom du plugin → `/forge:help`, pas `/help`. Le préfixe vient du champ `name` dans `plugin.json`.

Résolution des `source` dans `marketplace.json` : `metadata.pluginRoot: "./plugins"` permet d'écrire `"source": "./plugins/forge"`.

## Workflow d'édition du plugin

### Ajouter une skill au plugin forge
1. Créer `plugins/forge/skills/<nouveau-skill>/SKILL.md` avec frontmatter `name` + `description`
2. Bumper `version` dans `plugins/forge/.claude-plugin/plugin.json` (semver) **et** dans `.claude-plugin/marketplace.json`
3. Ajouter une ligne (skill / rôle) dans `plugins/forge/SKILLS.md`
4. Mettre à jour le `CHANGELOG.md`
5. `git push`

### Tester le plugin localement avant push
Depuis n'importe quel projet :
```
claude --plugin-dir /Users/gabriel/projets/forge/plugins/forge
```
Pendant la session : `/reload-plugins` après chaque modif.

### Installation côté utilisateur
```
/plugin marketplace add gabrielmustiere/forge
/plugin install forge@forge
/reload-plugins
```
Pull des maj : `/plugin marketplace update forge`.

## Conventions du plugin

- Kebab-case partout (noms de skills, noms de dossiers) — le `name` YAML du SKILL.md doit matcher le nom du dossier
- Français dans les `description`
- Semver dans `plugin.json` et `marketplace.json` (gardés alignés)
- Frontmatter SKILL.md minimal = `name` + `description`. Champs utiles : `disable-model-invocation`, `user_invocable`, `allowed-tools`, `paths`
- Substitutions dispo dans le contenu SKILL.md : `$ARGUMENTS`, `$0`, `$1`, `${CLAUDE_SKILL_DIR}`, `${CLAUDE_SESSION_ID}`

## Piège fréquent (plugin)

Une skill qui ne se déclenche pas automatiquement → le problème est presque toujours la `description` (trop vague ou sans les mots-clés que l'utilisateur dirait naturellement). Fix : rendre la description plus spécifique et inclure les phrases déclencheurs. Les descriptions > 250 caractères sont tronquées dans la liste de skills chargée en contexte.

---

# Partie 2 — Application Forge Board (Symfony)

App Symfony 8 à la racine du repo. Kanban **lecture seule** qui scanne les `docs/story/` de repos forge (cf. `docs/vision.md`). DA actuelle : design system « Paper » (voir `DESIGN.md`) — une DA plus moderne est un chantier à venir.

## Principes de travail (app)

**Réfléchir avant de coder** : énoncer les hypothèses, exposer les tradeoffs, demander en cas d'ambiguïté plutôt que choisir en silence.
**Simplicité d'abord** : le minimum de code qui résout le problème, rien de spéculatif, aucune abstraction pour un usage unique.
**Modifications chirurgicales** : ne toucher que ce que la demande impose, respecter le style existant, ne pas refactorer ce qui n'est pas cassé.
**Piloté par l'objectif** : transformer chaque tâche en critère vérifiable (« corriger le bug » → « écrire un test qui le reproduit, puis le faire passer »).

## Stack (app)

- PHP 8.5+ (`declare(strict_types=1)` partout), SQLite (`var/data.db`), Symfony 8.0, Symfony Messenger (Doctrine)
- Frontend : Tailwind CSS 4, Stimulus, Symfony UX (Live Components, Turbo, Icons), Flowbite 4
- Tests : PHPUnit 13 + Playwright (E2E)
- Qualité : PHPStan level 9 + PHP-CS-Fixer
- AI : serveur MCP `symfony-ai-mate` configuré dans `mate/` ; extensions maison dans `App\Mate\` (`mate/src/`)

## Commandes (app)

Toutes les commandes PHP passent par `symfony` CLI — jamais `php` directement.

```bash
make init                                 # Installation complète (deps + DB + fixtures)
make serve                                # Serveur Symfony (Mailpit : docker compose up -d)
make db-reset                             # Reset DB complet (drop + migrate + fixtures)
symfony console make:migration            # Après modif d'une entité
make phpunit                              # PHPUnit (Unit + Functional)
make playwright                           # Playwright (E2E)
make quality                              # CS-Fixer + PHPStan + build
make ci                                   # Reproduit la CI (lint + tests unitaires)
```

## Règles critiques (app)

- Fixtures dans `fixtures/` (PSR-4 : `DataFixtures\`) — **PAS** dans `src/DataFixtures/`
- Ne jamais modifier une migration commitée — en créer une nouvelle
- Ne jamais modifier `vendor/`
- Pas de `dump()`, `var_dump()`, `dd()` dans le code commité
- Toute modif de schéma = migration générée par `symfony console make:migration`
- PHPUnit 13 : `createStub()` sans attentes, `createMock()` avec `expects()`
- Playwright : sélecteurs `data-test="..."`, config dans `playwright.config.ts`
- Enums : backed string enums dans `src/Enum/Type/`
- Mailer : classes dédiées dans `src/Mailer/` avec `TemplatedEmail`

## Identifiants de test (app)

- `admin@example.com` / `password` (ROLE_USER)

## Architecture (app)

```
Request → Controller → Service/Manager → Repository → Entity → Response
```

**Interdit** : QueryBuilder hors repository, logique métier dans controller/entity/repository, `new Service()`, entity qui injecte un service.

## Développer le Board avec le workflow forge

Le Board se développe **avec le plugin forge lui-même** (dogfooding) : `/forge:vision`, `/forge:product-backlog`, `/forge:stack` (phase 0, déjà faits → `docs/`), puis les tracks feature/refacto/tech. Pour les recettes framework, s'appuyer sur les skills `symfony:*` de la marketplace `gabrielmustiere/skills` (controllers, doctrine, forms, events, messenger, validation…) — les préférer aux conventions ad hoc.
