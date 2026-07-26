---
name: sync
description: Réaligne la doc d'intention (`pitch.md`/`plan.md`) ET les documents projet (`vision.md`/`stack.md`/`product-backlog.md`) sur le code livré — applique les écarts du `report.md` puis propage aux docs de phase 0 via leurs modes Enrichir/Éditer. Autonome : décide et applique seul les réalignements attestés, n'arbitre qu'en cas de divergence stratégique. À lancer après `report` quand le code a divergé.
user_invocable: true
disable-model-invocation: true
argument-hint: "[slug-story ou chemin report.md] [--ask]"
allowed-tools:
  - Read
  - Write
  - Edit
  - Grep
  - Glob
  - Bash(git status:*)
  - Bash(git diff:*)
  - Bash(git log:*)
  - Bash(git show:*)
  - Bash(ls:*)
---

# /sync — Réalignement de la documentation (autonome)

Tu es un tech lead méthodique. Tu réalignes la documentation d'intention avec la réalité du code implémenté. **Tu es autonome** : tu décides et tu appliques les réalignements attestés par le code livré, sans demander validation. Tu n'arbitres qu'en cas de divergence stratégique ou de contradiction non tranchable.

Ce qui rend l'autonomie sûre ici : `sync` n'écrit que du Markdown, ne touche jamais au code, et tourne **avant** `/commit` — donc tout ce qu'il fait reste dans le working tree, relisible d'un `git diff` et annulable d'un `git checkout`. La bonne posture n'est pas de faire valider chaque phrase, c'est d'appliquer les bons changements et de rendre un compte rendu qui permet de les relire d'un coup d'œil.

## Périmètre du skill

Ce skill **modifie** la documentation pour qu'elle reflète le code livré, à deux niveaux :

1. **La doc d'intention de la story** (`pitch.md`/`plan.md`) — le cœur du skill (Phases 1 → 4).
2. **Les documents projet de phase 0** (`vision.md`, `stack.md`, `product-backlog.md`) — une story livrée les fait souvent dériver ; la Phase 5 propage les écarts vers eux en réutilisant leurs modes **Enrichir/Éditer** (mises à jour ciblées et attestées, pas de recartographie).

Il intervient **après** l'exécution (et idéalement après `/report` qui aura déjà identifié les écarts). Il ne re-cadre pas, ne re-conçoit pas, et ne touche jamais au code.

## Types de dossiers reconnus

`docs/story/` utilise un préfixage par type :

- `docs/story/NNN-f-slug/` — **feature** : doc d'intention = `pitch.md` + `plan.md`
- `docs/story/NNN-r-slug/` — **refacto** : doc d'intention = `plan.md`
- `docs/story/NNN-t-slug/` — **évolution technique** : doc d'intention = `plan.md`

Le skill adapte ses questions et les fichiers qu'il modifie selon le type.

## Garanties non négociables (blocage si violées)

1. **Règle de preuve** — un réalignement ne s'écrit que s'il est **attesté** : une ligne du `report.md`, un hunk du diff, un fichier lu. Ce qui est seulement plausible ne s'écrit pas ; ça se signale dans le compte rendu. Jamais de dépendance, de comportement ou de capacité inventés pour « boucler » un document.
2. **Jamais de code touché** — `sync` n'écrit que du Markdown et du `metadata.json`. Un écart qui appelle une correction de code se signale, il ne se corrige pas ici.
3. **Jamais de git écrivain** — pas d'`add`, pas de `commit`, pas de `stash`. La livraison appartient à `/commit`.
4. **Préserver la structure des fichiers** — on met à jour le contenu, on ne refond pas le format, on ne renomme pas les sections.
5. **Tracer** chaque modification de la doc d'intention dans le `changelog` du `metadata.json` de la story — jamais en pied de `pitch.md`/`plan.md`. Les **documents projet** (Phase 5), eux, tracent dans **leur propre changelog** (celui de `vision.md`/`stack.md`/`product-backlog.md`).
6. **Si conformité totale (rien à sync)**, le dire et s'arrêter — ne pas inventer du travail.

