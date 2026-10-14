# Squelettes d'`AGENTS.md` et du pont `CLAUDE.md`

Charge ce template au moment de la rédaction. Chaque section ne s'écrit que si tu as la
matière **prouvée** pour la remplir — une section sans preuve devient une question, pas un
remplissage au jugé. L'ordre va du plus structurant (ce qu'est le projet) au plus
opérationnel (comment on y travaille), puis la couche comportementale.

## `AGENTS.md` — la source de vérité

Écrit pour **n'importe quel agent** : aucune mention de mécanisme propre à Claude Code. On dit
« l'agent », pas « Claude ».

```markdown
# AGENTS.md

Ce fichier guide les agents de code qui travaillent sur ce dépôt.

## Nature du projet

[Une à trois phrases : qu'est-ce que ce projet, à quoi il sert, son type
(application web, CLI, lib, monorepo, marketplace…). Reprends docs/vision.md si présent.]

## Stack technique

[Langages, frameworks, bases de données, infra — synthèse courte. Si docs/stack.md existe,
renvoie vers lui plutôt que de dupliquer : « Stack détaillée : `docs/stack.md` ». Sinon, liste
l'essentiel prouvé par les manifestes.]

## Architecture

[Carte mentale du dépôt : dossiers principaux et leur rôle, points d'entrée, frontières entre
modules. Un arbre commenté est souvent plus clair qu'un paragraphe.]

## Commandes

[UNIQUEMENT les commandes réellement présentes — scripts package.json, cibles Makefile,
scripts composer, justfile, tâches CI. Ne jamais inventer une commande de test ou de build.]

- **Installer** : `[…]`
- **Lancer** : `[…]`
- **Tester** : `[…]`
- **Lint / format** : `[…]`
- **Build** : `[…]`

## Conventions

[Règles de code spécifiques au projet que les manifestes ne disent pas : nommage, structure
de dossiers imposée, style de commit, langue des commentaires, patterns à suivre ou à éviter.
Tire-les du code existant et des configs (linters, editorconfig), pas de généralités.]

## Pièges fréquents

[Optionnel mais précieux : les erreurs qu'un nouveau venu commet sur CE projet. À remplir
seulement si tu en as repéré dans le code, les configs ou docs/.]

<!-- Section « Principes de travail » : injecter le bloc de references/principes-karpathy.md -->
```

## `CLAUDE.md` — le pont

`@AGENTS.md` **en première ligne**, toujours. Le reste n'existe que s'il y a du spécifique
Claude Code à dire — sinon le fichier s'arrête à l'import.

```markdown
@AGENTS.md

## Spécifique Claude Code

[Seulement ce qui nomme un mécanisme de Claude Code. Exemples :]

- Conventions par zone : `.claude/rules/` (`doctrine.md`, `templates.md`), chargées à
  l'ouverture des fichiers concernés.
- [Skills ou slash commands du projet, hooks, serveurs MCP, subagents — une ligne chacun.]
```

Le renvoi vers `.claude/rules/` appartient au pont, pas à `AGENTS.md` : les autres agents ne
chargent pas ces règles, le renvoi ne leur dirait rien.

## Règles de rédaction

- **Court et dense.** Ces fichiers sont lus à chaque session : chaque ligne doit gagner sa
  place. Vise la concision — pas un pavé exhaustif.
- **Impératif et concret.** « Lance les tests avec `npm test` » plutôt que « le projet dispose
  d'une suite de tests ».
- **Une ligne, un fichier.** Rien ne figure dans les deux : un doublon est lu deux fois par
  Claude Code et finit par diverger.
- **Pas de duplication avec les docs vivantes.** Si `docs/stack.md` ou `docs/vision.md`
  existent, renvoie vers eux au lieu de recopier — ils évoluent, `AGENTS.md` pointe.
- **Des liens, pas des imports, dans `AGENTS.md`.** Une syntaxe `@chemin` n'est développée que
  par Claude Code ; pour les autres agents, c'est du texte. Un lien Markdown se lit partout.
- **Préserver l'existant.** En mise à jour, ne réécris pas les sections que l'utilisateur a
  rédigées à la main sans le lui signaler — elles encodent souvent un savoir non détectable.
