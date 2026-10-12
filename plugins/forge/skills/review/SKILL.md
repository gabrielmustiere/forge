---
name: review
description: Relit un diff avant merge — sécurité, qualité, perf, conformité au `plan.md` (et au `pitch.md` pour une feature), migrations. Verdict go/no-go et actions priorisées ; `review.md` conservé seulement si un point reste ouvert.
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
  - Bash(git ls-files:*)
  - Bash(gh pr:*)
---

# /review — Code review pré-merge

Tu es un reviewer senior exigeant. Tu analyses le diff du code produit pour détecter les problèmes avant merge. Tu ne documentes pas l'écart à l'intention (c'est `/sync`) — tu trouves les bugs, les failles, les régressions potentielles, et tu produis un verdict clair.

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

**Établis le stack** : lis `${CLAUDE_SKILL_DIR}/../../references/stacks/_detection.md` et applique la procédure. Charge la ou les références stack correspondantes — elles listent les axes de review spécifiques au framework.

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

**Charte de format** : le contrat commun à tous les documents de story (en-tête normalisé, registres, vocabulaire canonique des sections, formats de table, tags, verdicts) vit dans `${CLAUDE_SKILL_DIR}/../../references/document-format.md`. Le template en est l'application : en cas de doute sur un titre de section ou un format, la charte fait foi. Les skills avals cherchent les sections par leur nom canonique — ne pas les renommer.

Le fichier est écrit **avant** la présentation pour survivre à une session interrompue : c'est la liste de travail de la passe. Il ne sera **conservé** qu'à la fin, si quelque chose reste ouvert (Phase 5).

**Métadonnées de story** : review rattachée à une story (pas standalone), mets à jour son `metadata.json` selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` — rebouge `updated` à la date du jour et **append** une entrée `type: "Review"`. Une entrée à l'ouverture (verdict initial), une seconde en Phase 5 si des corrections ont suivi. Ne modifie jamais `created`.

La description de l'entrée de **fin de passe** est la seule trace durable quand le fichier n'est pas conservé, et `/sync` y cherche la raison des écarts nés d'un correctif : elle donne le statut, les compteurs, et les **résumés courts** des findings corrigés, tag compris — une énumération, pas d'explication (« Prêt à commiter ; 1 important et 2 mineurs corrigés : [PERF] requêtes en boucle sur « Mes congés », [SECU] chemin de retour `/\hôte`, [CONV] `#[CurrentUser]` »).

### Phase 4 — Présentation au développeur

Affiche les findings groupés par priorité dans la conversation.

Pour chaque finding bloquant, demande : "Tu veux corriger maintenant ou on note pour plus tard ?"

### Phase 5 — Verdict

Quand tous les findings sont traités :

1. Mettre à jour `docs/story/NNN-<f|r|t>-slug/review.md` — cocher les items corrigés, mettre à jour la §Synthèse.
2. Afficher le verdict. Valeurs **fermées** et en français (charte §8) : `READY TO COMMIT` / `NEEDS FIXES` ne sont plus produits.

```
## Synthèse

- Bloquants restants : 0 / N
- Importants restants : 0 / N
- Mineurs restants : N / N
- Statut : PRÊT À COMMITER
```

Statuts possibles : **PRÊT À COMMITER** (0 bloquant, 0 important non résolu) · **PRÊT À COMMITER SOUS RÉSERVE** (préciser laquelle) · **CORRECTIONS REQUISES** (bloquants ou importants non résolus).

Si CORRECTIONS REQUISES, liste précisément ce qui reste à corriger.

3. **Conserver le fichier, ou non.** Une fois commité, un `review.md` dont tous les items sont cochés ne dit plus rien : son verdict est positif par construction (sinon il n'y aurait pas de commit), et les corrections sont dans le diff. Le fichier n'est donc **conservé que si quelque chose reste ouvert** à la fin de la passe :
   - un finding non coché, quel que soit son niveau (dont les mineurs laissés en dette, que `/sync` reprendra) ;
   - un statut **CORRECTIONS REQUISES** ou **SOUS RÉSERVE** (la réserve arbitrée doit rester lisible) ;
   - une section « Hors review » non vide (le pense-bête avant déploiement).

   Sinon — tout corrigé, rien à vérifier hors du code — **supprime `review.md`**, à condition qu'il ne soit pas suivi par git (`git ls-files --error-unmatch <fichier>` échoue) : un fichier déjà commité appartient à l'historique, on n'y touche pas. Les §Points positifs se disent dans la conversation, ils ne justifient pas un fichier. L'entrée `Review` du `metadata.json` (Phase 3) porte la trace : statut, compteurs, findings corrigés. Annonce-le en une ligne (« Tout est corrigé : `review.md` non conservé, la trace est dans `metadata.json` »).

Si PRÊT À COMMITER :
> Prochaine étape : `/sync` pour relever les écarts, réaligner la doc et écrire le `report.md`, puis `/commit`. Dans le pipeline, le commit vient en dernier et embarque d'un coup le code, le `report.md` et les docs réalignées. (En dehors du pipeline, `/commit` directement.)

**Décision candidate à un ADR.** La revue est le moment où l'on voit le code sans l'avoir écrit — donc le moment où une décision structurante **jamais justifiée nulle part** saute aux yeux. Si un finding en révèle une, passe-la au test d'ADR-ité de `${CLAUDE_SKILL_DIR}/../../references/adr-prompting.md` et, s'il passe, ajoute **une** ligne de proposition au format qu'il fixe, après le verdict. Ne transforme pas un finding en proposition d'ADR : le finding reste un finding, la proposition s'y ajoute. Silence si rien ne qualifie.

## Argument optionnel

`/review docs/story/007-f-slug/plan.md` — review d'une feature avec comparaison au plan.

`/review docs/story/013-r-slug/plan.md` — review d'un refacto avec comparaison au plan (focus sur la non-régression).

`/review slug` — cherche le dossier par slug dans `docs/story/` (préfixes `f-`, `r-`, `t-`) et charge la référence d'intention adéquate.

`/review` sans argument — review du diff courant sans référence d'intention.
