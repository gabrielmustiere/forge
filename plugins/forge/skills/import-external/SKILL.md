---
name: import-external
description: Importe une doc externe (Spec Kit, BMAD-METHOD, GSD) vers `docs/story/NNN-<f|r|t>-<slug>/`. Détecte la source, mappe les sections vers `pitch.md`/`plan.md` avec validation, copie via `git mv` pour préserver l'historique.
user_invocable: true
disable-model-invocation: true
argument-hint: "[spec-kit | bmad | gsd]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Grep
  - Glob
  - Bash(ls:*)
  - Bash(find:*)
  - Bash(mkdir:*)
  - Bash(cp:*)
  - Bash(git mv:*)
  - Bash(git add:*)
---

# /import-external — Import depuis Spec Kit, BMAD-METHOD ou GSD

Ce skill fait passer un projet d'un autre framework spec-driven (Spec Kit, BMAD-METHOD, get-shit-done) vers le format `forge` : `docs/story/NNN-<f|r|t>-<slug>/` avec `pitch.md`+`plan.md` pour une feature, `plan.md` seul pour un refacto ou une évolution tech.

C'est une opération d'import, pas un pont bidirectionnel. L'objectif : récupérer le contenu existant dans la nomenclature workflow pour pouvoir continuer avec `/feature-implem`, `/refactor-implem`, `/tech-implem`, etc. La doc d'origine est **conservée** (déplacée dans `_archive/`, pas supprimée) au cas où l'utilisateur veut comparer.

## Périmètre

- **Détection automatique** de la source via la présence de répertoires/fichiers caractéristiques.
- **Mapping fichier-à-fichier** documenté pour chaque source (voir références).
- **Choix du tag** (`f`/`r`/`t`) interactif, avec heuristique sur les mots-clés du titre.
- **Compteur global** : si certaines stories ont déjà un numéro (Spec Kit), on **réutilise**. Sinon (BMAD, GSD), on alloue séquentiellement.
- **Pas de modification de contenu** au-delà du strict minimum (renommage de sections type `## Spec` → `## Spec` reste tel quel ; le fichier renommé `spec.md` → `pitch.md` garde son texte intégral).

## Sources supportées

Trois références détaillées, **bundlées avec la skill** — lis celle qui correspond à la source détectée via `Read` sur `${CLAUDE_SKILL_DIR}/references/<fichier>` (jamais un chemin relatif au projet) :

| Source | Référence | Indice de détection |
|--------|-----------|---------------------|
| Spec Kit (GitHub) | `spec-kit.md` | `specs/NNN-<slug>/` avec `spec.md`+`plan.md` ; ou `.specify/` à la racine |
| BMAD-METHOD | `bmad.md` | `docs/stories/<epic>.<story>*.md` ; ou `docs/prd.md`+`docs/architecture.md` |
| GSD (get-shit-done) | `gsd.md` | `.plans/<id>-<slug>.md` (fichiers plats) ; ou `.gsd/` |

Si la source ne matche aucune des trois : « Je ne reconnais pas le format source. Décris-moi la structure (`tree -L 3 docs/ specs/ .plans/ 2>/dev/null`) et on improvise un mapping manuel. »

## Règles du mode interactif

1. **Lecture seule en phase de plan** — n'écrire ni renommer aucun fichier avant validation explicite ("go", "exécute") du plan complet.
2. **Vérifier l'état du repo** — abandonner si l'index n'est pas propre, pour que l'import soit un commit isolé.
3. **Une seule source à la fois** — si plusieurs frameworks coexistent (rare), demander lequel migrer en premier. Mélanger casse les heuristiques.
4. **Préserver l'historique** — utiliser `git mv` quand c'est faisable pour qu'un `git log --follow` retrouve le contenu d'origine.

## Déroulement

### Phase 1 — Détection de la source

```bash
# Spec Kit
[ -d ".specify" ] || ls -d specs/[0-9][0-9][0-9]-* 2>/dev/null | head -1

# BMAD
[ -f "docs/prd.md" ] || ls docs/stories/*.story.md 2>/dev/null | head -1

# GSD
[ -d ".gsd" ] || [ -d ".plans" ]
```

Annonce ce que tu as trouvé : « Source détectée : **Spec Kit** (8 dossiers sous `specs/`). Tu confirmes que c'est bien ça qu'on importe ? »

Charge la référence correspondante (`${CLAUDE_SKILL_DIR}/references/<source>.md`) et **suis-la** pour le mapping fichiers.

### Phase 2 — Inventaire et tagging

Pour chaque story source, construis un enregistrement :

```
- source : specs/003-checkout-express/
- slug : checkout-express
- numéro source : 003
- titre détecté (depuis spec.md ou frontmatter) : "Express checkout"
- tag proposé : f  (heuristique : mots-clés "feature", "user story", "checkout")
- destination : docs/story/003-f-checkout-express/
- mapping fichiers :
    spec.md   →  pitch.md
    plan.md   →  plan.md
    tasks.md  →  _archive/tasks.md
```

