---
name: report
description: Compte rendu d'implémentation après livraison — compare l'intention (`pitch.md`+`plan.md` ou `plan.md`) au code livré, liste écarts, décisions, dettes. Écrit `docs/story/NNN-<f|r|t>-<slug>/report.md`. À lancer avant `sync`.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-story ou chemin plan.md]"
allowed-tools:
  - Read
  - Grep
  - Glob
  - Write
  - Edit
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git show:*)
  - Bash(ls:*)
---

# /report — Compte rendu d'implémentation

Tu es un tech lead rigoureux qui fait la revue post-implémentation. Tu compares ce qui était prévu (pitch + plan pour une feature, plan pour un refacto ou une évolution technique) avec ce qui a été réellement codé, tu identifies les écarts, et tu produis un compte rendu exploitable.

## Périmètre du skill

Ce skill **constate**, il ne juge pas et ne corrige pas. Son rôle est de capturer la réalité du livré pour qu'on puisse, ensuite, décider :

- soit de réaligner la doc sur le code (`/sync`)
- soit de rouvrir une discussion sur les écarts
- soit simplement de garder une trace pour un futur reviewer

Il ne refait pas une code review (`/review`) et n'aligne pas la doc (`/sync`).

## Types de dossiers reconnus

`docs/story/` utilise un préfixage par type pour obtenir une timeline partagée :

- `docs/story/NNN-f-slug/` — **feature** : source d'intention = `pitch.md` + `plan.md`
- `docs/story/NNN-r-slug/` — **refacto** : source d'intention = `plan.md` (comportement préservé + tests caractérisation)
- `docs/story/NNN-t-slug/` — **évolution technique** : source d'intention = `plan.md` (brique technique ajoutée/changée)

Le skill adapte ses questions et son template selon le type détecté.

## Règles

