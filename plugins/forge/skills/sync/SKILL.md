---
name: sync
description: Réaligne la doc d'intention (`pitch.md`/`plan.md`) ET les documents projet (`vision.md`/`stack.md`/`product-backlog.md`) sur le code livré — applique les écarts du `report.md` puis propage aux docs de phase 0 via leurs modes Enrichir/Éditer. À lancer après `report` quand le code a divergé.
user_invocable: true
disable-model-invocation: true
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

Ce skill **modifie** la documentation pour qu'elle reflète le code livré, à deux niveaux :

1. **La doc d'intention de la story** (`pitch.md`/`plan.md`) — le cœur du skill (Phases 1 → 4).
2. **Les documents projet de phase 0** (`vision.md`, `stack.md`, `product-backlog.md`) — une story livrée les fait souvent dériver ; la Phase 5 propage les écarts vers eux en réutilisant leurs modes **Enrichir/Éditer** (mises à jour ciblées et validées, pas de recartographie).

Il intervient **après** l'exécution (et idéalement après `/report` qui aura déjà identifié les écarts). Il ne re-cadre pas, ne re-conçoit pas, et ne touche jamais au code.

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
4. **Tracer** chaque modification de la doc d'intention dans le `changelog` du `metadata.json` de la story — jamais en pied de `pitch.md`/`plan.md`. Les **documents projet** (Phase 5), eux, tracent dans **leur propre changelog** (celui de `vision.md`/`stack.md`/`product-backlog.md`).
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

1. **Mises à jour pitch** — §Règles métier qui ont changé, §User Stories ajoutées/modifiées, §Critères d'acceptation à corriger, §Hors scope qui a bougé, §Impacts transverses différents
2. **Mises à jour plan** — §Périmètre (fichiers créés/modifiés différents du prévu), §Approche retenue ajustée, §Stratégie de test modifiée, §Ordre d'exécution réel, §Critères de sortie
3. **Aucune mise à jour nécessaire** — écarts mineurs qui ne changent pas la documentation

**Jamais réalignée** : l'§Annexe — Pistes pour le plan du `pitch.md` est **non contractuelle** (charte §3). Elle capture ce qu'on pressentait au cadrage ; la voir diverger du code livré est normal et sans conséquence. Ne la corrige pas, ne la supprime pas.

**Cas `r-` (refacto)** — catégories :

