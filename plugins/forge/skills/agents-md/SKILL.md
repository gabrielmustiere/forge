---
name: agents-md
description: Génère, met à jour ou migre l'AGENTS.md racine (lu par Claude Code et les autres agents) avec un CLAUDE.md pont `@AGENTS.md`. Déclenche sur « génère un AGENTS.md », « migre mon CLAUDE.md », « documente le repo pour les agents ».
user_invocable: true
disable-model-invocation: true
argument-hint: "[section ciblée ou intention libre]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(cat:*)
  - Bash(mkdir:*)
  - Bash(git mv:*)
  - Bash(git ls-files:*)
---

# /agents-md — Génère les instructions projet pour les agents de code

Tu produis les **instructions projet** à la racine du dépôt : ce que tout agent de code lit à
chaque session pour comprendre **ce qu'est le projet, comment on y travaille, et comment se
comporter**. Leur valeur tient à deux choses indissociables :

1. **Une analyse fidèle du projet réel** — architecture, commandes, conventions — *prouvée par
   les fichiers du dépôt*, jamais inventée.
2. **Une couche comportementale** — les principes Karpathy — qui corrige les écueils
   récurrents du code assisté par LLM (hypothèses silencieuses, sur-ingénierie, refactos
   sauvages, sur-commentaire, absence de critère de succès).

Des instructions qui décrivent bien le projet mais laissent l'agent coder n'importe comment
sont à moitié utiles ; l'inverse aussi. Tu fournis les deux.

## Deux fichiers, un seul contenu

`AGENTS.md` est le format ouvert que lisent Codex, Cursor, Copilot, Gemini CLI… et, depuis la
v2.1.277, Claude Code. Tu écris donc **deux fichiers aux rôles nets** :

- **`AGENTS.md`** — la **source de vérité**. Tout ce qui vaut pour n'importe quel agent : nature
  du projet, stack, architecture, commandes, conventions, pièges, principes de travail.
- **`CLAUDE.md`** — un **pont**. Première ligne `@AGENTS.md`, puis seulement ce qui n'a de sens
  que dans Claude Code (renvoi vers `.claude/rules/`, skills, hooks, serveurs MCP…). Souvent
  rien d'autre que l'import.

Le pont n'est pas une coquetterie. Claude Code lit `AGENTS.md` **seulement si aucun `CLAUDE.md`
ni `CLAUDE.local.md` n'existe** dans le répertoire ou ses parents. Il ne le lit pas non plus
dans certaines sessions : Bedrock, Vertex, Foundry, télémétrie désactivée, versions antérieures,
ou plugin intégré qui fait cette lecture désactivé. L'import `@AGENTS.md` couvre tous ces cas,
et la doc garantit qu'il ne fait jamais lire le fichier deux fois.

**Le test de tri**, à appliquer à chaque ligne : *un agent qui n'est pas Claude Code pourrait-il
en faire quelque chose ?* Oui → `AGENTS.md`. Non → `CLAUDE.md`. Est spécifique à Claude Code ce
qui nomme ses mécanismes : `.claude/rules/`, `.claude/settings.json`, skills et slash commands
(`/forge:…`), hooks, serveurs MCP, subagents, outils nommés (`AskUserQuestion`, `ToolSearch`…).

## Périmètre du skill

Ce skill écrit **deux fichiers** : `AGENTS.md` et `CLAUDE.md` à la racine. Ce n'est **pas** :

- La cartographie technique exhaustive (`/stack` → `docs/stack.md`) — `AGENTS.md` en fait une
  **synthèse courte** et renvoie vers `docs/stack.md` s'il existe.
- La vision produit (`/vision` → `docs/vision.md`) ni le périmètre fonctionnel
  (`/product-backlog`) — `AGENTS.md` peut en reprendre une phrase, pas les détailler.
- Les conventions scopées à une zone du code (`/rules` → `.claude/rules/`).
- Une décision d'architecture (`/adr`).

Si l'utilisateur dérive vers « documente toute ma stack » ou « cadre la vision », recadre vers
le skill dédié. `AGENTS.md` est le **point d'entrée court** ; les docs vivantes portent le
détail.

**Différence avec le `/init` natif** : `/init` génère un `CLAUDE.md` centré sur le codebase.
`/agents-md` écrit des instructions **portables** entre agents, y ajoute la couche
comportementale Karpathy et **réutilise les artifacts forge** (`docs/stack.md`, `docs/vision.md`)
au lieu de tout re-détecter.

## Règles du mode interactif

1. **Ne jamais écrire sans avoir montré la synthèse et obtenu un go.** Ces fichiers sont lus à
   chaque session et contiennent souvent du savoir manuel précieux. Présente ce que tu comptes
   écrire, attends « go » / « c'est bon » / « rédige ».
2. **Tout prouver par un fichier.** Chaque commande, chaque techno, chaque convention listée
   doit pointer vers un fichier réel (`package.json`, `Makefile`, `composer.json`, un linter,
   le code lui-même). Une affirmation non prouvable est une **question**, pas une ligne.
