---
name: commit
description: "Commit Conventional Commits v1.0.0 en français depuis le diff courant — analyse, génère `type(scope): sujet` (corps si pertinent), commit, rebase (jamais de merge), puis push. Bloque sur secrets, code de debug ou conflit de rebase."
user_invocable: true
disable-model-invocation: true
argument-hint: "[--no-push] [--amend]"
allowed-tools:
  - Read
  - Bash(git status:*)
  - Bash(git diff:*)
  - Bash(git log:*)
  - Bash(git add:*)
  - Bash(git commit:*)
  - Bash(git push:*)
  - Bash(git fetch:*)
  - Bash(git pull:*)
  - Bash(git rebase:*)
  - Bash(git rev-parse:*)
  - Bash(git symbolic-ref:*)
  - Bash(git for-each-ref:*)
  - Bash(git merge-base:*)
  - Bash(git branch:*)
  - Bash(git config:*)
  - Bash(git ls-remote:*)
  - Bash(git remote:*)
---

# /commit — Commit, sync rebase & push (autonome)

Tu es un développeur rigoureux qui produit des commits propres et traçables. **Tu es autonome** : tu n'attends pas de validation pour committer ou pusher tant qu'aucun problème de sécurité ou de cohérence ne le justifie.

## Périmètre

Ce skill **commit, synchronise et push**. Il ne fait pas la code review (`/review`), ne corrige pas le style, ne documente pas (`/report`). Il **garantit zéro commit de merge** : la synchronisation passe toujours par `git fetch` + `git rebase`, jamais par un `git pull` qui pourrait produire un merge.

## Garanties non négociables (blocage si violées)

1. **Aucun fichier sensible commité** (`.env*`, `*.pem`, `*.key`, `credentials*`, `*token*`, `id_rsa*`, fichiers contenant des secrets détectables) → arrêt immédiat, exclusion proposée.
2. **Aucun code de debug commité** (PHP : `dump()` / `var_dump()` / `dd()` / `xdebug_break()` ; JS/TS : `console.log` / `console.debug` / `debugger` ; Python : `print(` / `breakpoint()` ; Go : `fmt.Println` ajouté localement) → arrêt immédiat.
3. **Aucun fichier temporaire** (`.playwright-mcp/`, `*.log`, screenshots, dumps, artefacts CI locaux) → arrêt immédiat, exclusion proposée.
4. **Aucun commit de merge** — la sync passe toujours par rebase. Si un rebase rencontre un conflit, **arrêt** et remontée à l'utilisateur (jamais d'auto-résolution).
5. **Jamais `--no-verify`** — un hook qui échoue, on corrige le problème de fond ou on arrête.
6. **Jamais `--force`** — uniquement `--force-with-lease` après un rebase d'une branche déjà publiée, et uniquement si ce skill a effectué ce rebase dans la session courante.
7. **Jamais `--amend` un commit déjà pushé** — `/commit --amend` est refusé si `HEAD` existe sur le remote.

## Autonomie

Le skill **ne demande aucune validation** pour :
- Le message de commit (il est généré et utilisé directement).
- L'exécution de `git add` sur les fichiers détectés comme pertinents.
- L'exécution du commit.
- Le fetch et le rebase de synchronisation.
- Le push final.

Le skill **demande arbitrage** uniquement quand :
- Une garantie ci-dessus est violée (secret, debug, merge commit, conflit).
- Le diff couvre plusieurs intentions distinctes et nécessite un découpage.
- `--amend` est demandé mais le commit est déjà publié.

## Format Conventional Commits v1.0.0

```
type(scope): description courte à l'impératif en français

Body (si > 1 changement significatif) :
- Pourquoi ce changement
- Ce qui a été ajouté/modifié
- Impacts techniques ou fonctionnels

BREAKING CHANGE: description (si applicable)
```

### Types

| Type       | Usage                                        |
|------------|----------------------------------------------|
| `feat`     | Nouvelle fonctionnalité                      |
| `fix`      | Correction de bug                            |
| `docs`     | Documentation uniquement                     |
| `style`    | Formatage, ECS, pas de changement de logique |
| `refactor` | Refactoring sans changement de comportement  |
| `perf`     | Amélioration de performance                  |
| `test`     | Ajout ou modification de tests               |
| `chore`    | Maintenance, config, dépendances, CI         |

### Règles de rédaction

- **Description** : impératif présent, français, intention pas implémentation.
    - Bon : `feat(product): ajouter le filtre par disponibilité`
    - Mauvais : `feat(product): ajout d'un QueryBuilder avec WHERE sur inStock`
- **Scope** : domaine métier/technique déduit du chemin (`product`, `order`, `cart`, `auth`, `migration`, `theme`, `config`…).
- **Body** : liste si > 2 points, paragraphe sinon. Omis si le sujet suffit.
- **Breaking change** : footer `BREAKING CHANGE:` si rupture (API, schéma, config).

## Déroulement

### Phase 1 — Analyse du diff

```bash
git status --porcelain=v1
git diff --cached --stat
git diff --cached
git diff --stat
git diff
```

- Si **rien à committer** (ni stagé ni working tree) → afficher l'état et arrêter.
- Si **changements présents** : analyser.

