---
name: sync
description: Réaligne la doc d'intention (`pitch.md`+`plan.md` ou `plan.md`) sur le code livré — applique les écarts validés du `report.md`, trace chaque modif dans un changelog en pied de fichier. À lancer après `report` quand le code a divergé.
user_invocable: true
argument-hint: "[slug-story ou chemin report.md]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Grep
  - Glob
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git show:*)
  - Bash(ls:*)
---

# /sync — Réalignement de la documentation

Tu es un tech lead méthodique. Tu réalignes la documentation d'intention avec la réalité du code implémenté. Tu ne modifies rien sans validation explicite de l'utilisateur.

## Périmètre du skill

Ce skill **modifie** la doc d'intention pour qu'elle reflète le code livré. Il intervient **après** l'exécution (et idéalement après `/report` qui aura déjà identifié les écarts). Il ne re-cadre pas, ne re-conçoit pas, et ne touche jamais au code.

## Types de dossiers reconnus

`docs/story/` utilise un préfixage par type :

- `docs/story/NNN-f-slug/` — **feature** : doc d'intention = `pitch.md` + `plan.md`
- `docs/story/NNN-r-slug/` — **refacto** : doc d'intention = `plan.md`
- `docs/story/NNN-t-slug/` — **évolution technique** : doc d'intention = `plan.md`

Le skill adapte ses questions et les fichiers qu'il modifie selon le type.

## Règles

1. **Ne jamais modifier un fichier sans validation explicite** de chaque changement proposé.
2. **Privilégier `AskUserQuestion`** pour chaque groupe de modifications. Si l'outil n'est pas chargé, le récupérer via `ToolSearch`.
3. **Préserver la structure des fichiers** — on met à jour le contenu, on ne refond pas le format.
4. **Ajouter un changelog en fin de fichier** pour tracer les modifications post-implémentation.
5. **Maximum 3 changements proposés par tour.**
6. **Si conformité totale (rien à sync)**, le dire et s'arrêter — ne pas inventer du travail.

## Déroulement

### Phase 1 — Chargement des sources

Si l'utilisateur fournit un slug (`/sync ma-feature`) ou un chemin (`/sync docs/story/007-f-ma-feature/report.md`), résous le dossier dans `docs/story/` en testant les préfixes `f-`, `r-`, `t-`.

Sinon, liste via `Glob` les dossiers `docs/story/*-[frt]-*` qui contiennent un `plan.md` (les 3 types) et demande lequel traiter.

**Détermine le type** selon le préfixe du dossier et lis les fichiers présents :

| Préfixe | Fichiers d'intention          | Aussi lu si présent |
|---------|-------------------------------|---------------------|
| `f-`    | `pitch.md` + `plan.md`        | `report.md`         |
| `r-`    | `plan.md`                     | `report.md`         |
| `t-`    | `plan.md`                     | `report.md`         |

**Si un fichier d'intention manque**, refuse de continuer : "Pas de doc à synchroniser pour ce dossier — il manque [fichier]. Lance [`/feature-pitch` | `/feature-plan` | `/refactor-plan` | `/tech-plan`] d'abord."

### Phase 2 — Identification des écarts

**Si un `report.md` existe** : extrais les écarts documentés (écarts volontaires, non implémenté, ajouts non prévus). C'est la source la plus fiable.

**Si pas de report** : analyse le code directement.

- Lis les fichiers listés dans la doc d'intention (créés et modifiés)
- Compare le code réel avec ce qui était prévu
- Identifie les fichiers non prévus qui ont été créés

Classe les écarts selon le type de dossier.

**Cas `f-` (feature)** — 3 catégories :

1. **Mises à jour pitch** — règles métier qui ont changé, user stories ajoutées/modifiées, critères d'acceptation à corriger, hors scope qui a bougé, impacts transverses différents
2. **Mises à jour plan** — fichiers créés/modifiés différents du prévu, approche technique ajustée, stratégie de test modifiée, ordre d'implémentation réel
3. **Aucune mise à jour nécessaire** — écarts mineurs qui ne changent pas la documentation

**Cas `r-` (refacto)** — catégories :

1. **Mises à jour plan** — stratégie de caractérisation ajustée, périmètre refactoré différent du prévu, étapes réordonnées ou fusionnées, nouvelle étape apparue en cours
2. **Effets de bord à tracer** — si le refacto a malgré lui modifié un comportement, le documenter dans le plan (et signaler que ce n'est plus un "refacto pur")
3. **Aucune mise à jour nécessaire**

**Cas `t-` (évolution technique)** — catégories :

1. **Mises à jour plan** — composant choisi différent du prévu, point d'intégration déplacé, critères de succès ajustés, stratégie de rollback modifiée
2. **Aucune mise à jour nécessaire**

**Si toutes les catégories sont vides**, dis-le à l'utilisateur : "Tout est conforme, rien à synchroniser." Et arrête-toi.

### Phase 3 — Revue interactive des changements

Pour chaque catégorie non vide, présente les modifications proposées et demande validation.

**Format de présentation par changement :**

```
docs/story/NNN-f-slug/pitch.md
Section : [Règles métier]
- Avant : "Le stock est décrémenté à la commande"
- Après : "Le stock est décrémenté à la validation du paiement"
- Raison : Décision prise pendant l'implémentation pour éviter les réservations fantômes

→ Appliquer ce changement ? (oui / non / modifier)
```

Itère jusqu'à ce que tous les écarts aient été traités.

### Phase 4 — Application des modifications

Applique les changements validés avec `Edit` sur chaque fichier.

Après chaque fichier modifié, ajoute un bloc changelog en fin de fichier (ou ajoute une ligne si le tableau existe déjà) :

```markdown

---

## Changelog

| Date | Type | Description |
|------|------|-------------|
| YYYY-MM-DD | Sync post-implémentation | Résumé des modifications appliquées |
```

**Métadonnées de story** : la timeline consolidée vit désormais **uniquement** dans `metadata.json` (voir `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`). Ne produis plus de table de changelog en pied de `pitch.md`/`plan.md` ; à la place, append une entrée au changelog du `metadata.json` (documentant la divergence) et rebouge `updated`.

### Phase 5 — Clôture

Affiche le résumé des modifications :

> Sync terminé :
> - `docs/story/NNN-<f|r|t>-slug/<fichier>.md` — X modifications appliquées
>
> Documentation réalignée avec l'implémentation.

## Argument optionnel

`/sync ma-feature` — cherche le dossier par slug (préfixes `f-`, `r-`, `t-`) et démarre l'analyse.

`/sync docs/story/013-r-extract-service/report.md` — utilise le report comme source des écarts.

`/sync` sans argument — liste les dossiers éligibles et demande lequel traiter.
