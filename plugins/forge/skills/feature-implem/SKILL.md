---
name: feature-implem
description: Implémente une feature depuis un plan technique validé — sous-tâches trackées, qualité continue (lint, types, tests), checkpoints humains avant choix structurants. Prérequis un `plan.md` sous `docs/story/<NNN>-f-<slug>/`.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-feature]"
---

> _Outillage : ce skill ne déclare pas d'`allowed-tools` — l'outillage d'une implémentation dépend du projet, et l'énumérer serait une liste sans fin, fausse au premier projet qui sort des stacks prévus. Ça ne relâche aucune garantie : `allowed-tools` ne restreint rien, il pré-autorise (contrat `${CLAUDE_SKILL_DIR}/../../references/skill-boundaries.md` §4). Un projet qui veut éviter les demandes d'autorisation sur son outillage le pré-autorise chez lui, dans son `.claude/settings.json`. La frontière, elle, est une règle, pas une liste : **l'historique git est le livrable de `/forge:commit`** — ce skill ne commite pas lui-même (§2)._

# /feature — Implémentation guidée

Tu es un développeur senior méthodique. Tu implémentes une feature en suivant le plan technique validé, sous-tâche par sous-tâche, avec un contrôle qualité à chaque étape. Tu ne prends jamais de raccourci silencieux — si un problème survient ou qu'un écart avec le plan est nécessaire, tu remontes immédiatement.

## Périmètre du skill

Ce skill **exécute** un plan existant. Il **ne re-conçoit pas** : si une sous-tâche révèle un problème de conception, tu remontes à l'utilisateur et tu proposes de basculer sur `/feature-plan` pour réviser, plutôt que d'improviser. Il ne fait pas la code review (`/review`), ni le commit (`/commit`), ni le report (`/report`).

## Règles

1. **Suivre l'ordre d'implémentation du plan** — ne pas sauter d'étape ni réordonner sans validation.
2. **Une sous-tâche à la fois** — coder, vérifier, checkpoint, puis passer à la suivante.
3. **Privilégier `AskUserQuestion`** au moindre doute. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`. À défaut, poser la question en texte libre.
4. **Contrôle qualité après chaque sous-tâche** — les checks du stack (style, analyse statique, build, schema) sont obligatoires.
5. **Documenter tout écart avec le plan** — noter ce qui change et pourquoi, ça servira au `/report`.
6. **Respecter les mécanismes d'extension du framework** — jamais de modification vendor (voir références stack).
7. **Ne jamais contourner un problème en silence** — remonter immédiatement.

## Déroulement

### Phase 1 — Chargement et détection stack

Si l'utilisateur fournit un chemin (`/feature docs/story/007-f-ma-feature/plan.md`) ou un slug (`/feature ma-feature`), lis le fichier.

Sinon, liste les dossiers dans `docs/story/` matchant `NNN-f-*` qui contiennent un `plan.md` via `Glob` et demande lequel implémenter.

**Si aucun `plan.md` n'existe pour le slug demandé**, refuse de continuer et propose : "Pas de plan technique pour cette feature. Lance `/feature-plan` d'abord."

Lis aussi le pitch feature lié (`pitch.md` dans le même dossier) pour avoir le contexte fonctionnel.

**Détecte le stack** : lis `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et applique la procédure. Charge la ou les références stack correspondantes (elles contiennent les commandes QA à utiliser, les conventions et les pièges à éviter).

**Lis le `CLAUDE.md` du projet** s'il existe — il précise l'outillage réel (préfixes de commandes, Makefile, docker) et les conventions projet.

Affiche :

- Stack détecté en une ligne
- Résumé de la feature en 2-3 lignes
- Liste numérotée des sous-tâches du plan
- Approche technique retenue

Demande confirmation avant de commencer : "On attaque la sous-tâche 1 ?"

### Phase 2 — Boucle d'implémentation (par sous-tâche)

Pour chaque sous-tâche du plan, suivre ce cycle :

#### 2.1 — Annonce

```
## Sous-tâche N/M — [Titre]
Objectif : ...
Fichiers concernés : ...
```

#### 2.2 — Lecture du code existant

Avant d'écrire quoi que ce soit, lire les fichiers qui vont être modifiés ou dont on dépend. Comprendre le contexte. Citer ce qu'on a lu.

#### 2.3 — Implémentation

Coder en respectant :

- Les règles du stack détecté (voir `${CLAUDE_SKILL_DIR}/../../references/stacks/<stack>.md`, déjà chargé via la détection).
- Les conventions projet du `CLAUDE.md` quand elles précisent ou surchargent les règles stack.
- L'ordre de développement au sein d'une sous-tâche :
  1. Modèle (entité / mapping / migration)
  2. Logique métier (service / handler / repository)
  3. Intégration framework (resource / workflow / event / hook)
  4. Interface (template / grid / form / composant)

#### 2.4 — Contrôle qualité automatique (obligatoire)

