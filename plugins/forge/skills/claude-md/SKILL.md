---
name: claude-md
description: Génère ou met à jour le CLAUDE.md racine — analyse du codebase (stack, archi, commandes, conventions) + principes comportementaux Karpathy. Déclenche sur « génère un CLAUDE.md », « initialise les instructions Claude », « documente le repo pour Claude ».
user_invocable: true
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
---

# /claude-md — Génère le fichier d'instructions projet pour Claude Code

Tu produis le `CLAUDE.md` à la racine du dépôt : le fichier que Claude Code lit à chaque
session pour comprendre **ce qu'est le projet, comment on y travaille, et comment se comporter**.
Sa valeur tient à deux choses indissociables :

1. **Une analyse fidèle du projet réel** — architecture, commandes, conventions — *prouvée par
   les fichiers du dépôt*, jamais inventée.
2. **Une couche comportementale** — les quatre principes Karpathy — qui corrige les écueils
   récurrents du code assisté par LLM (hypothèses silencieuses, sur-ingénierie, refactos
   sauvages, absence de critère de succès).

Un `CLAUDE.md` qui décrit bien le projet mais laisse l'agent coder n'importe comment est à
moitié utile ; l'inverse aussi. Tu fournis les deux.

## Périmètre du skill

Ce skill produit **un seul fichier** : le `CLAUDE.md` à la racine. Ce n'est **pas** :

- La cartographie technique exhaustive (`/stack` → `docs/stack.md`) — le `CLAUDE.md` en fait
  une **synthèse courte** et renvoie vers `docs/stack.md` s'il existe.
- La vision produit (`/vision` → `docs/vision.md`) ni le périmètre fonctionnel
  (`/product-backlog`) — le `CLAUDE.md` peut en reprendre une phrase, pas les détailler.
- Une décision d'architecture (`/adr`).

Si l'utilisateur dérive vers « documente toute ma stack » ou « cadre la vision », recadre vers
le skill dédié. Le `CLAUDE.md` est le **point d'entrée court** ; les docs vivantes portent le
détail.

**Différence avec le `/init` natif** : `/init` se concentre sur la documentation du codebase.
`/claude-md` y ajoute la couche comportementale Karpathy et **réutilise les artifacts forge**
(`docs/stack.md`, `docs/vision.md`) au lieu de tout re-détecter.

## Quand lancer ce skill

- **Création** — le dépôt n'a pas de `CLAUDE.md`. On en pose un, calibré sur le projet réel.
- **Mise à jour** — un `CLAUDE.md` existe mais a vieilli (nouvelle commande, archi qui a bougé,
  convention qui a changé) ; on l'actualise sans écraser ce que l'utilisateur a écrit à la main.

## Règles du mode interactif

1. **Ne jamais écrire `CLAUDE.md` sans avoir montré la synthèse et obtenu un go.** Ce fichier
   est lu à chaque session et contient souvent du savoir manuel précieux. Présente ce que tu
   comptes écrire, attends « go » / « c'est bon » / « rédige ».
2. **Tout prouver par un fichier.** Chaque commande, chaque techno, chaque convention listée
   doit pointer vers un fichier réel (`package.json`, `Makefile`, `composer.json`, un linter,
   le code lui-même). Une affirmation non prouvable est une **question**, pas une ligne.
3. **Ne jamais inventer une commande.** Pas de `npm test` si aucun script `test` n'existe. Si
   tu ne trouves pas comment on teste/build/lance, demande — ou laisse la ligne hors du fichier.
4. **Préserver l'existant en mise à jour.** Ne réécris pas une section rédigée à la main sans
   le signaler. Repère ce qui a vieilli, propose la correction ciblée, garde le reste.
5. **Réutiliser avant de re-détecter.** Lis `docs/stack.md`, `docs/vision.md` et `README.md`
   s'ils existent : ils contiennent déjà la matière. Le `CLAUDE.md` les synthétise et y renvoie.
6. **Privilégier `AskUserQuestion`** pour combler les trous (max 3 questions par tour). Si
   l'outil n'est pas chargé, le récupérer via `ToolSearch`, sinon poser en texte libre.

## Déroulement

### Phase 0 — Inventaire de l'existant et choix du mode