**Heuristique de tagging** (le skill propose, l'utilisateur confirme/corrige) :

- **`r`** (refacto) : titre/contenu mentionne *refactor*, *refacto*, *cleanup*, *extract*, *split class*, *modernize*, *rename*, *dead code*.
- **`t`** (tech) : titre/contenu mentionne *cache*, *retry*, *circuit breaker*, *queue*, *async*, *index*, *migration*, *health check*, *latency*, *performance*, *observability*, *log*, *security*, *CSP*, *CVE*.
- **`f`** (feature) : par défaut.

Présente un tableau récapitulatif :

```
| #  | Source                          | Tag proposé | Destination                        |
|----|---------------------------------|-------------|------------------------------------|
| 1  | specs/001-user-login            | f           | docs/story/001-f-user-login        |
| 2  | specs/002-extract-auth-service  | r           | docs/story/002-r-extract-auth-service |
| 3  | specs/003-redis-cache           | t           | docs/story/003-t-redis-cache       |
```

Demande : « Les tags te paraissent corrects ? Tu peux corriger ligne par ligne (ex: "ligne 2 c'est plutôt un t"), ou valider le lot. »

### Phase 3 — Allocation des numéros

**Si la source utilise déjà un compteur** (Spec Kit séquentiel) : on **réutilise** les numéros pour préserver les références dans les commits/branches existants. Vérifie qu'il n'y a pas de collision avec des dossiers `docs/story/` existants — si oui, demande quelle politique appliquer (renuméroter les nouveaux ou les anciens).

**Si la source n'a pas de compteur** (BMAD avec format `<epic>.<story>`, GSD avec timestamps/issues) : alloue séquentiellement à partir de `max(numéros existants dans docs/story/) + 1`. L'ordre est dicté par la date de modification du fichier source (essaie `git log --diff-filter=A --format=%ad --date=iso -- <source>` pour récupérer la date de création ; à défaut, `stat`).

### Phase 4 — Plan complet et validation

Récapitule tout :

> Import depuis **Spec Kit** :
> - 8 stories à importer
> - Compteur source réutilisé (numéros 001-008)
> - Tags : 6 × f, 1 × r, 1 × t (modifie si besoin)
> - Fichiers d'origine déplacés vers `_archive/spec-kit/` (conservés)
> - `tasks.md` archivés (pas d'équivalent dans le format workflow)
>
> Confirme pour exécuter ("go", "exécute", "c'est bon").

### Phase 5 — Exécution

Pour chaque story :

1. Crée le dossier de destination : `mkdir -p docs/story/<NNN>-<tag>-<slug>/`
2. Déplace les fichiers mappés via `git mv` (préserve l'historique).
3. Renomme selon le mapping (ex: `git mv specs/003-x/spec.md docs/story/003-f-x/pitch.md`).
4. Archive les fichiers non mappés : `git mv <fichier> _archive/<source>/<NNN>-<slug>/<fichier>`.
5. Si la source était un fichier plat (GSD `.plans/<id>-<slug>.md`), le contenu va dans `pitch.md` ou `plan.md` selon le tag — voir `${CLAUDE_SKILL_DIR}/references/gsd.md`.

À la fin :

```bash
ls -1 docs/story/ | head -20  # vérifier le tri chronologique
```

### Phase 6 — Bilan

Affiche :

> Import terminé :
> - 8 stories importées dans `docs/story/`
> - 1 dossier `_archive/spec-kit/` créé (à committer ou .gitignore selon ta préférence)
> - Tu peux maintenant utiliser `/feature-implem`, `/refactor-implem`, `/tech-implem` sur ces stories existantes
>
> Recommandation : commit isolé `chore(docs): import depuis Spec Kit`. Je peux le faire si tu veux.

**Ne commit pas sans validation explicite.**

## Pièges courants

- **Numéros qui dépassent 999** : Spec Kit accepte des timestamps `20251225-143022-x` comme numéros. Dans ce cas, demande à l'utilisateur s'il veut renuméroter séquentiellement (recommandé : `001-f-x`, `002-f-y`, …) ou tronquer à 3 chiffres (risqué : collisions possibles).
- **Stories BMAD au format `<epic>.<story>`** : `1.1.story.md`, `1.2.story.md`, `2.1.story.md` n'ont pas de compteur global. On alloue séquentiellement et on garde la trace de l'epic dans le **slug** (ex: `001-f-epic-1-user-login`).
- **GSD avec un seul fichier plat** : `.plans/1755-install-audit-fix.md` devient `docs/story/<NNN>-<tag>-install-audit-fix/pitch.md` ou `plan.md`. Le numéro `1755` (souvent un issue GitHub) est mentionné dans le **header** du fichier importé pour traçabilité, pas dans le nom du dossier.
- **Conflit avec stories workflow déjà présentes** : si `docs/story/` contient déjà du contenu workflow, l'import doit s'**ajouter** (suite logique de la numérotation), pas écraser. Vérifier collisions avant chaque `git mv`.

## Substitutions disponibles

`$ARGUMENTS` — force la source si la détection est ambiguë (`/import-external spec-kit`, `/import-external bmad`, `/import-external gsd`).