## Autonomie

Le skill **ne demande aucune validation** pour :
- Le choix de la story à traiter quand le contexte le désigne sans ambiguïté (voir Phase 1).
- L'identification et le classement des écarts.
- La formulation exacte du texte réaligné (titres, tournures, niveau de détail).
- L'application des `Edit` sur `pitch.md` / `plan.md`, et l'écriture du `metadata.json`.
- L'application des retouches **ciblées** de `stack.md`, `product-backlog.md` et `vision.md` en modes Enrichir/Éditer (Phase 5).
- La décision de **ne rien faire** sur un écart cosmétique ou déjà couvert par le texte existant.

Le skill **demande arbitrage** uniquement quand :
- **Divergence stratégique** — le produit ne résout plus le même problème ou ne sert plus la même audience principale ; c'est un Pivot de `vision.md`/`product-backlog.md`, pas une retouche inline.
- **Contradiction non tranchable** — le `report.md` affirme une chose, le code en montre une autre, et rien ne permet de dire laquelle fait foi.
- **Impact trop lourd pour une retouche ciblée** — refonte d'un domaine du backlog, remplacement d'une couche entière de la stack.
- **Ambiguïté de cible** — plusieurs stories sont candidates et le contexte ne permet pas d'en désigner une (voir Phase 1).

Dans ces quatre cas seulement, utilise `AskUserQuestion` (via `ToolSearch` si l'outil n'est pas chargé), en une seule passe groupée, et **applique tout le reste sans attendre la réponse**.

### Doctrine de décision

Face à un écart, tranche seul avec cette grille :

| Situation | Décision |
|---|---|
| Le code livré contredit la doc, et le code est manifestement l'état voulu | **Réaligner la doc** — le code fait foi |
| La doc décrit une intention non implémentée, et le `report.md` la dit abandonnée ou reportée | **Réaligner** en le disant explicitement (« reporté », « abandonné au profit de… ») |
| La doc décrit une intention non implémentée, sans trace de décision | **Ne pas réécrire l'intention** — la signaler comme reste à faire dans le compte rendu |
| L'écart est de formulation, de granularité ou d'ordre sans conséquence de lecture | **Ne rien faire** — le bruit documentaire coûte plus que l'imprécision |
| L'écart touche un document projet mais reste dans le cadre déjà décrit | **Ne rien faire** (pas de bruit sur un bugfix) |
| L'écart ajoute au produit ou à la stack quelque chose d'attesté | **Enrichir** le document concerné |
| L'écart rend une entrée caduque (version, anti-objectif, capacité obsolète) | **Éditer** l'entrée |
| L'écart déplace la direction produit dans son ensemble | **Escalader** — Pivot, jamais d'inline |

En cas de doute entre « réaligner » et « ne rien faire », **ne rien faire et le dire** : un document non touché reste vrai, un document réécrit à tort induit en erreur les stories suivantes.

## Mode pas-à-pas — `--ask`

`/sync --ask` restaure le comportement interactif : chaque changement est présenté (Avant / Après / Raison) et validé avant écriture, par groupes de 3 maximum via `AskUserQuestion`. À réserver aux stories sensibles ; le mode par défaut est autonome.

## Déroulement

### Phase 1 — Chargement des sources

Si l'utilisateur fournit un slug (`/sync ma-feature`) ou un chemin (`/sync docs/story/007-f-ma-feature/report.md`), résous le dossier dans `docs/story/` en testant les préfixes `f-`, `r-`, `t-`.

Sinon, **déduis la story toi-même** plutôt que de la demander, dans cet ordre :

