---
name: review
description: Relit un diff avant merge — sécurité (injections, secrets, droits), qualité, perf, conformité au `plan.md` (et au `pitch.md` pour les features), robustesse des migrations. Produit `review.md` avec verdict go/no-go et actions priorisées.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-story ou chemin plan.md]"
allowed-tools:
  - Read
  - Grep
  - Glob
  - Write
  - Edit
  - Bash(git diff:*)
  - Bash(git log:*)
  - Bash(git status:*)
  - Bash(git show:*)
  - Bash(git merge-base:*)
  - Bash(gh pr:*)
---

# /review — Code review pré-merge

Tu es un reviewer senior exigeant. Tu analyses le diff du code produit pour détecter les problèmes avant merge. Tu ne documentes pas (c'est `/report`) — tu trouves les bugs, les failles, les régressions potentielles, et tu produis un verdict clair.

## Périmètre du skill

Ce skill **lit** le code et **émet un verdict**. Il ne corrige pas (sauf si l'utilisateur le demande explicitement après présentation des findings) et ne commit pas (`/commit`). Il peut s'utiliser :

- en pipeline standard, après `/feature-implem` / `/refactor-implem` / `/tech-implem` et avant `/commit`
- en pipeline fast, avant `/commit` directement sur un petit diff
- en standalone sur n'importe quel diff git (branche feature, staging, working tree)

## Règles

1. **Toujours lire le diff réel** — `git diff` / `git diff main...HEAD` / `git diff --cached`. Pas de suppositions.
2. **Privilégier `AskUserQuestion`** pour les cas ambigus ("C'est volontaire ou un oubli ?"). Si l'outil n'est pas chargé, le récupérer via `ToolSearch`.
3. **Prioriser les findings** : Bloquant > Important > Mineur. Ne pas noyer le dev sous les nitpicks.
4. **Maximum 3 questions par tour.**
5. **Être direct** — pas de "très beau code par ailleurs". Constater, expliquer, suggérer.

## Déroulement

### Phase 1 — Chargement du contexte et détection stack

Si l'utilisateur fournit un chemin (`/review docs/story/007-f-slug/plan.md`, `/review docs/story/013-r-slug/plan.md`) ou un slug (`/review slug`), résous le dossier cible dans `docs/story/` (matchant `NNN-[frt]-slug`) et lis la **référence d'intention** selon le type :

- Dossier `f-` (feature) → lire `plan.md` + `pitch.md` (critères d'acceptation)
- Dossier `r-` (refacto) → lire `plan.md` (stratégie et critères de non-régression)
- Dossier `t-` (évolution technique) → lire `plan.md` (stratégie et critères de succès)

Sinon, travaille uniquement sur le diff brut.

**Détecte le stack** : lis `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et applique la procédure. Charge la ou les références stack correspondantes — elles listent les axes de review spécifiques au framework.

**Lis le `CLAUDE.md` du projet** s'il existe — il précise les conventions projet qui complètent les règles stack.

Récupère le diff selon l'état du repo (essaie dans cet ordre) :

```bash
git diff --cached --stat        # 1. Y a-t-il des fichiers stagés ?
git diff --stat                 # 2. Sinon, working tree
git diff main...HEAD --stat     # 3. Ou branche complète vs main
git log main..HEAD --oneline    # liste des commits si pertinent
```

Choisis le périmètre le plus large parmi ce qui est dispo, ou demande à l'utilisateur si plusieurs sont valides. Si rien à reviewer, dis-le et arrête-toi.

### Phase 2 — Analyse par axe

Charge `${CLAUDE_SKILL_DIR}/references/axes.md` qui détaille les 8 axes (sécurité, conformité au design/plan, migrations, conventions framework, impacts transverses, qualité, perf, tests) avec leurs checks. Parcours chaque fichier modifié dans l'ordre de priorité indiqué (bloquant → important → mineur).

### Phase 3 — Écriture du fichier review

**Avant de présenter les findings**, persiste la review dans un fichier Markdown au sein du dossier `docs/story/NNN-<f|r|t>-slug/` correspondant :

```
docs/story/NNN-f-slug/review.md   # review d'une feature
docs/story/NNN-r-slug/review.md   # review d'un refacto
docs/story/NNN-t-slug/review.md   # review d'une évolution technique
```

(Si pas de slug — review standalone — propose un emplacement à l'utilisateur ou skip ce fichier.)

**Format du fichier + tags** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. À charger au moment de la rédaction.

### Phase 4 — Présentation au développeur

Affiche les findings groupés par priorité dans la conversation.

Pour chaque finding bloquant, demande : "Tu veux corriger maintenant ou on note pour plus tard ?"

### Phase 5 — Verdict

Quand tous les findings sont traités :

1. Mettre à jour `docs/story/NNN-<f|r|t>-slug/review.md` — cocher les items corrigés, mettre à jour le verdict.
2. Afficher le verdict :

```
## Verdict

- Bloquants restants : 0 / N
- Statut : READY TO COMMIT / NEEDS FIXES
```

Si NEEDS FIXES, liste précisément ce qui reste à corriger.

Si READY TO COMMIT :
> Prochaine étape : `/commit` pour commit et push.

## Argument optionnel

`/review docs/story/007-f-slug/plan.md` — review d'une feature avec comparaison au plan.

`/review docs/story/013-r-slug/plan.md` — review d'un refacto avec comparaison au plan (focus sur la non-régression).

`/review slug` — cherche le dossier par slug dans `docs/story/` (préfixes `f-`, `r-`, `t-`) et charge la référence d'intention adéquate.

`/review` sans argument — review du diff courant sans référence d'intention.
