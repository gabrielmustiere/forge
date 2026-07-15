# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Nature du repo

Ce dépôt héberge **trois sujets distincts** qui cohabitent :

1. **La marketplace `forge`** — un catalogue de plugins Claude Code distribué via GitHub (`plugins/`, `.claude-plugin/`). Pas de build ni de runtime : du JSON et du Markdown consommés chez les utilisateurs qui font `/plugin marketplace add gabrielmustiere/forge`.
2. **L'application Forge Board** — une app Symfony 8 (à la **racine** du repo : `src/`, `config/`, `templates/`, `composer.json`…) qui projette les stories du workflow forge en kanban. C'est un vrai projet buildable/testable. Sa vision : `docs/vision.md`, sa stack : `docs/stack.md`.
3. **Le site public** (`site/`) — deux pages HTML statiques publiées sur [forge.mustiere.fr](https://forge.mustiere.fr) via GitHub Pages : la vitrine du plugin et sa documentation. Aucun build, aucune dépendance. Sa charte (fond sombre, accent cyan) est **distincte** de la DA du Board (« Nova · Midnight », violet iris) — ne pas les confondre.

Les trois vivent côte à côte : la racine est un projet Symfony **et** contient `plugins/` et `site/`. Ne pas confondre les trois quand on édite.

⚠️ Ne pas confondre `docs/` (documentation du workflow forge : `vision.md`, `stack.md`, `story/`…) et `site/docs/` (la page de documentation publiée). GitHub Pages ne peut pas servir depuis `docs/`, déjà occupé — d'où `site/`.

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

App Symfony 8 à la racine du repo. Kanban **lecture seule** qui scanne les `docs/story/` de repos forge (cf. `docs/vision.md`). DA : design system **« Nova · Midnight »** (voir `DESIGN.md`) — thème sombre dense inspiré de Linear, accent iris. Tokens dans `assets/styles/app.css` (bloc `@theme`), point d'entrée unique du re-thème.

## Principes de travail (app)

**Réfléchir avant de coder** : énoncer les hypothèses, exposer les tradeoffs, demander en cas d'ambiguïté plutôt que choisir en silence.
**Simplicité d'abord** : le minimum de code qui résout le problème, rien de spéculatif, aucune abstraction pour un usage unique.
**Modifications chirurgicales** : ne toucher que ce que la demande impose, respecter le style existant, ne pas refactorer ce qui n'est pas cassé.
**Piloté par l'objectif** : transformer chaque tâche en critère vérifiable (« corriger le bug » → « écrire un test qui le reproduit, puis le faire passer »).

## Stack (app)

- PHP 8.5+ (`declare(strict_types=1)` partout), SQLite (`var/data.db`), Symfony 8.0, Symfony Messenger (Doctrine)
- Frontend : Tailwind CSS 4, Stimulus, Symfony UX (Live Components, Turbo, Icons), Flowbite 4
- Tests : PHPUnit 13 + Playwright (E2E)
- Qualité : PHPStan level 10 + PHP-CS-Fixer
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
make ci                                   # Lint + tests unitaires
```

La QA de l'app tourne **en local** : la seule CI du dépôt (`.github/workflows/pages.yml`) vérifie et déploie le site statique, elle ne lance ni PHPUnit ni Playwright.

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

---

# Partie 3 — Site public (`site/`)

Deux pages HTML statiques publiées sur [forge.mustiere.fr](https://forge.mustiere.fr) : `site/index.html` (vitrine) et `site/docs/index.html` (documentation du plugin). Zéro build, zéro dépendance externe hors Google Fonts.

```
site/
  index.html          ← vitrine
  docs/index.html     ← documentation développeur
  assets/forge.css    ← charte partagée par les deux pages (tokens en :root)
  assets/forge.js     ← burger + sommaire + scrollspy, partagés
  assets/og.png       ← carte de partage social
  llms.txt            ← indexation LLM
  CNAME               ← forge.mustiere.fr
```

## Règles (site)

- **CSS et JS sont partagés** (`assets/forge.*`) — ne pas réintroduire de style ou de script inline dans une seule page : les deux pages divergeraient.
- **Charte** : fond `#0a0e12`, accent cyan `#3fd6f2`, Space Grotesk + JetBrains Mono. Les tokens font foi (`:root` dans `forge.css`). **Rien à voir** avec la DA du Board (Nova · Midnight, violet).
- **Mobile-first** : styles de base pour mobile, `@media (min-width: 861px)` pour le desktop (+ `1024px` pour le layout de la doc).
- **Contraste** : tout texte doit tenir 4.5:1 sur `--surface`. C'est pourquoi `--text-dim` vaut `#7d8f9c` et non le `#5a6b78` d'origine (3.4:1, insuffisant).
- **Version en dur** : le site affiche la version du plugin à plusieurs endroits. `plugins/forge/.claude-plugin/plugin.json` est la **source de vérité** ; `tools/check-site-version.py` vérifie la cohérence et **le déploiement échoue** en cas de dérive. Après un `/forge:release`, mettre le site à jour.
- **Images** : `assets/og.png` et `.github/banner.png` se régénèrent depuis `tools/og-source.html` et `tools/banner-source.html` (procédure en commentaire dans chaque fichier). Ne pas les éditer à la main.

## Déploiement (site)

`.github/workflows/pages.yml` est la **seule CI du dépôt** : elle vérifie la version puis déploie sur GitHub Pages. Elle se déclenche sur `site/**`, `tools/check-site-version.py`, le workflow lui-même et `plugin.json` (pour attraper une dérive de version après une release).