1. **`CLAUDE.md` racine** : vérifier sa présence. S'il existe, le lire intégralement — c'est la
   base à mettre à jour, pas à écraser.
2. **Artifacts forge** : lire `docs/vision.md` (nature/but du projet) et `docs/stack.md` (stack
   prouvée) s'ils existent. Ils dispensent de re-détecter ce qui est déjà documenté.
3. **`README.md`** : souvent la source la plus directe pour les commandes et le pitch.

**Choix du mode** :

- Si `CLAUDE.md` n'existe pas → mode **Création** imposé.
- Si `CLAUDE.md` existe → demander explicitement : **Mise à jour** (le cas normal, on actualise)
  ou **Création** (rare : le fichier existant est inutilisable, on repart de zéro — confirmer
  car on perd le contenu manuel).

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

### Phase 2 — Synthèse

Présente, section par section, ce que tu comptes écrire — en distinguant le **prouvé** des
**trous** :

```
## Prêt à écrire (prouvé)
- Nature : marketplace de plugins Claude Code (docs/vision.md, marketplace.json)
- Stack : PHP 8.2 / Symfony 6.4 (composer.json) — renvoi vers docs/stack.md
- Commandes : test `composer test`, lint `composer cs` (composer.json → scripts)
- Architecture : src/ (domaine), config/, tests/ (arbre détecté)
- Conventions : PSR-12 (.php-cs-fixer.dist.php), commits FR conventionnels (historique git)

## Trous (à confirmer)
- Comment lance-t-on le projet en local ? (aucun script détecté)
- Pièges connus à signaler à un nouveau venu ?

## Couche comportementale
- Les 4 principes Karpathy seront injectés en section « Principes de travail ».
```

### Phase 3 — Combler les trous

Pour chaque trou, une question ciblée (3 max par tour) via `AskUserQuestion`. Si l'utilisateur
ne sait pas, **omets la ligne** plutôt que d'inventer.

À propos de la couche comportementale Karpathy — elle est le principal apport de ce skill
par rapport au `/init` natif, alors rends-la visible :

- **En Création** : elle est incluse par défaut. Demande seulement s'il veut la **garder,
  l'alléger ou la retirer** — c'est son fichier.
- **En Mise à jour, si le `CLAUDE.md` ne la contient pas encore** : signale-le et **propose
  explicitement de l'ajouter** (« ton `CLAUDE.md` n'a pas de section *Principes de travail* —
  veux-tu que je l'ajoute ? »). Ne l'injecte pas en silence : une mise à jour reste
  chirurgicale, mais l'utilisateur doit savoir que cette couche existe et décider en connaissance
  de cause. S'il refuse, n'y touche pas.

### Phase 4 — Rédaction

Quand l'utilisateur valide, rédige (ou actualise) `./CLAUDE.md`.

- **Format** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. Charge-le à ce moment.
- **Couche comportementale** : injecte le bloc de
  `${CLAUDE_SKILL_DIR}/references/principes-karpathy.md` en section « Principes de travail »,
  sauf si l'utilisateur l'a retirée. Adapte uniquement les exemples au projet.
- **Mode Création** : écrire le fichier complet depuis le template.
- **Mode Mise à jour** : modifier **seulement** les sections vieillies (via `Edit`), préserver
  le contenu manuel à l'identique. N'ajoute la couche Karpathy que si elle manquait **et** que
  l'utilisateur a accepté ta proposition de Phase 3 — jamais en silence.

Crée les dossiers au besoin. Après écriture, affiche un résumé et demande si des ajustements
sont nécessaires.

### Phase 5 — Clôture

> `CLAUDE.md` généré à la racine. Claude Code le lira au début de chaque session pour cadrer son
> comportement et comprendre le projet.
> *(si docs/stack.md existe)* La stack détaillée reste dans `docs/stack.md` ; le `CLAUDE.md` y renvoie.
> *(mode Mise à jour)* Sections actualisées : <liste>. Le reste a été préservé.

## Argument optionnel

`/claude-md [section ou intention]` — si l'argument cible une section (« commandes »,
« architecture », « conventions »), oriente la détection et la mise à jour vers elle. Applique
toujours la Phase 0 (lecture de l'existant + choix du mode) avant d'enchaîner.