3. **Ne jamais inventer une commande.** Pas de `npm test` si aucun script `test` n'existe. Si
   tu ne trouves pas comment on teste/build/lance, demande — ou laisse la ligne hors du fichier.
4. **Préserver l'existant.** Ne réécris pas une section rédigée à la main sans le signaler. En
   migration, **déplace sans réécrire** : le contenu change de fichier, pas de formulation —
   sauf les mentions de « Claude » qui deviennent « l'agent » dans `AGENTS.md`.
5. **Réutiliser avant de re-détecter.** Lis `docs/stack.md`, `docs/vision.md` et `README.md`
   s'ils existent : ils contiennent déjà la matière. `AGENTS.md` les synthétise et y renvoie.
6. **Privilégier `AskUserQuestion`** pour combler les trous (max 3 questions par tour). Si
   l'outil n'est pas chargé, le récupérer via `ToolSearch`, sinon poser en texte libre.

## Déroulement

### Phase 0 — Inventaire de l'existant et choix du mode

1. **Instructions existantes** : vérifier `AGENTS.md`, `CLAUDE.md` et `.claude/CLAUDE.md` à la
   racine (`ls -l` : un `CLAUDE.md` peut être un lien symbolique vers `AGENTS.md`). Lire
   intégralement ceux qui existent — c'est la base à faire évoluer, pas à écraser.
2. **Artifacts forge** : lire `docs/vision.md` (nature/but du projet) et `docs/stack.md` (stack
   prouvée) s'ils existent. Ils dispensent de re-détecter ce qui est déjà documenté.
3. **`README.md`** : souvent la source la plus directe pour les commandes et le pitch.

**Choix du mode**, dicté par l'état trouvé :

| État | Ce que Claude Code lit aujourd'hui | Mode |
|---|---|---|
| Ni `AGENTS.md` ni `CLAUDE.md` | rien | **Création** |
| `CLAUDE.md` seul | `CLAUDE.md` | **Migration** |
| Les deux, `CLAUDE.md` sans `@AGENTS.md` | `CLAUDE.md` seul — **`AGENTS.md` est ignoré** | **Migration** (fusion) |
| `AGENTS.md` seul | `AGENTS.md`, selon la session | **Mise à jour** + poser le pont |
| `CLAUDE.md` lien symbolique vers `AGENTS.md` | `AGENTS.md` | **Mise à jour** + remplacer le lien par le pont |
| `AGENTS.md` + pont en place | les deux | **Mise à jour** |

Annonce l'état et ce qu'il implique — en particulier le cas « les deux sans import », où
l'utilisateur croit souvent que son `AGENTS.md` est lu alors qu'il ne l'est pas. Le lien
symbolique se remplace parce que les outils `Edit`/`Write` refusent d'écrire à travers lui, et
qu'un clone sous Windows peut le transformer en fichier texte d'une ligne.

Si l'état laisse le choix (Mise à jour possible), demande explicitement : **Mise à jour** (le cas
normal) ou **Création** (rare : l'existant est inutilisable — confirmer, car on perd le contenu
manuel).

### Phase 1 — Détection (si la matière n'est pas déjà dans les artifacts)

Ne re-détecte que ce que `docs/stack.md` / `docs/vision.md` ne couvrent pas. Pour le reste,
scanne le dépôt couche par couche :

1. **Nature & stack** : manifestes de dépendances (`package.json`, `composer.json`,
   `pyproject.toml`, `go.mod`, `Cargo.toml`…) → langage, frameworks, type de projet.
2. **Commandes** : scripts `package.json`, cibles `Makefile`/`justfile`, scripts `composer`,
   tâches CI (`.github/workflows/`). C'est la source de vérité des commandes réelles.
3. **Architecture** : `Glob`/`ls` sur l'arbre des dossiers de premier niveau, repérer points
   d'entrée et frontières de modules. Lire les fichiers d'index/bootstrap si besoin.
4. **Conventions** : configs de linters/formatters (`.eslintrc`, `.php-cs-fixer`, `ruff.toml`,
   `.editorconfig`), structure imposée, langue des commentaires lue dans le code.
5. **Spécifique Claude Code** : `.claude/rules/`, `.claude/settings.json`, `.mcp.json`,
   `.claude/agents/` — ce qui alimentera la partie propre du pont.

En **Migration**, la détection sert à vérifier l'existant (une commande citée existe-t-elle
encore ?), pas à le reconstruire.

### Phase 2 — Synthèse

Présente ce que tu comptes écrire, fichier par fichier, en distinguant le **prouvé** des
**trous** :

```
## AGENTS.md (prouvé)
- Nature : marketplace de plugins Claude Code (docs/vision.md, marketplace.json)
- Stack : PHP 8.2 / Symfony 6.4 (composer.json) — renvoi vers docs/stack.md
- Commandes : test `composer test`, lint `composer cs` (composer.json → scripts)
- Architecture : src/ (domaine), config/, tests/ (arbre détecté)
- Conventions : PSR-12 (.php-cs-fixer.dist.php), commits FR conventionnels (historique git)
- Principes de travail : bloc Karpathy

## CLAUDE.md (pont)
- @AGENTS.md
- Spécifique Claude Code : renvoi vers .claude/rules/ (3 zones)

## Trous (à confirmer)
- Comment lance-t-on le projet en local ? (aucun script détecté)
- Pièges connus à signaler à un nouveau venu ?
```

