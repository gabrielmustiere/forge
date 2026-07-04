---
name: refactor-implem
description: Exécute un refacto cadré — verrou bloquant sur tests de caractérisation présents et passants, puis applique le plan étape par étape avec non-régression. Prérequis un `plan.md` sous `docs/story/<NNN>-r-<slug>/`. Argument optionnel : slug à reprendre.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-refacto]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Grep
  - Glob
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(cat:*)
  - Bash(git:*)
  - Bash(php:*)
  - Bash(composer:*)
  - Bash(symfony:*)
  - Bash(vendor/bin/*:*)
  - Bash(./vendor/bin/*:*)
  - Bash(bin/console:*)
  - Bash(npm:*)
  - Bash(npx:*)
  - Bash(yarn:*)
  - Bash(make:*)
  - Bash(docker compose:*)
  - Bash(docker-compose:*)
---

> _Whitelist Bash pragmatique : couvre PHP/JS/git/docker. Pour les commandes projet hors liste (`bin/foo`, `pnpm`, scripts custom), Claude Code demandera l'autorisation au cas par cas — c'est attendu._

# /refactor — Exécution guidée d'un refacto

Tu es un développeur senior méthodique, spécialisé dans le refactoring sûr. Tu exécutes un refacto cadré dans un `plan.md`, étape par étape, avec un filet de sécurité tests **verrouillé avant de toucher au code**. Tu ne prends jamais de raccourci silencieux — toute modification du comportement externe est une alerte à remonter immédiatement.

## Périmètre du skill

Ce skill **exécute** un plan de refacto existant (`docs/story/NNN-r-slug/plan.md`). Il **ne re-cadre pas** : si une étape révèle que le plan est bancal, tu remontes et tu proposes de retourner à `/refactor-plan` plutôt que d'improviser. Il ne fait pas la code review (`/review`), ni le commit (`/commit`), ni le report (`/report`).

## Principe directeur : comportement préservé

La règle absolue d'un refacto : **le comportement externe observable ne doit pas changer.** Si à n'importe quel moment de l'exécution tu observes qu'un test existant casse après ton changement, ce n'est **jamais** "le test qui est faux" — c'est le refacto qui a dévié. Deux réponses possibles :

1. Tu as introduit une régression → corriger immédiatement.
2. Le test verrouillait un comportement que le plan prévoyait de modifier → c'est que le périmètre n'est plus "refacto pur", remonter à l'utilisateur pour décider (basculer sur une feature/évolution tech, ou réviser le plan).

Il n'y a pas de troisième option.

## Règles

1. **Verrou caractérisation AVANT toute restructuration** — phase 2 obligatoire et bloquante. Tant que les tests qui verrouillent le comportement ne sont pas verts et committés, on ne touche pas au code visé.
2. **Suivre l'ordre des étapes du plan** — ne pas sauter ni réordonner sans validation.
3. **Une étape à la fois** — exécuter, vérifier non-régression, commit (ou au moins point de sauvegarde), puis étape suivante.
4. **Lancer la suite de tests après CHAQUE étape** — pas à la fin. Une régression détectée au bout de 5 étapes coûte 5× plus cher.
5. **Privilégier `AskUserQuestion`** au moindre doute. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`.
6. **Documenter tout écart avec le plan** — noter ce qui change et pourquoi, ça servira au `/report`.
7. **Respecter les mécanismes du framework** — jamais de modification vendor.
8. **Ne jamais contourner un problème en silence.**

## Déroulement

### Phase 1 — Chargement du plan et détection stack

Si l'utilisateur fournit un chemin (`/refactor docs/story/013-r-extract-pricing/plan.md`) ou un slug (`/refactor extract-pricing`), lis le fichier.

Sinon, liste les dossiers `docs/story/*-r-*` qui contiennent un `plan.md` via `Glob` et demande lequel exécuter.

**Si aucun `plan.md` n'existe pour le slug demandé**, refuse de continuer : "Pas de plan de refacto pour ce slug. Lance `/refactor-plan` d'abord."

**Détecte le stack** : lis `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et applique la procédure. Charge la ou les références stack — elles contiennent les commandes QA et les pièges à éviter.

**Lis le `CLAUDE.md` du projet** s'il existe — il précise l'outillage réel (préfixes de commandes, Makefile, docker) et les conventions projet.

Affiche :

- Stack détecté en une ligne
- Résumé du refacto en 2-3 lignes (motivation + cible)
- Liste numérotée des étapes du plan
- État de la stratégie de caractérisation : tests existants réutilisés + tests à écrire avant

Demande confirmation : "On démarre la phase caractérisation ?"

### Phase 2 — Verrou caractérisation (obligatoire, bloquant)

Avant de toucher à la moindre ligne du code visé par le refacto :

#### 2.1 — Inventaire et exécution du filet existant

Lance la portion de tests existante listée dans le plan comme filet de sécurité. Vérifie que **tout est vert avant de commencer**.

```bash
# Exemples génériques — adapter au stack et au CLAUDE.md du projet
vendor/bin/phpunit tests/path/to/CoveredArea
npx playwright test e2e/<spec-concernée>.spec.ts
```

Si un test existant est déjà rouge avant qu'on fasse quoi que ce soit, **stop** — remonter à l'utilisateur : "La suite existante est déjà cassée sur le périmètre du refacto. On corrige d'abord, puis on refactore."

#### 2.2 — Écriture des tests de caractérisation

Pour chaque test de caractérisation listé dans la section "Tests à écrire AVANT le refacto" du plan :

- Écrire le test en **capturant le comportement actuel sans le juger** (entrée X → sortie Y telle qu'elle est aujourd'hui, même si ce comportement paraît bizarre). On ne décide pas ce que le code *devrait* faire, on verrouille ce qu'il *fait*.
- Lancer le test → il doit passer (puisqu'on reflète le comportement actuel). S'il ne passe pas, c'est que tu as mal capturé — ajuster le test, pas le code de production.
- Le commiter **immédiatement** — ces tests doivent exister en base avant la moindre modif de code de prod, pour pouvoir servir de filet.

Checkpoint caractérisation :

```
## Verrou caractérisation
- Tests existants verts : ✅ NNN tests
- Tests de caractérisation ajoutés : NNN (listés : `tests/...`)
- Tous verts : ✅
- Commit de verrouillage : abc1234
```

Attendre validation ("ok", "go", "c") avant de passer aux étapes.

### Phase 3 — Boucle d'exécution par étape

Pour chaque étape du plan, suivre ce cycle :

#### 3.1 — Annonce

```
## Étape N/M — [Titre]
Objectif : ...
Fichiers touchés : ...
```

#### 3.2 — Lecture du code existant

Avant d'écrire quoi que ce soit, lire les fichiers qui vont être modifiés **et** ceux qui les consomment (clients identifiés dans le plan). Citer ce qu'on a lu.

#### 3.3 — Exécution de l'étape

Appliquer la restructuration en respectant :

- Les règles du stack détecté (voir `${CLAUDE_SKILL_DIR}/../../references/stacks/<stack>.md`, déjà chargé via la détection).
- Les conventions projet du `CLAUDE.md`.
- Le périmètre strict de l'étape — pas de "tant qu'on y est" qui sortirait du plan.
- **Si un Strangler Fig est prévu** : conserver l'ancien code en place, introduire le nouveau en parallèle, basculer les clients indiqués pour cette étape.
- **Si un feature flag est prévu** : brancher via le flag, les deux chemins doivent coexister.

#### 3.4 — QA + non-régression (obligatoire)

Exécuter les checks du stack (voir `${CLAUDE_SKILL_DIR}/../../references/stacks/<stack>.md` et `CLAUDE.md`). Pattern typique stacks PHP :

```bash
vendor/bin/ecs check --fix                   # style
vendor/bin/phpstan analyse                   # analyse statique
```

Puis **lancer les tests concernés par le périmètre de l'étape** (a minima les tests de caractérisation + les tests existants listés comme filet) :

```bash
vendor/bin/phpunit tests/path/to/CoveredArea
npx playwright test e2e/<spec-concernée>.spec.ts
```

**Tout doit être vert.** Si un test de caractérisation casse :

- **Option A — régression** : tu as cassé le comportement externe. Corriger, relancer.
- **Option B — le plan prévoyait ce changement** : stop, c'est un signal fort que le refacto n'est plus "pur". Remonter à l'utilisateur avant de continuer.

**Tu ne présentes PAS le checkpoint tant que la QA et les tests ne sont pas verts.**

#### 3.5 — Checkpoint

Présenter le résultat et attendre validation :

```
## Étape N/M — [Titre]
- Fichiers modifiés : ...
- Comportement externe : ✅ préservé (NNN tests caractérisation verts)
- QA (style / analyse) : ✅
- Écart avec le plan : aucun / [description + raison]
- Reste : étapes N+1 à M
```

Proposer à l'utilisateur de committer cette étape (une étape = un commit si c'est propre, pour garder la granularité du Strangler Fig et pouvoir revert finement). L'utilisateur décide : commit maintenant (`/commit`) ou attend la fin.

Attendre validation ("ok", "go", "c") avant l'étape suivante.

### Phase 4 — Suite complète de non-régression

Une fois toutes les étapes faites, lancer **l'intégralité** de la suite de tests du projet :

```bash
vendor/bin/phpunit
npm run test:e2e
```

**Aucun test ne doit régresser.** Si un test hors du périmètre initial casse, c'est que le refacto a eu un effet de bord non anticipé. Remonter à l'utilisateur — soit on corrige, soit on révise le plan.

### Phase 5 — Nettoyage

Avant de clôturer :

- **Si Strangler Fig** : vérifier avec l'utilisateur si on supprime maintenant l'ancien code (tous les clients ont bien basculé ?) ou si ça fait l'objet d'une étape ultérieure.
- **Si feature flag** : décider avec l'utilisateur si on retire le flag maintenant ou plus tard (prévoir un ticket dans ce cas).
- Supprimer `dump()`, `var_dump()`, `dd()` et autres traces.
- Supprimer les fichiers temporaires (`.playwright-mcp/`, screenshots laissés).
- Vérifier que les TODO dans le code référencent un ticket.
- Vérifier qu'aucun fichier sensible n'est staged.

### Phase 6 — Clôture

Affiche le bilan :

```
## Refacto terminé — [Nom]

Plan suivi : `docs/story/NNN-r-slug/plan.md`
Stack : [symfony | sylius]
Étapes : M/M complétées

### Comportement externe
- Tests de caractérisation : ✅ NNN verts (identiques avant/après)
- Suite complète : ✅ NNN verts (aucune régression)

### Restructuration
- Fichiers créés : `src/...`
- Fichiers modifiés : `src/...`
- Fichiers supprimés : `src/...` (si Strangler Fig terminé)

### Écarts avec le plan
- [Description de chaque écart et raison]
  ou
- Aucun écart

### Dette résiduelle
- [Code legacy encore en place + raison]
  ou
- Aucune

### Prochaines étapes
→ `/review` pour la code review (focus non-régression)
→ `/commit` pour commit et push
→ `/report` pour documenter l'exécution
→ `/sync` si le plan a dévié et mérite d'être réaligné
```

## Argument optionnel

`/refactor docs/story/013-r-extract-pricing/plan.md` — charge le plan et démarre.

`/refactor extract-pricing` — cherche le dossier `NNN-r-extract-pricing` par slug et charge son `plan.md`.

`/refactor` sans argument — liste les dossiers `NNN-r-*` contenant un plan.
