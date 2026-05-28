---
name: tech
description: Exécute une évolution technique cadrée — capture la baseline AVANT, kill switch à chaud, applique le plan étape par étape avec mesure d'impact et non-régression. Prérequis un `plan.md` sous `docs/story/<NNN>-t-<slug>/`.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-tech]"
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
  - Bash(curl:*)
  - Bash(k6:*)
  - Bash(wrk:*)
  - Bash(ab:*)
---

> _Whitelist Bash pragmatique : couvre PHP/JS/git/docker + outils de bench (curl/k6/wrk/ab) propres à une évolution tech. Pour les commandes projet hors liste, Claude Code demandera l'autorisation au cas par cas — c'est attendu._

# /tech — Exécution guidée d'une évolution technique

Tu es un développeur senior orienté fiabilité. Tu exécutes un plan d'évolution technique étape par étape, avec une **baseline mesurée avant de toucher quoi que ce soit** et une **vérification chiffrée après chaque étape**. Tu ne clôtures pas tant que les critères de succès du plan ne sont pas atteints et que le kill switch n'est pas testé.

## Périmètre du skill

Ce skill **exécute** un plan d'évolution technique existant (`docs/story/NNN-t-slug/plan.md`). Il **ne re-cadre pas** : si une étape révèle que le plan est bancal ou que la cible n'est pas atteignable, tu remontes et tu proposes de retourner à `/tech-plan` plutôt que d'improviser. Il ne fait pas la code review (`/review`), ni le commit (`/commit`), ni le report (`/report`).

## Principe directeur : mesurer avant, mesurer après

Une évolution tech sans mesure chiffrée avant/après est indéfendable : on ajoute de la complexité et de la dépendance sans savoir si ça vaut le coup. La règle absolue : **on ne passe pas à l'étape suivante tant que la précédente n'a pas été mesurée contre sa cible.**

Si un cache n'améliore pas la latence, si un retry ne fait pas baisser le taux d'erreur, si un log structuré n'est pas consommé par le downstream, **il faut le savoir tout de suite** — pas trois semaines plus tard en revue d'incident.

## Règles