**En Migration**, la synthèse est un **tri** : chaque section du `CLAUDE.md` existant, avec sa
destination (`AGENTS.md`, reste dans `CLAUDE.md`, ou à corriger car périmée) et la raison en
quelques mots quand elle n'est pas évidente. En cas de fusion avec un `AGENTS.md` existant,
signale chaque contradiction entre les deux fichiers : c'est une question, pas un arbitrage
silencieux.

Deux cas à signaler pendant le tri :

- **Les imports `@chemin`** du `CLAUDE.md` fonctionnent aussi dans `AGENTS.md` pour Claude
  Code, mais les autres agents ne les suivent pas. Un import de contenu générique qui part dans
  `AGENTS.md` mérite un lien Markdown classique, ou l'intégration du contenu : demande.
- **`CLAUDE.local.md`** (instructions personnelles, non versionnées) : ne le touche pas. Rappelle
  seulement que sa seule présence suffit à désactiver la lecture native d'`AGENTS.md`. Le pont
  règle ce cas.

### Phase 3 — Combler les trous

Pour chaque trou, une question ciblée (3 max par tour) via `AskUserQuestion`. Si l'utilisateur
ne sait pas, **omets la ligne** plutôt que d'inventer.

À propos de la couche comportementale Karpathy — elle est le principal apport de ce skill
par rapport au `/init` natif, alors rends-la visible :

- **En Création** : elle est incluse par défaut. Demande seulement s'il veut la **garder,
  l'alléger ou la retirer** — c'est son fichier.
- **En Mise à jour ou Migration, si l'existant ne la contient pas encore** : signale-le et
  **propose explicitement de l'ajouter** (« tes instructions n'ont pas de section *Principes de
  travail* — veux-tu que je l'ajoute ? »). Ne l'injecte pas en silence. S'il refuse, n'y touche
  pas.

### Phase 4 — Rédaction

Quand l'utilisateur valide, écris. Formats : charge maintenant
`${CLAUDE_SKILL_DIR}/references/template.md` (squelettes des deux fichiers et règles de
rédaction). La couche comportementale vient de
`${CLAUDE_SKILL_DIR}/references/principes-karpathy.md`, en section « Principes de travail »
d'`AGENTS.md`, sauf si l'utilisateur l'a retirée. Adapte uniquement les exemples au projet.

- **Création** : écrire `AGENTS.md` complet depuis le template, puis le pont `CLAUDE.md`.
- **Migration** :
  1. Si `CLAUDE.md` est suivi par git et qu'`AGENTS.md` n'existe pas, `git mv CLAUDE.md AGENTS.md`
     d'abord : l'historique suit le contenu. Sinon (fichier non suivi, ou fusion), crée ou
     complète `AGENTS.md` par `Write`/`Edit`.
  2. Dans `AGENTS.md`, applique le tri validé : retire ce qui revient au pont, neutralise
     l'en-tête et les « Claude » génériques. Rien d'autre ne change de formulation.
  3. Écris le pont `CLAUDE.md` : `@AGENTS.md` en première ligne, puis les sections triées vers lui.
- **Mise à jour** : modifier **seulement** les sections vieillies (via `Edit`), préserver le
  contenu manuel à l'identique. Si le pont manque ou n'est qu'un lien symbolique, le poser
  (supprimer le lien, puis `Write`).

N'ajoute la couche Karpathy à un existant que si elle manquait **et** que l'utilisateur a
accepté ta proposition de Phase 3 — jamais en silence.

Après écriture, relis les deux fichiers : `CLAUDE.md` commence-t-il bien par `@AGENTS.md` ?
Aucune ligne n'est-elle présente dans les deux ? Affiche un résumé et demande si des ajustements
sont nécessaires.

### Phase 5 — Clôture

> `AGENTS.md` écrit à la racine : les instructions du projet, lisibles par n'importe quel agent
> de code. `CLAUDE.md` ne fait que l'importer pour Claude Code. *(si du spécifique)* Il garde en plus :
> <liste courte>.
> *(si docs/stack.md existe)* La stack détaillée reste dans `docs/stack.md` ; `AGENTS.md` y renvoie.
> *(Migration)* Contenu déplacé de `CLAUDE.md` vers `AGENTS.md` : <sections>. Resté dans le pont :
> <sections>. *(si git mv)* L'historique git suit `AGENTS.md`.
> *(Mise à jour)* Sections actualisées : <liste>. Le reste a été préservé.
> Pour vérifier ce que Claude Code charge dans une session : `/memory`.

## Argument optionnel

`/agents-md [section ou intention]` — si l'argument cible une section (« commandes »,
« architecture », « conventions »), oriente la détection et la mise à jour vers elle. Applique
toujours la Phase 0 (lecture de l'existant + choix du mode) avant d'enchaîner.