**Vérifications bloquantes** (cf. garanties 1–3) : parcourir le diff pour détecter secrets, code de debug, fichiers temporaires. Si présent → arrêter avec la liste précise des fichiers/lignes et demander : `corriger` (sortir pour que l'utilisateur retire) ou `exclure` (proposer le subset propre à committer).

### Phase 2 — Type, scope, découpage

1. **Type** dominant déduit de la nature des changements.
2. **Scope** déduit du chemin (`src/Entity/Product/*` → `product`, `config/packages/*` → `config`, `migrations/*` → `migration`, etc.).
3. **Découpage** : si le diff mélange plusieurs intentions distinctes (ex: un `feat` + un `fix` non liés, ou refacto + feat sur un autre domaine), **proposer** un découpage en plusieurs commits successifs et demander confirmation du plan. Sinon, un seul commit.

### Phase 3 — Génération du message et commit

Générer le message Conventional Commits **et l'utiliser directement**, sans demander validation. Afficher le message exécuté.

```bash
git add <fichiers pertinents>        # staging sélectif, jamais -A ni .
git commit -m "<message>"            # HEREDOC pour les messages multi-lignes
```

Si le commit échoue (hook pre-commit) :

- Lire l'erreur.
- Corriger le problème de fond (lint, types, tests selon l'outillage du projet) **dans la limite du raisonnable**. Si la correction sort du périmètre d'un commit (refacto profond, échec test non lié) → arrêter et remonter.
- Re-stager + **nouveau** commit (jamais `--amend` sauf demande explicite).

### Phase 4 — Synchronisation (rebase strict, zéro merge)

Cette phase est **systématique** avant tout push.

```bash
git fetch origin --prune
BRANCH=$(git rev-parse --abbrev-ref HEAD)
DEFAULT=$(git symbolic-ref --quiet --short refs/remotes/origin/HEAD | sed 's@^origin/@@' || echo main)
```

**Cas A — Branche par défaut (souvent `main` ou `master`)** :

```bash
# Rebase de HEAD sur origin/<DEFAULT> pour récupérer les commits remote sans merge
if git rev-parse --verify "origin/$BRANCH" >/dev/null 2>&1; then
  git rebase "origin/$BRANCH"
fi
```

**Cas B — Branche de feature/refacto/tech** :

```bash
# 1) Aligner sur l'upstream de la branche si elle existe (récupère les nouveaux commits poussés par d'autres)
if git rev-parse --verify "origin/$BRANCH" >/dev/null 2>&1; then
  git rebase "origin/$BRANCH"
fi

# 2) Rebase sur la base d'intégration (souvent main) pour garder la branche à jour avec la cible de merge
git rebase "origin/$DEFAULT"
```

**Règles de conflit** :

- Si `git rebase` produit un conflit : **arrêt immédiat**. Afficher la liste des fichiers en conflit, le rebase en cours (`git status`) et demander instruction. Ne **jamais** tenter de résoudre automatiquement, ne **jamais** `git rebase --abort` sans demande explicite.

**Détection d'un rebase qui a réécrit l'historique déjà publié** :

```bash
# Mémoriser si la branche existait sur le remote avant rebase (Phase 4 doit le savoir).
# Si oui ET que le SHA HEAD a changé pendant le rebase → push en --force-with-lease au lieu de push standard.
```

### Phase 5 — Push

Sauf `--no-push` :

```bash
# Si la branche n'a pas d'upstream tracké, on crée le tracking au premier push.
if git rev-parse --abbrev-ref --symbolic-full-name '@{u}' >/dev/null 2>&1; then
  if [ "$REWROTE_PUBLISHED_HISTORY" = "1" ]; then
    git push --force-with-lease origin "$BRANCH"
  else
    git push origin "$BRANCH"
  fi
else
  git push -u origin "$BRANCH"
fi
```

Si le push échoue malgré le rebase préalable (race avec un autre push entre fetch et push) :

- Re-jouer **une seule fois** Phase 4 puis Phase 5.
- Si ça échoue encore → arrêter et remonter (probable conflit ou protection de branche).

**Jamais `--force` nu, jamais bypass de protection de branche.**

### Phase 6 — Résumé

```
## Commit & push terminé

- Branche      : <branch>
- Commit(s)    : <sha1>[, <sha2>…]
- Message      : <type(scope): description>
- Fichiers     : N modifiés
- Sync         : rebase sur origin/<branch> + origin/<default> (ou skip si non applicable)
- Push         : ✅ (standard | --force-with-lease) | ❌ --no-push
```

**Métadonnées de story** : après le commit, renseigne `delivery.commit` (SHA court) dans le `metadata.json` des stories livrées, selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`, puis rebouge `updated` et append une entrée `type: "Livraison"`. Si `delivery` est absent, crée-le avec `release: null`.

> Prochaine étape : `/report` pour documenter l'implémentation.

## Arguments

- `/commit` — flux complet autonome : analyse → commit → fetch+rebase → push.
- `/commit --no-push` — commit + rebase de sync local, **pas** de push.
- `/commit --amend` — amende le dernier commit local. **Refusé** si ce commit est déjà sur le remote (`git branch --contains HEAD -r` renvoie au moins une réf `origin/*`).
