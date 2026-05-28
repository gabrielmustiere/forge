# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Nature du repo

Ce repo **n'est pas une application** : c'est une **marketplace de plugins Claude Code** (nom public : `forge`) distribuée via GitHub. Il n'y a ni build, ni test, ni runtime — juste du JSON et du Markdown consommés par Claude Code chez les utilisateurs qui ajoutent cette marketplace avec `/plugin marketplace add gabrielmustiere/forge`.

La marketplace ne publie qu'**un seul plugin** : `workflow`, un pipeline de développement stack-agnostique. Les skills Symfony, Sylius et éditoriales vivent dans une marketplace séparée : `gabrielmustiere/skills`.

Source de vérité :
- `.claude-plugin/marketplace.json` → catalogue (un seul plugin : `workflow`)
- `plugins/workflow/.claude-plugin/plugin.json` → manifeste du plugin
- `plugins/workflow/skills/<skill>/SKILL.md` → une skill (frontmatter YAML + instructions Markdown)
- `documentation/workflow.md` → inventaire lisible (2 colonnes skill / rôle). À mettre à jour à chaque ajout/retrait/renommage de skill.

Références externes : [docs plugins](https://code.claude.com/docs/fr/plugins), [docs skills](https://code.claude.com/docs/fr/skills), [docs marketplaces](https://code.claude.com/docs/fr/plugin-marketplaces).

## Architecture

```
.claude-plugin/marketplace.json        ← catalogue (name: forge, un plugin workflow)
plugins/workflow/
  .claude-plugin/plugin.json           ← manifeste (name, description, version, author)
  skills/<skill-name>/SKILL.md         ← une skill, nom du dossier = nom de la skill
  references/stacks/                    ← règles framework (Symfony, Sylius) chargées par détection
  agents/                              ← subagents (autopilot, report-and-sync)
```

Règle structurelle critique : `skills/`, `commands/`, `agents/`, `hooks/` vont **à la racine du plugin**, jamais dans `.claude-plugin/`. Seul `plugin.json` habite `.claude-plugin/`.

Namespacing : les skills sont toujours invoquées en préfixant par le nom du plugin → `/workflow:help`, pas `/help`. Le préfixe vient du champ `name` dans `plugin.json`.

Résolution des `source` dans `marketplace.json` : `metadata.pluginRoot: "./plugins"` permet d'écrire `"source": "./plugins/workflow"`.

## Workflow d'édition

### Ajouter une skill au plugin workflow
1. Créer `plugins/workflow/skills/<nouveau-skill>/SKILL.md` avec frontmatter `name` + `description`
2. Bumper `version` dans `plugins/workflow/.claude-plugin/plugin.json` (semver) **et** dans `.claude-plugin/marketplace.json`
3. Ajouter une ligne (skill / rôle) dans `documentation/workflow.md`
4. Mettre à jour le `CHANGELOG.md`
5. `git push`

### Tester localement avant push
Depuis n'importe quel projet :
```
claude --plugin-dir /Users/gabriel/projets/forge/plugins/workflow
```
Pendant la session : `/reload-plugins` après chaque modif.

### Installation côté utilisateur
```
/plugin marketplace add gabrielmustiere/forge
/plugin install workflow@forge
/reload-plugins
```
Pull des maj : `/plugin marketplace update forge`.

## Conventions

- Kebab-case partout (noms de skills, noms de dossiers) — le `name` YAML du SKILL.md doit matcher le nom du dossier
- Français dans les `description`
- Semver dans `plugin.json` et `marketplace.json` (gardés alignés)
- Frontmatter SKILL.md minimal = `name` + `description`. Champs utiles : `disable-model-invocation`, `user_invocable`, `allowed-tools`, `paths`
- Substitutions dispo dans le contenu SKILL.md : `$ARGUMENTS`, `$0`, `$1`, `${CLAUDE_SKILL_DIR}`, `${CLAUDE_SESSION_ID}`

## Piège fréquent

Une skill qui ne se déclenche pas automatiquement → le problème est presque toujours la `description` (trop vague ou sans les mots-clés que l'utilisateur dirait naturellement). Fix : rendre la description plus spécifique et inclure les phrases déclencheurs. Les descriptions > 250 caractères sont tronquées dans la liste de skills chargée en contexte.