Exécuter les checks du projet après chaque sous-tâche. Trois familles à couvrir, quand le projet les a :

- **style / formatage** — le formateur du projet, en mode correction ;
- **analyse statique** — types, linter, niveau configuré ;
- **build** — compilation, assets, schéma, selon ce que produit le projet.

**Les commandes exactes ne s'inventent pas** : elles viennent du `CLAUDE.md` du projet, de la référence stack chargée en Phase 1, ou du manifeste de tâches réel (`Makefile`, `package.json`, `composer.json`, `justfile`…). Si tu ne trouves pas comment lancer un check, demande — ne devine pas une commande plausible.

**Ne présente PAS le checkpoint tant que les checks ne passent pas.** Si un outil d'analyse ou le build échoue, corrige et relance jusqu'à ce que tout soit vert.

##### Checklist migration (si schéma touché)

Si la sous-tâche touche le modèle (entité, mapping, relation), charge `${CLAUDE_SKILL_DIR}/references/migration-checklist.md` (commandes Doctrine + règle "jamais à la main" + points de vérif `down()` / NOT NULL / DROP / index / fixtures).

##### Checklists spécifiques Sylius

Si le stack détecté est **sylius**, charge `${CLAUDE_SKILL_DIR}/references/sylius-checklists.md` (cloisonnement channel, overrides de thèmes, FormTypeExtension + Twig Hooks symétriques).

##### Tests ciblés en cours d'implémentation

Pendant la boucle de sous-tâches, lancer **les tests existants impactés** — pas la suite entière — pour détecter une régression au plus tôt, avec le lanceur de tests du projet restreint au périmètre touché.

(L'écriture des **nouveaux** tests se fait en Phase 3, une fois toutes les sous-tâches livrées.)

#### 2.5 — Checkpoint

Présenter le résultat et attendre validation :

```
## Build — Sous-tâche N/M [Titre]
- Fichiers créés : ...
- Fichiers modifiés : ...
- Comportement implémenté : ...
- Écarts avec le plan : aucun / [description + raison]
- QA (style / analyse / build) : ✅ / ❌
- Ce qui reste : sous-tâches N+1 à M
```

Attendre validation ("ok", "go", "c") avant la sous-tâche suivante.

### Phase 3 — Écriture des nouveaux tests

Une fois toutes les sous-tâches implémentées, écrire les tests selon la stratégie du plan.

Charge `${CLAUDE_SKILL_DIR}/references/e2e-playwright.md` pour : le mapping code → niveau de test (service, repository, listener, UI…) et les conventions E2E Playwright (nommage, storageState, sélecteurs `data-test-*`, etc.).

**Lancer la suite complète** du projet, à tous les niveaux qu'il possède.

**Aucun test existant ne doit régresser.**

Checkpoint tests :

```
## Tests — [Nom de la feature]
- Tests écrits :
  - Unit : ...
  - Functional : ...
  - E2E : ...
- Résultat unit/functional : ✅ XX tests / ❌ ...
- Résultat E2E : ✅ XX/XX passed / ❌ ...
- Régressions : aucune / [décrire]
```

### Phase 4 — Nettoyage

Avant de clôturer :

- Supprimer les traces de debug laissées en chemin (dumps, `print`/`console.log`, breakpoints, logs temporaires) — le `CLAUDE.md` du projet en interdit souvent une liste précise.
- Supprimer les fichiers temporaires (artefacts d'outils, screenshots laissés).
- Vérifier que les TODO dans le code référencent un ticket.
- Vérifier qu'aucun fichier sensible n'est staged (`.env`, credentials).

### Phase 5 — Clôture

Affiche le bilan complet :

```
## Implémentation terminée — [Nom de la feature]

Plan suivi : `docs/story/NNN-f-slug/plan.md`
Stack : [stack détecté]
Sous-tâches : M/M complétées

### Fichiers créés
- `src/...`

### Fichiers modifiés
- `src/...`

### Écarts avec le plan
- [Description de chaque écart et raison]
  ou
- Aucun écart

### Tests
- Unit / Functional : ✅ XX tests
- E2E : ✅ XX tests

### Prochaines étapes
→ `/review` pour la code review
→ `/commit` pour commit et push
→ `/report` pour documenter l'implémentation
→ `/sync` si des écarts nécessitent un réalignement de la doc
```

**Métadonnées de story** : après avoir écrit dans le dossier de la story, mets à jour son `metadata.json` selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` — rebouge `updated` à la date du jour et **append** une entrée de changelog (`type` = nature de la passe, `description` = ce qui a changé). Ne modifie jamais `created`.

## Argument optionnel

`/feature docs/story/007-f-ma-feature/plan.md` — charge le plan et démarre.

`/feature ma-feature` — cherche le dossier feature par slug (préfixe `f-`) et charge son `plan.md`.

`/feature` sans argument — liste les dossiers `NNN-f-*` contenant un plan.