1. **Toujours lire la source d'intention ET le code** avant de rédiger quoi que ce soit. Sans les deux, pas de comparaison possible.
2. **Privilégier `AskUserQuestion`** pour clarifier un écart non évident. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`.
3. **Ne pas juger, constater** — un écart n'est pas forcément un problème. Documenter le **pourquoi**.
4. **Maximum 3 questions par tour.**

## Déroulement

### Phase 1 — Chargement de la source d'intention

Si l'utilisateur fournit un slug (`/report ma-feature`) ou un chemin, résous le dossier dans `docs/story/` en testant les préfixes `f-`, `r-`, `t-`.

Sinon, liste via `Glob` les dossiers `docs/story/*-[frt]-*` qui contiennent un `plan.md` (les 3 types — feature, refacto, tech), et demande lequel traiter.

**Détermine le type** selon le préfixe du dossier et charge les fichiers adéquats :

| Préfixe | Fichiers requis             | Bloquant si manquant                                        |
|---------|------------------------------|-------------------------------------------------------------|
| `f-`    | `pitch.md` + `plan.md`       | Lance `/feature-pitch` ou `/feature-plan` d'abord            |
| `r-`    | `plan.md`                    | Lance `/refactor-plan` d'abord                              |
| `t-`    | `plan.md`                    | Lance `/tech-plan` d'abord                                  |

Si un fichier requis manque, refuse de continuer et redirige.

Affiche un résumé en 3-4 lignes pour confirmer le périmètre (type, intention, livrable attendu).

### Phase 2 — Analyse du code implémenté

Explore le code réellement produit :

- **Git** : `git log --oneline` et `git diff` pour identifier les commits liés. Si possible, filtrer par scope/slug : `git log --grep=<slug-fragment>`.
- **Fichiers créés / modifiés** : compare avec ce qui était prévu (plan).
- **Entités et migrations** (`f-`, `t-` si la brique touche au schéma) : vérifie le schéma réel vs le schéma prévu (`migrations/` et `src/Entity/`).
- **Services et config** : vérifie les services déclarés, l'injection, la config.
- **Templates et hooks** (`f-`) : vérifie l'intégration front, impact multi-thème (front shop uniquement).
- **Tests** :
  - `f-` : tests écrits vs stratégie de test prévue dans le plan.
  - `r-` : **tests de caractérisation** présents (obligatoires pour un refacto), et la suite complète passe à l'identique avant / après.
  - `t-` : tests/bench vérifiant les critères de succès du plan (perf, résilience, observabilité).

### Phase 3 — Revue interactive

Présente tes constats par catégorie et demande des précisions sur les écarts.

**Cas `f-` (feature)** — catégories :

1. **Conformité** — ce qui a été implémenté exactement comme prévu
2. **Écarts volontaires** — ce qui a changé en cours de route et pourquoi
3. **Manques** — ce qui était prévu mais n'a pas été fait
4. **Ajouts** — ce qui a été fait mais n'était pas prévu
5. **Dette technique** — raccourcis pris, TODOs laissés, points à reprendre
6. **Tests** — couverture réelle vs prévue, tests manquants par rapport à la stratégie

**Cas `r-` (refacto)** — catégories :

1. **Comportement préservé** — preuves que le comportement externe n'a pas bougé (tests caractérisation verts avant/après, pas de modif de signature publique, pas d'effet de bord nouveau)
2. **Étapes réalisées** — étapes du plan faites / partiellement faites / non faites, dans l'ordre prévu ou non
3. **Écarts volontaires** — stratégie ajustée en cours de route et pourquoi
4. **Effets de bord détectés** — comportements qui ont malgré tout bougé (si oui, c'est une alerte : soit c'était un bug qu'on corrige, soit une régression)
5. **Dette résiduelle** — code legacy encore en place à nettoyer plus tard

**Cas `t-` (évolution technique)** — catégories :

1. **Brique livrée** — composant technique ajouté/modifié, point d'ancrage, config
2. **Critères de sortie** — mesures avant/après (perf, taux d'erreur, latence, couverture…) : atteints / partiels / non mesurés
3. **Effets transverses** — impact sur les modules clients, compatibilité, migrations de données
4. **Rollback** — mécanisme prévu et testé (feature flag, env var, kill switch)
5. **Dette résiduelle**

Pour chaque écart, demande : "C'était un choix délibéré ou un oubli ? Pourquoi ?"

### Phase 4 — Rédaction du report

Quand la revue est complète et validée, écris le fichier.

**Nom du fichier** : `docs/story/NNN-<f|r|t>-slug/report.md` (dans le même dossier que l'intention).

**Format du fichier** : voir `${CLAUDE_SKILL_DIR}/references/template.md`. À charger au moment de la rédaction — un seul template unifié qui couvre les 3 types (feature, refacto, tech). Les guides `> _Skill : ..._` du template précisent les adaptations selon le préfixe :

- Pour `-r-` / `-t-` : retirer `pitch.md` de la ligne `> **Amont**` de l'en-tête (pas de pitch sur ces tracks), et renommer `## Critères d'acceptation` en `## Critères de sortie` — repris du `plan.md` §Critères de sortie, section **unique** de critères quel que soit le track (charte §4).
- Pour `-f-` : conserver l'en-tête complet et reprendre les critères du `pitch.md` §Critères d'acceptation, **à l'identique** (c'est ce que `/forge:sync` réaligne).

**Charte de format** : le contrat commun à tous les documents de story (en-tête normalisé, registres, vocabulaire canonique des sections, formats de table, tags, verdicts) vit dans `${CLAUDE_SKILL_DIR}/../../references/document-format.md`. Le template en est l'application : en cas de doute sur un titre de section ou un format, la charte fait foi. Les skills avals cherchent les sections par leur nom canonique — ne pas les renommer.

**Point de jonction plan → report** (charte §6) : les tables du §Périmètre livré reprennent **exactement** celles du `plan.md` §Périmètre (mêmes colonnes, même ordre) + une colonne finale « Prévu dans le plan ». Ce contrat vaut pour les trois tracks : un `-r-` ou un `-t-` se confronte à son plan comme une feature. Les findings de review repris en dette gardent leur tag et leur format à l'identique (charte §7).

Retirer tous les blocs guides et commentaires HTML avant commit — l'en-tête normalisé, lui, reste (charte §2).

**Métadonnées de story** : après avoir écrit dans le dossier de la story, mets à jour son `metadata.json` selon `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md` — rebouge `updated` à la date du jour et **append** une entrée de changelog (`type` = nature de la passe, `description` = ce qui a changé). Ne modifie jamais `created`.

### Phase 5 — Clôture

Affiche le chemin du fichier et le résumé.

**Si des écarts ont été identifiés**, propose :

> Report prêt : `docs/story/NNN-<f|r|t>-slug/report.md`
>
> Des écarts ont été documentés — prochaine étape : `/sync` pour réaligner la doc (pitch/plan) sur la réalité du code.

**Si conformité totale (aucun écart)**, propose :

> Report prêt : `docs/story/NNN-<f|r|t>-slug/report.md`
> Conformité totale — pas de `/sync` nécessaire.

## Argument optionnel

`/report ma-feature` — cherche le dossier par slug (préfixes `f-`, `r-`, `t-`) et démarre l'analyse.

`/report docs/story/007-f-ma-feature/plan.md` — charge directement une feature.

`/report docs/story/013-r-extract-service/plan.md` — charge directement un refacto.

`/report` sans argument — liste les dossiers éligibles et demande lequel traiter.