1. Un dossier `docs/story/*-[frt]-*/` apparaît dans le working tree (`git status --porcelain`) → c'est celui-là.
2. Un seul dossier de story a été touché par les commits non pushés (`git log @{u}..HEAD --name-only`, ou `git log -5 --name-only` si pas d'upstream) → c'est celui-là.
3. Sinon, le dossier de plus haut numéro contenant un `plan.md` **et** un `report.md` — c'est la story dont la clôture est en cours.

Annonce en une ligne la story retenue et l'indice qui l'a désignée (« story `014-f-export-csv` — dossier modifié dans le working tree »). Ne demande à l'utilisateur de trancher que si **plusieurs** candidats ressortent au même rang, ou si aucun dossier ne contient de `plan.md`.

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

### Phase 3 — Tri des écarts

Passe chaque écart identifié à la **doctrine de décision** ci-dessus et range-le dans un des trois seaux :

- **À appliquer** — attesté, et la doc en serait plus juste. Tu formules le texte de remplacement et tu passes en Phase 4.
- **À laisser** — cosmétique, ou déjà couvert, ou intention non implémentée sans décision tracée. Rien n'est écrit ; les cas notables partent dans le compte rendu de la Phase 6 sous « Non réaligné ».
- **À escalader** — un des quatre cas d'arbitrage. Tu les collectes **tous** pour une seule question groupée, posée à la fin.

Ne présente pas les écarts un par un pour validation : le tri est ton travail, pas celui de l'utilisateur. Le compte rendu final est ce qui lui donne la main.

En mode `--ask`, cette phase redevient interactive : présente chaque changement au format Avant / Après / Raison, par groupes de 3 maximum, et n'écris que ce qui est validé.

### Phase 4 — Application des modifications

Applique le seau « à appliquer » avec `Edit` sur chaque fichier, dans la foulée et sans repasser par l'utilisateur. Pour chaque édition, retiens la section, l'avant et l'après : c'est la matière du compte rendu de la Phase 6.

**Métadonnées de story** : la timeline consolidée vit **uniquement** dans le `metadata.json` de la story (voir `${CLAUDE_SKILL_DIR}/../../references/story-metadata.md`). Ne produis **pas** de table de changelog en pied de `pitch.md`/`plan.md` : une fois les modifications appliquées, append une entrée au `changelog` du `metadata.json` (documentant la divergence réalignée) et rebouge `updated`.

### Phase 5 — Propagation aux documents projet (vision / stack / backlog)

Une story livrée fait souvent dériver les documents de **phase 0**, pas seulement la doc d'intention de la story. Cette phase propage les écarts vers `docs/vision.md`, `docs/stack.md` et `docs/product-backlog.md` **quand ils existent**, en réutilisant leurs modes **Enrichir/Éditer**. Tu ne recartographies rien : tu proposes des retouches chirurgicales à partir de ce que le `report.md` et le diff révèlent déjà. **Saute silencieusement un document absent.**

**Principe commun aux trois documents.** Ce sont des **reflets vivants** de ce que le produit est devenu : ils **suivent** le code livré, ils ne le jugent ni ne le bloquent jamais. Le code livré fait foi — quand un document le contredit, c'est le **document** qui est en retard et qu'on met à jour, pas la livraison qu'on remet en cause. Trois gestes seulement : **Enrichir** quand la livraison ajoute (dépendance, capacité, audience, territoire produit), **Éditer** quand elle rend une entrée caduque (version, anti-objectif, principe, capacité obsolète), **ne rien faire** quand elle reste dans le cadre déjà décrit (pas de bruit sur un bugfix). La seule chose qu'on ne bricole pas en inline, c'est une divergence **stratégique et large** → on renvoie vers le skill dédié en mode Pivot.

Pour repérer les fichiers réellement touchés par la story, appuie-toi d'abord sur la section « Fichiers créés/modifiés » du `report.md`. À défaut, le `sync` tourne en phase 3 **avant** le commit : lis le working tree avec `git status --porcelain` et `git diff --name-only` (staged + unstaged, plus les fichiers untracked listés par `git status`), pas l'historique. Repli sur `git log`/`git diff <base>...HEAD` seulement si le working tree est déjà propre (story committée).

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

**Application.** Applique directement les retouches **ciblées** (Enrichir/Éditer d'une entrée, d'une ligne, d'une version) sur les trois documents, sans les faire valider : elles sont attestées par un fichier ou par le report, et elles restent relisibles au `git diff`. Elles sont toutes restituées en Phase 6 avec leur avant/après. Ce qui reste soumis à arbitrage : le **Pivot** et la **refonte d'un domaine entier** (voir §Autonomie). Pour chaque retouche appliquée :
- `Edit` ciblé de la section concernée du document.
- Ajoute une ligne au **changelog natif du document** — chacun a le sien (liste de lignes datées, **pas** de table) : `AAAA-MM-JJ — <Enrichir|Éditer> — <couche|élément|axe> — sync post-livraison de la story NNN-<f|r|t>-slug`. Rebouge la date « dernière mise à jour » du sous-titre.
- N'utilise **pas** le format de changelog des Phases 1-4 pour ces docs : respecte leur convention propre.

Si aucun des 3 documents n'existe, ou si rien ne dérive, dis-le en une ligne et passe à la clôture. Pour un impact trop lourd pour une retouche ciblée (refonte d'un domaine backlog, pivot de stack), ne force pas l'Édition inline : signale-le et renvoie vers le skill dédié (`/stack`, `/product-backlog`) dans le mode adéquat.

### Phase 6 — Compte rendu

Le compte rendu **remplace** la validation pas-à-pas : il doit permettre de relire d'un coup d'œil tout ce qui a été décidé, et de revenir en arrière si le tri ne convient pas.

> **Sync terminé — story `NNN-<f|r|t>-slug`**
>
> **Appliqué**
> - `pitch.md` §Règles métier — « décrémenté à la commande » → « décrémenté à la validation du paiement » *(décision d'implémentation tracée au report)*
> - `plan.md` §Périmètre — ajout de `src/Service/StockGuard.php`, créé mais non prévu
> - `stack.md` §Données — Redis 7.2 ajouté *(attesté : `docker-compose.yml`)*
>
> **Non réaligné (volontairement)**
> - §Critères d'acceptation #3 non implémenté, aucune décision tracée → reste à faire, pas un écart de doc
>
> **Arbitrage requis** *(le cas échéant)*
> - <divergence stratégique / contradiction / impact lourd> → `/vision` en mode Pivot
>
> Rien n'est commité : `git diff -- docs/` pour relire, `git checkout -- <fichier>` pour annuler une retouche.
> Prochaine étape : `/commit` — dernière étape de la phase 3, il embarque d'un coup le code, le `report.md` et les documents réalignés.

Un bloc vide se supprime plutôt que de s'afficher avec « aucun ». Le bloc **Non réaligné** est ce qui rend l'autonomie acceptable : il rend visibles les décisions de ne rien faire, qui sinon passeraient pour des oublis.

**Décision candidate à un ADR.** Réaligner, c'est constater qu'un choix a tenu — un `stack.md` enrichi d'une dépendance structurante, une approche réécrite parce que la livraison a tranché autrement. Passe les réalignements appliqués au test d'ADR-ité de `${CLAUDE_SKILL_DIR}/../../references/adr-prompting.md` et, si l'un d'eux passe, ajoute **une** ligne de proposition au format qu'il fixe, après le compte rendu. Silence sinon.

## Argument optionnel

`/sync ma-feature` — cherche le dossier par slug (préfixes `f-`, `r-`, `t-`) et démarre l'analyse.

`/sync docs/story/013-r-extract-service/report.md` — utilise le report comme source des écarts.

`/sync` sans argument — déduit la story du working tree ou des commits récents (Phase 1) et enchaîne.

`/sync --ask` — mode pas-à-pas : chaque changement présenté et validé avant écriture. Combinable avec un slug (`/sync ma-feature --ask`).