1. **Mises à jour plan** — §Tests de caractérisation ajustés, §Périmètre différent du prévu, §Ordre d'exécution réordonné ou fusionné, nouvelle étape apparue en cours
2. **Effets de bord à tracer** — si le refacto a malgré lui modifié un comportement, le documenter dans le §Comportement externe à préserver du plan (et signaler que ce n'est plus un "refacto pur")
3. **Aucune mise à jour nécessaire**

**Cas `t-` (évolution technique)** — catégories :

1. **Mises à jour plan** — composant choisi différent du prévu (§Approche retenue), §Métriques (baseline → cible) ajustées, §Rollback et kill switch modifié, §Critères de sortie
2. **Aucune mise à jour nécessaire**

**Vocabulaire des sections** : les titres cités ci-dessus sont les titres **canoniques** de la charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md` (§4). Une story antérieure à la charte peut porter d'anciens titres (`Ordre d'implémentation`, `Critères de réussite`, `Problème adressé`…) : réaligne le contenu **sans** renommer les sections au passage — un sync n'est pas une migration de format.

**Si toutes les catégories sont vides**, la doc d'intention de la story est conforme : saute les Phases 3-4 (rien à réaligner sur `pitch.md`/`plan.md`), mais **enchaîne quand même sur la Phase 5** — une story conforme à son plan peut malgré tout avoir introduit une dépendance ou une capacité que les documents projet ne reflètent pas encore. Si la Phase 5 ne trouve rien non plus, dis « tout est conforme, rien à synchroniser » et arrête-toi.

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

**Métadonnées de story** : la timeline consolidée vit **uniquement** dans le `metadata.json` de la story (voir `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`). Ne produis **pas** de table de changelog en pied de `pitch.md`/`plan.md` : une fois les modifications appliquées, append une entrée au `changelog` du `metadata.json` (documentant la divergence réalignée) et rebouge `updated`.

### Phase 5 — Propagation aux documents projet (vision / stack / backlog)

Une story livrée fait souvent dériver les documents de **phase 0**, pas seulement la doc d'intention de la story. Cette phase propage les écarts vers `docs/vision.md`, `docs/stack.md` et `docs/product-backlog.md` **quand ils existent**, en réutilisant leurs modes **Enrichir/Éditer**. Tu ne recartographies rien : tu proposes des retouches chirurgicales à partir de ce que le `report.md` et le diff révèlent déjà. **Saute silencieusement un document absent.**

**Principe commun aux trois documents.** Ce sont des **reflets vivants** de ce que le produit est devenu : ils **suivent** le code livré, ils ne le jugent ni ne le bloquent jamais. Le code livré fait foi — quand un document le contredit, c'est le **document** qui est en retard et qu'on met à jour, pas la livraison qu'on remet en cause. Trois gestes seulement : **Enrichir** quand la livraison ajoute (dépendance, capacité, audience, territoire produit), **Éditer** quand elle rend une entrée caduque (version, anti-objectif, principe, capacité obsolète), **ne rien faire** quand elle reste dans le cadre déjà décrit (pas de bruit sur un bugfix). La seule chose qu'on ne bricole pas en inline, c'est une divergence **stratégique et large** → on renvoie vers le skill dédié en mode Pivot.

Pour repérer les fichiers réellement touchés par la story, appuie-toi sur la section « Fichiers créés/modifiés » du `report.md`, ou sur `git diff --name-only` sur les commits de la story.

Applique l'attitude adaptée au profil de chaque document :

**`docs/stack.md` — factuel, prouvé par fichier.**
Regarde si la story a touché un manifeste : `composer.json`/`composer.lock`, `package.json`, `docker-compose*.yml`, `.github/workflows/*`, `Dockerfile`, `.env*`.
- Nouvelle dépendance structurante, nouveau service, version bumpée, job CI ajouté → propose une ligne **Enrichir** (ajout) ou **Éditer** (version/remplacement), **prouvée par le fichier** (règle de `/stack` : jamais de techno non attestée).
- Rien de structurant (juste du code applicatif) → ne touche pas `stack.md`.

**`docs/product-backlog.md` — fonctionnel, ciblé.** (surtout stories `f-`)
- Rapproche la feature livrée d'une ligne du backlog (par slug/titre). Si elle correspond → propose de la marquer **livrée** et de mettre à jour la « Couverture » impactée (**Éditer**).
- Si la livraison a fait émerger une **capacité non prévue** au backlog → propose de l'ajouter (**Enrichir**).
- Stories `r-`/`t-` : en général pas d'impact backlog (comportement figé / non user-facing) — ne propose rien, sauf si le report signale un changement de comportement visible.

**`docs/vision.md` — boussole vivante, elle suit le produit.**
La vision n'est **pas un garde-fou** qui juge ou bloque les features : c'est un document vivant qui **évolue avec ce que le produit devient**. Une feature livrée qui déplace la direction produit doit **mettre à jour la vision**, jamais être « signalée comme non conforme ».
- La story **étend** le produit au-delà de la vision actuelle (nouvelle audience réellement servie, nouvelle valeur délivrée, nouveau territoire fonctionnel, jalon North Star/horizon franchi) → propose un **Enrichir** ciblé de la section concernée.
- La story **contredit** un principe ou un anti-objectif écrit → c'est le document qui est en retard sur la réalité, pas la feature : propose un **Éditer** (reformuler le principe, retirer/ajuster l'anti-objectif devenu caduc). Jamais de blocage.
- La story reste **dans le cadre** déjà décrit → ne touche à rien. Une vision ne se réécrit pas à chaque bugfix ; on évite le bruit sans pour autant la figer.
- Divergence **stratégique et large** (le produit ne résout plus le même problème, ne sert plus la même audience principale) → ne bricole pas en inline : signale qu'un **Pivot** est probablement nécessaire et renvoie vers `/vision` en mode Pivot.

**Application.** Présente les propositions (stack + backlog + vision) groupées, **max 3 par tour** via `AskUserQuestion`, même format qu'en Phase 3 (Avant / Après / Raison). Toute modification — y compris sur `vision.md` — est **proposée et validée**, jamais écrite en silence. Sur validation :
- `Edit` ciblé de la section concernée du document.
- Ajoute une ligne au **changelog natif du document** — chacun a le sien (liste de lignes datées, **pas** de table) : `AAAA-MM-JJ — <Enrichir|Éditer> — <couche|élément|axe> — sync post-livraison de la story NNN-<f|r|t>-slug`. Rebouge la date « dernière mise à jour » du sous-titre.
- N'utilise **pas** le format de changelog des Phases 1-4 pour ces docs : respecte leur convention propre.

Si aucun des 3 documents n'existe, ou si rien ne dérive, dis-le en une ligne et passe à la clôture. Pour un impact trop lourd pour une retouche ciblée (refonte d'un domaine backlog, pivot de stack), ne force pas l'Édition inline : signale-le et renvoie vers le skill dédié (`/stack`, `/product-backlog`) dans le mode adéquat.

### Phase 6 — Clôture

Affiche le résumé des modifications :

> Sync terminé :
> - `docs/story/NNN-<f|r|t>-slug/<fichier>.md` — X modifications appliquées (doc d'intention)
> - `docs/stack.md` — Y modif(s) · `docs/product-backlog.md` — Z modif(s) · `docs/vision.md` — W modif(s) *(si touchés)*
> - Divergence stratégique détectée : <suggestion de Pivot, ou « aucune »>
>
> Documentation réalignée avec l'implémentation, docs projet propagés.

## Argument optionnel

`/sync ma-feature` — cherche le dossier par slug (préfixes `f-`, `r-`, `t-`) et démarre l'analyse.

`/sync docs/story/013-r-extract-service/report.md` — utilise le report comme source des écarts.

`/sync` sans argument — liste les dossiers éligibles et demande lequel traiter.