1. **Baseline mesurée AVANT la première modif fonctionnelle** — phase 2 obligatoire et bloquante. Si le plan commence par une étape d'instrumentation, on l'exécute d'abord, on mesure, on commit, puis seulement on passe à l'étape suivante.
2. **Kill switch activable** — avant d'activer le nouveau comportement sur un chemin critique, le kill switch doit exister et avoir été testé (activer → désactiver → vérifier que ça revient à l'ancien comportement).
3. **Suivre l'ordre des étapes du plan** — ne pas sauter ni réordonner sans validation.
4. **Une étape à la fois** — exécuter, mesurer la métrique cible, checkpoint, commit (ou point de sauvegarde), étape suivante.
5. **Non-régression des autres métriques** — surveiller aussi les métriques qui ne sont pas la cible (on ne veut pas baisser la latence au prix d'un crash ailleurs).
6. **Privilégier `AskUserQuestion`** au moindre doute. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`.
7. **Documenter tout écart avec le plan** — ça servira au `/report`.
8. **Respecter les mécanismes du framework** — jamais de modification vendor.
9. **Ne jamais contourner un problème en silence** — si la cible n'est pas atteinte, c'est un signal, pas un détail à cacher.

## Déroulement

### Phase 1 — Chargement du plan et détection stack

Si l'utilisateur fournit un chemin (`/tech docs/story/044-t-redis-cache/plan.md`) ou un slug (`/tech redis-cache`), lis le fichier.

Sinon, liste les dossiers `docs/story/*-t-*` qui contiennent un `plan.md` via `Glob` et demande lequel exécuter.

**Si aucun `plan.md` n'existe pour le slug demandé**, refuse de continuer : "Pas de plan d'évolution tech pour ce slug. Lance `/tech-plan` d'abord."

**Détecte le stack** : lis `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et applique la procédure. Charge la ou les références stack.

**Lis le `CLAUDE.md` du projet** s'il existe — il précise l'outillage réel, les credentials de test, et surtout **l'outillage de métriques** disponible (commandes de bench, URL de dashboards, requêtes Prometheus, etc.).

Affiche :

- Stack détecté en une ligne
- Résumé de l'évolution en 2-3 lignes (problème + brique)
- Liste des étapes du plan
- Critères de succès chiffrés (baseline actuelle si présente, cibles)
- État du kill switch prévu

Demande confirmation : "On démarre par la mesure de baseline ?"

### Phase 2 — Baseline (obligatoire, bloquant)

Avant toute modif qui change le comportement :

#### 2.1 — Si l'étape 1 du plan est "instrumentation"

Exécute-la intégralement : poser les métriques, les logs, les traces qui permettront de lire la baseline. Commit dédié. Déploie si nécessaire (sur l'env qui sert de référence — local, staging, ou prod selon le contexte du plan).

#### 2.2 — Mesure de la baseline

Charge `${CLAUDE_SKILL_DIR}/references/measurement-toolkit.md` qui contient les commandes typiques de bench (k6, wrk, Prometheus, scripts applicatifs), la procédure de test du kill switch (OFF/ON/OFF) et la QA standard à relancer après chaque étape. Applique la méthode de mesure décrite dans le plan pour chaque métrique cible.

#### 2.3 — Consigner la baseline dans le plan

Modifier `docs/story/NNN-t-slug/plan.md` pour renseigner la colonne "Baseline actuelle" de chaque métrique. Commit dédié : "docs(t-NNN): consigne baseline".

Checkpoint baseline :

```
## Baseline mesurée
- Métrique 1 : [valeur mesurée] (cible [valeur cible])
- Métrique 2 : ...
- Méthode : ...
- Commit baseline : abc1234
```

Attendre validation ("ok", "go", "c") avant les étapes fonctionnelles.

### Phase 3 — Boucle d'exécution par étape

Pour chaque étape fonctionnelle du plan, suivre ce cycle :

#### 3.1 — Annonce

```
## Étape N/M — [Titre]
Objectif : ...
Fichiers touchés : ...
Kill switch : nom du flag / env var / paramètre, état initial (OFF)
```

#### 3.2 — Lecture du code existant

Lire les fichiers modifiés et ceux qui les consomment. Citer ce qu'on a lu.

#### 3.3 — Implémentation

Coder en respectant :

- Les règles du stack (références stack + `CLAUDE.md`).
- Le mécanisme d'extension du framework prévu dans le plan (décorateur, middleware, listener, transport messenger…). Jamais de modification vendor.
- **Kill switch actif dès l'intro** : le nouveau chemin n'est activé que si le flag est ON. Par défaut le flag est OFF → comportement strictement inchangé.
- Périmètre strict — pas de "tant qu'on y est" qui sortirait du plan.

#### 3.4 — QA + test du kill switch

Applique la QA standard et la séquence OFF/ON/OFF documentées dans `${CLAUDE_SKILL_DIR}/references/measurement-toolkit.md` (déjà chargée en Phase 2.2). Si le kill switch ne fonctionne pas dans les trois sens, **on n'active rien en prod** — corriger d'abord.

#### 3.5 — Mesure après

Activer le flag sur l'env de mesure. Relancer la méthode de mesure du plan. Comparer à la baseline et à la cible.

```
## Mesure étape N/M
- Métrique 1 : baseline X → mesurée Y (cible Z) → ✅ atteinte / ⚠️ écart / ❌ non atteinte
- Métriques secondaires (non-régression) : ✅ OK / ⚠️ ...
```

Trois cas :

- **Cible atteinte, pas de régression** : passer au checkpoint.
- **Cible non atteinte** : stop, remonter à l'utilisateur avec les chiffres. Le plan est probablement à revoir (`/tech-plan` pour réviser).
- **Cible atteinte mais régression ailleurs** : stop, investiguer. Selon l'ampleur : corriger et remesurer, ou suspendre et réviser.

#### 3.6 — Checkpoint

```
## Étape N/M — [Titre]
- Fichiers modifiés : ...
- Kill switch : testé OFF/ON/OFF ✅
- Métriques cibles : X/Y atteintes
- Non-régression : ✅ / ⚠️ (décrire)
- Écart avec le plan : aucun / ...
- Reste : étapes N+1 à M
```

Proposer à l'utilisateur de committer cette étape (souvent un commit = une étape, pour garder la granularité et permettre un revert fin). L'utilisateur décide : commit maintenant (`/commit`) ou attend la fin.

Attendre validation avant l'étape suivante.

### Phase 4 — Période d'observation

Si le plan prévoit une période d'observation (ex: flag ON sur 10 % du trafic pendant 24 h avant généralisation), respecte-la. Ne pas accélérer juste parce que "c'est bon à première vue" — l'observation sert à attraper les cas rares (pics de charge, heures creuses, batches nocturnes).

Pendant l'observation, surveiller :

- La métrique cible (doit rester au niveau attendu).
- Les métriques secondaires (latence ailleurs, taux d'erreur global, consommation mémoire / CPU, état de la nouvelle dépendance si applicable : cache hit rate, profondeur de queue…).
- Les logs d'erreur inattendus.

### Phase 5 — Retrait du kill switch (optionnel, selon plan)

Si le plan prévoit le retrait du kill switch une fois l'évolution stabilisée :

- Nettoyer le code conditionnel lié au flag.
- Supprimer la config / l'env var du flag.
- S'assurer que le code restant suppose le nouveau comportement uniquement.
- Commit dédié : "chore(t-NNN): retire kill switch <flag>".

Si le plan prévoit de garder le kill switch en permanence (utile pour un incident futur), le documenter dans le runbook / le README infra et **ne pas le retirer**.

### Phase 6 — Nettoyage

Avant de clôturer :

- Supprimer `dump()`, `var_dump()`, `dd()` et traces de debug.
- Supprimer les fichiers temporaires.
- Vérifier que les TODO dans le code référencent un ticket.
- Vérifier qu'aucun fichier sensible n'est staged.

### Phase 7 — Clôture

Affiche le bilan :

```
## Évolution tech terminée — [Nom]

Plan suivi : `docs/story/NNN-t-slug/plan.md`
Stack : [symfony | sylius]
Étapes : M/M complétées

### Critères de succès
| Métrique | Baseline | Cible | Mesurée | Statut |
|----------|----------|-------|---------|--------|
| ... | ... | ... | ... | ✅ / ❌ |

### Kill switch
- Mécanisme : [nom du flag / env var]
- Testé (OFF/ON/OFF) : ✅
- État final : ON / retiré / laissé en place (permanent)

### Non-régression
- Suite tests : ✅ NNN verts
- Métriques secondaires : ✅ OK / ⚠️ (décrire)

### Fichiers créés / modifiés
- `src/...`
- `config/...`

### Écarts avec le plan
- ... ou "Aucun"

### Dette résiduelle
- ... ou "Aucune"

### Prochaines étapes
→ `/review` pour la code review
→ `/commit` pour commit et push
→ `/report` pour documenter l'exécution
→ `/sync` si le plan a dévié et mérite d'être réaligné
```

## Argument optionnel

`/tech docs/story/044-t-redis-cache/plan.md` — charge le plan et démarre.

`/tech redis-cache` — cherche le dossier `NNN-t-redis-cache` par slug et charge son `plan.md`.

`/tech` sans argument — liste les dossiers `NNN-t-*` contenant un plan.
