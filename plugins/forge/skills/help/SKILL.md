---
name: help
description: Affiche le sommaire du workflow et oriente vers le bon skill — quatre phases (décor, cadrage, implem, clôture), trois tracks (feature, refacto, tech) et leurs artifacts. À utiliser quand tu ne sais pas par où commencer ou quel skill correspond.
user_invocable: true
disable-model-invocation: true
allowed-tools:
  - Read
  - Glob
---

# /help — Guide du workflow de développement

## Schéma global du pipeline

```
                       PHASE 0 — POSER LE DÉCOR (une fois par projet, ou pivot)
                       ┌────────┐
                       │ vision │──▶ docs/vision.md (problème, audience, valeur,
                       └────────┘    North Star, principes, anti-objectifs)
                                     Lu par product-backlog puis feature-pitch.
                       ┌────────────────┐
                       │ product-backlog│──▶ docs/product-backlog.md (domaines, capacités,
                       └────────────────┘    parcours, règles transverses, backlog priorisé MVP/V2/V3)
                                           Lu par feature-pitch pour situer chaque feature.
                       ┌───────┐
                       │ stack │──▶ docs/stack.md (langages, backend, frontend, données,
                       └───────┘    ops, CI — chaque techno prouvée par un fichier source)
                                    Lu par feature-implem/refactor-implem/tech-implem/review.
                       ┌───────────┐
                       │ agents-md │──▶ AGENTS.md à la racine + CLAUDE.md pont (nature, stack,
                       └───────────┘    commandes, conventions — chaque ligne prouvée par fichier)
                                        Lu par toutes les skills : commandes QA, conventions projet.

     PHASES 1 À 3 — CADRER, IMPLÉMENTER, CLÔTURER : une story emprunte UN SEUL des trois tracks.
     La phase dit quand ; le track dit avec quelles skills. Dans chaque track ci-dessous :
     les plans = phase 1, l'implem = phase 2, review → sync → commit = phase 3.
     La phase 3 se ferme, quand on publie une version, par release (voir sous les tracks).

                        TRACK FEATURE (valeur utilisateur, structurante)
 (optionnel : besoin flou)
 ┌───────────────────┐
 │ feature-interview │┄┐
 └─────────┬─────────┘ ┊ brief.md
           ▼           ▼
 ┌───────────────┐   ┌──────────────┐   ┌────────────────┐   ┌────────┐   ┌──────┐   ┌────────┐
 │ feature-pitch │──▶│ feature-plan │──▶│ feature-implem │──▶│ review │──▶│ sync │──▶│ commit │
 └───────┬───────┘   └───────┬──────┘   └────────┬───────┘   └────┬───┘   └───┬──┘   └────┬───┘
         │                   │                   │                │           │           │
     pitch.md             plan.md         code+migrations     verdict      doc sync    commit
                                          +nouveaux tests   (review.md   +report.md    +push
                                                            si ouvert)

                    TRACK REFACTO (comportement figé, code restructuré)
 ┌───────────────┐   ┌─────────────────┐   ┌────────┐   ┌──────┐   ┌────────┐
 │ refactor-plan │──▶│ refactor-implem │──▶│ review │──▶│ sync │──▶│ commit │
 └───────┬───────┘   └────────┬────────┘   └────┬───┘   └───┬──┘   └────┬───┘
         │                    │                 │           │           │
      plan.md           verrou tests         verdict     doc sync    commit
                          + étapes                      +report.md    +push
                        incrémentales

               TRACK TECH (perf, résilience, observabilité, sécu — non user-facing)
 ┌───────────┐   ┌─────────────┐   ┌────────┐   ┌──────┐   ┌────────┐
 │ tech-plan │──▶│ tech-implem │──▶│ review │──▶│ sync │──▶│ commit │
 └─────┬─────┘   └──────┬──────┘   └────┬───┘   └───┬──┘   └────┬───┘
       │                │               │           │           │
    plan.md         baseline         verdict     doc sync    commit
                  + kill switch                 +report.md    +push
                + étapes mesurées

                 FIN DE PHASE 3 (optionnel, pas à chaque story : quand on publie)
 ┌────────┐   ┌─────────┐
 │ commit │┄┄▶│ release │──▶ vX.Y.Z + CHANGELOG.md + tag annoté + GitHub release
 └────────┘   └─────────┘    (après un ou plusieurs commits de stories livrées)

                       UTILITAIRES (hors pipeline, à la demande)
 ┌─────────────────┐    ┌─────┐    ┌──────┐
 │  test-scenario  │    │ adr │    │ help │  ← tu y es
 └─────────────────┘    └──┬──┘    └──────┘
 Playwright MCP live       │       ce sommaire
                           ▼
                  docs/adr/NNNN-slug.md
                  (depuis pitch/plan/review/report
                   ou topic libre)

 ┌──────────────┐    ┌──────────┐
 │  doc-feature │    │ estimate │
 └──────┬───────┘    └────┬─────┘
        │                 │
        ▼                 ▼
 docs/feature-map/    estimate.md
 NNN-slug/overview.md (temps facturable
 (rétro-doc à partir   tout compris, en
  du code livré)       heures : réf. + IA)

 ┌────────┐
 │ status │  point de situation à la reprise : stories en cours, étape
 └────────┘  atteinte, état du dépôt, par quoi reprendre
             (lecture seule — n'écrit aucun fichier)

 ┌───────┐
 │ grill │  passe au gril une idée, une décision ou un pitch/plan existant :
 └───────┘  arbre de décision, une réponse recommandée par question
            (n'écrit aucun fichier — rend une synthèse et nomme qui l'intègre)
```

**Comment les ateliers te challengent.** `/feature-pitch`, les trois plans, `/vision` et `/adr` ne déroulent pas une checklist : ils remontent l'**arbre des décisions**. Chaque round ne pose que les questions dont les prérequis sont tranchés, chacune arrive avec **leur réponse recommandée** (tu valides ou tu contestes, tu n'as pas à tout formuler), ce que le code dit déjà est lu plutôt que demandé, et une ligne d'état te montre les branches encore ouvertes. Tu peux dire « on rédige » à tout moment : les branches que tu laisses ouvertes partent en `## Questions ouvertes`, avec leur recommandation.

Règle d'or : ne jamais passer à l'étape suivante sans validation explicite du user ("ok", "go", "validé", "c", etc.).

## Convention dossiers `docs/story/`

Tous les artifacts vivent dans `docs/story/` à plat, **numérotés globalement** puis taggés par type. Format : `NNN-<f|r|t>-<slug>`. Le numéro vient en premier pour que le tri lexicographique de `ls` corresponde à l'ordre chronologique.

| Tag    | Type              | Doc d'intention              | Exemple                             |
|--------|-------------------|------------------------------|-------------------------------------|
| `f`    | Feature           | `pitch.md` + `plan.md`       | `docs/story/042-f-checkout-express/` |
| `r`    | Refacto           | `plan.md`                    | `docs/story/043-r-extract-pricing/`  |
| `t`    | Évolution tech    | `plan.md`                    | `docs/story/044-t-redis-cache/`      |

Tous les tracks produisent un `plan.md` (cadrage technique exécutable). Le track feature a en amont un `pitch.md` (cadrage fonctionnel) qui formalise le problème utilisateur avant le plan.

Les numéros s'incrémentent globalement (042-f → 043-r → 044-t → 045-f…), ce qui permet de lire la timeline d'évolution du projet en listant simplement `docs/story/`.

**Un format commun à tous les documents** : `brief.md`, `pitch.md`, `plan.md`, `review.md`, `report.md` et `estimate.md` partagent un contrat de format unique (`references/document-format.md`). Chaque document sert **un seul but**, dans **un seul registre** — annoncé dans son en-tête : le brief et le pitch sont **fonctionnels** (aucun nom de classe, de service ou de framework), les plans et la review sont **techniques**, le report est **factuel**, l'estimation est **économique**. Les documents de décision (review, report, estimation) ouvrent sur leur `## Synthèse` : on doit pouvoir ne lire qu'elle. Les trois plans partagent le même squelette de sections, quel que soit le track — leurs spécificités (caractérisation d'un refacto, kill switch d'un tech) sont des sections additionnelles. La timeline, elle, ne vit dans aucun `.md` : elle est dans le `metadata.json` de la story. Et rien ne se recopie d'un document à l'autre : `review.md` n'est conservé que si un point reste ouvert, `report.md` ne garde que les écarts, la dette et les leçons — le périmètre livré et les critères cochés vivent dans le plan et le pitch réalignés.

## Les deux axes : phases et tracks

Le pipeline se lit sur deux axes qu'il ne faut pas confondre :

- **La phase dit *quand*.** Quatre phases, dans l'ordre : **0** poser le décor (une fois par projet), puis **1** cadrer, **2** implémenter, **3** clôturer — traversées par chaque story.
- **Le track dit *avec quelles skills*.** À phase égale, une feature, un refacto et une évolution technique n'appellent pas les mêmes skills. Le track se choisit à l'entrée de la phase 1 et ne change pas les phases traversées : la phase 3 est identique pour les trois.

## Phase 0 — Poser le décor

Avant le premier track, en tout début de projet (ou lors d'un pivot), poser le décor **une fois pour toutes** : pourquoi ce produit existe, ce qu'on va construire, sur quoi on marche, et comment on travaille dans ce dépôt. Les trois documents de `docs/` sont **vivants** (4 modes : Création / Enrichir / Éditer / Pivot, avec changelog) et vivent à la racine de `docs/`, pas dans une story. L'`AGENTS.md`, lui, vit à la racine du dépôt (modes Création / Migration / Mise à jour).

| Skill              | Rôle                                                                                                | Produit                   |
|--------------------|-----------------------------------------------------------------------------------------------------|---------------------------|
| `/vision`          | Atelier challengeur sur problème, audience, valeur, North Star, principes, anti-objectifs            | `docs/vision.md`          |
| `/product-backlog` | Atelier fonctionnel : domaines → capacités → parcours → règles transverses → backlog MVP/V2/V3       | `docs/product-backlog.md` |
| `/stack`           | Scanne tous les manifestes (langages, backend, frontend, données, ops, CI), interroge pour combler les trous, prouve chaque techno par un fichier source | `docs/stack.md` |
| `/agents-md`       | Analyse le codebase (nature, stack, architecture, commandes, conventions), chaque ligne prouvée par un fichier — aucune commande inventée ; migre un `CLAUDE.md` existant | `AGENTS.md` + `CLAUDE.md` pont |

**Vision** — pourquoi ce produit existe, pour qui, quelle valeur il crée, comment on mesure le succès, et ce qu'on refuse explicitement de faire. Lue par `/product-backlog` puis par `/feature-pitch` à chaque nouvelle feature pour challenger l'alignement (problème adressé, audience, principes, impact North Star). Pas de lancement à chaque feature : c'est un document fondateur, révisé seulement lors d'un pivot.

**Product-backlog** — le pont entre la stratégie (vision) et le cadrage feature par feature : carte des capacités fonctionnelles + backlog priorisé de features candidates. On le révise quand le périmètre évolue (nouvelle capacité, repriorisation, pivot). `/feature-pitch` le lit pour reprendre le pitch initial d'une ligne de backlog, ses capacités couvertes et ses dépendances. **Facultatif** (on peut aller direct vision → feature-pitch), mais recommandé dès qu'on a plus de 3-4 features pressenties.

**Stack** — sur quoi on marche, avant de cadrer la moindre évolution. Indispensable sur un legacy non documenté, utile sur un projet neuf dès que la stack se stabilise. Lu **en priorité par les skills techniques** (`/feature-plan`, `/refactor-plan`, `/tech-plan`, `/review`) pour décider sur des bases factuelles. Il **constate** l'existant — il ne justifie pas un choix (c'est le rôle d'`/adr`) ni ne décide d'une évolution (`/tech-plan`).

`docs/stack.md` est un document **vivant** (mêmes 4 modes que vision/backlog : Création / Enrichir / Éditer / Pivot, avec changelog). Il est lu **en priorité par les tracks techniques** (`/feature-plan`, `/refactor-plan`, `/tech-plan`, `/review`) pour décider sur des bases factuelles. Il constate l'existant — il ne justifie pas un choix (c'est le rôle d'`/adr`) ni ne décide d'une évolution (`/tech-plan`).

**AGENTS.md** — comment on travaille dans ce dépôt : commandes de build, de test et de QA, conventions, architecture. C'est là que les skills d'implémentation lisent les commandes exactes au lieu de les deviner, et il sert aussi aux autres agents (Codex, Cursor…) ; le `CLAUDE.md` est réduit à un pont `@AGENTS.md` plus le spécifique Claude Code. À lancer **après `/stack` et `/vision`** s'ils existent : il les réutilise. Les règles qui ne concernent qu'une zone du code en sortent ensuite vers `.claude/rules/` via `/rules`.

## Choisir son track

| Question | Si **oui** → |
|----------|--------------|
| Un utilisateur final ou un admin peut décrire ce qu'il voit de nouveau ? | **Feature** (`f-`) |
| Le comportement externe reste **strictement** identique (mêmes réponses, events, logs, timings) et on restructure juste le code ? | **Refacto** (`r-`) |
| Un observateur externe (test, monitoring, log consumer, autre service) peut détecter la différence, mais c'est pour **mieux** (plus rapide, plus résilient, plus observable, plus sûr) sans nouvelle valeur user ? | **Tech** (`t-`) |
| Moins de 3 fichiers, pas de migration, pas d'impact transverse ? | **Fast** |

**Piège** : un changement qui mélange les catégories. Règle : si tu ne peux pas scinder ton diff en commits distincts (le refacto pur, puis l'ajout de la brique tech, puis la feature qui s'en sert), tu as probablement mélangé deux tracks. Sépare.

## Track feature — Valeur utilisateur

Pour tout changement qui introduit une nouvelle fonctionnalité ou modifie un comportement observable par l'utilisateur.

| Phase | Skill                | Rôle                                                                             | Produit                            |
|-------|----------------------|----------------------------------------------------------------------------------|------------------------------------|
| 1     | `/feature-interview` | *(optionnel)* Découvrir un besoin flou par interview avant de pouvoir le pitcher | `docs/story/NNN-f-slug/brief.md`   |
| 1     | `/feature-pitch`     | Cadrer et challenger une fonctionnalité (lit le `brief.md` amont s'il existe)    | `docs/story/NNN-f-slug/pitch.md`   |
| 1     | `/feature-plan`      | Concevoir la solution technique à partir du pitch                                | `docs/story/NNN-f-slug/plan.md`    |
| 2     | `/feature-implem`    | Implémenter sous-tâche par sous-tâche avec QA continue                           | Code + migrations + tests          |
| 3     | `/review`            | Code review (sécu, perf, qualité, conformité plan)                               | Verdict ; `review.md` si un point reste ouvert |
| 3     | `/sync`              | Relever les écarts, réaligner pitch et plan, cocher les critères, consigner      | `pitch.md` + `plan.md` réalignés, `report.md` |
| 3     | `/commit`            | En dernier : embarque code + report + docs réalignées, Conventional Commits + push | Commit                           |

## Track refacto — Comportement figé, code restructuré

Pour restructurer du code sans toucher au comportement externe (dette, couplage, préparer une feature à venir, extraire un service, décomposer une god class…).

**Principe** : comportement externe strictement préservé, verrou tests de caractérisation AVANT de toucher, exécution incrémentale réversible.

| Phase | Skill             | Rôle                                                              | Produit                           |
|-------|-------------------|-------------------------------------------------------------------|-----------------------------------|
| 1     | `/refactor-plan`  | Cadrer un refacto (motivation, cible, caractérisation, étapes)   | `docs/story/NNN-r-slug/plan.md`   |
| 2     | `/refactor-implem`| Exécuter : verrou tests puis étapes incrémentales, non-régression | Code restructuré + tests          |
| 3     | `/review`         | Code review focus non-régression                                  | Verdict ; `review.md` si un point reste ouvert |
| 3     | `/sync`           | Relever les écarts, réaligner le plan, consigner                  | `plan.md` réaligné, `report.md`   |
| 3     | `/commit`         | En dernier : embarque tout, souvent un commit par étape           | Commits                           |

## Track tech — Perf, résilience, observabilité, sécu (non user-facing)

Pour ajouter ou modifier une brique technique observable (latence, taux d'erreur, format de log, timing) qui n'apporte pas de nouvelle valeur utilisateur fonctionnelle : cache, retry, circuit breaker, queue async, logs structurés, index SQL, health check, CSP, bump de CVE…

**Principe** : métrique cible chiffrée obligatoire, baseline mesurée AVANT toute modif, kill switch activable, étapes incrémentales avec mesure après chaque.

| Phase | Skill          | Rôle                                                               | Produit                           |
|-------|----------------|--------------------------------------------------------------------|-----------------------------------|
| 1     | `/tech-plan`   | Cadrer l'évolution (problème, brique, métriques cibles, rollback) | `docs/story/NNN-t-slug/plan.md`   |
| 2     | `/tech-implem` | Exécuter : baseline, kill switch, étapes mesurées, validation      | Code + config + observabilité     |
| 3     | `/review`      | Code review (kill switch, compatibilité, non-régression)          | Verdict ; `review.md` si un point reste ouvert |
| 3     | `/sync`        | Écarts, métriques atteintes ou non, plan réaligné, consigner       | `plan.md` réaligné, `report.md`   |
| 3     | `/commit`      | En dernier : embarque code + report + docs réalignées, + push      | Commits                           |

## Track fast — Bugfixes et petits changements

**Conditions (toutes requises)** :

- Moins de 3 fichiers modifiés
- Pas de changement de schéma (migration) ni de nouveau service/entité
- Pas d'impact transverse (multi-channel, multi-thème, API publique…)

**Processus** :

```
coder → QA du stack → tests ciblés → /review (optionnel) → /commit
```

En cas de doute → partir sur le track approprié (feature, refacto ou tech). Il est toujours possible de basculer du structurant vers le fast si l'analyse révèle que c'est trivial.

## Phase 3 — Clôturer (`/review`, `/sync`, `/commit`, puis `/release`)

Les trois tracks (feature, refacto, tech) partagent les mêmes étapes de clôture après l'implémentation, **dans cet ordre**. Ce sont des skills communs : seuls le contenu et le ton des artifacts changent selon le track. Le `/commit` vient **en dernier** pour la story : review et sync travaillent sur le diff du working tree (non committé), puis le commit embarque d'un coup le code, le `report.md` et les documents réalignés. `/release` ferme la phase quand on publie une version — pas à chaque story.

### `/review` — Juger le diff
Cherche ce qui doit bloquer le commit : faille, bug, régression, migration risquée, dérive non justifiée par rapport au plan. Les findings se corrigent dans la passe. `review.md` n'est **conservé que si un point reste ouvert** (finding non coché, réserve arbitrée, vérification « hors review » avant déploiement) : un fichier tout coché au verdict positif ne dit rien qu'un commit ne dise déjà. La trace de la revue — statut, findings corrigés — vit dans le `metadata.json`.

### `/sync` — Clôture documentaire
Une seule passe, le diff lu une fois :

1. **Relève les écarts** entre l'intention (`pitch.md`/`plan.md`) et le code livré — écarts volontaires, non livré, ajouts non prévus. Le *pourquoi* de chaque écart, il le prend là où il est né : checkpoints de l'implem, entrée `Implémentation` et `Review` du `metadata.json`, findings de la review. Il ne pose de question que si un élément prévu n'est pas livré et que rien ne dit s'il est abandonné, reporté ou oublié.
2. **Réaligne** la doc d'intention pour qu'elle **se lise comme si elle avait été écrite correctement dès le départ** (modèle ajusté, route renommée, étape rajoutée…), et **coche les critères en place** : critères d'acceptation dans le pitch, critères de sortie dans le plan. Une **phase finale** propage aux **documents de phase 0** quand ils existent (modes Enrichir/Éditer) : `stack.md` gagne une dépendance détectée dans le diff, `product-backlog.md` marque l'état de la feature, `vision.md` **évolue avec le produit** — elle suit les features, elle ne les bloque pas ; seule une divergence stratégique large est renvoyée vers `/vision` en mode Pivot.
3. **Consigne** dans `report.md` ce qui n'a aucune autre place : les écarts et où ils ont été réalignés, ce qui n'est pas livré, la dette, les leçons. Rien n'y est recopié — le périmètre livré, c'est le plan réaligné ; les critères, ils sont cochés dans le pitch. Pas de report du tout si la story est conforme et sans dette.

**Autonome** : il déduit la story du working tree, tranche seul (réaligner / laisser / escalader), applique, puis rend un compte rendu avant/après relisible d'un `git diff` — rien n'étant encore commité. `--ask` restaure le mode pas-à-pas. En clôture, il **propose** un ADR quand un écart est une décision d'architecture, et `/rules` quand une leçon mérite d'être chargée la prochaine fois qu'on touche la zone.

### `/commit` — Construire et pousser les commits
**Dernière étape** : une fois review et sync passés, lit le diff git, regroupe les changements en lots cohérents, propose des messages au format **Conventional Commits en français** (`feat(scope): …`, `fix(scope): …`, `refactor(scope): …`, etc.), demande validation, commit et push. Le commit embarque ainsi le code, le `report.md` et les docs réalignées en une fois. Sur un track refacto, on a souvent **un commit par étape** pour préserver la réversibilité.

### `/release` — Publier une version (optionnel)
**Pas à chaque story** : quand une ou plusieurs stories commitées méritent d'être publiées. Détermine le bump SemVer (major/minor/patch) depuis les Conventional Commits depuis le dernier tag, met à jour `CHANGELOG.md` (format Keep a Changelog, titre de version, chapitres Fonctionnel / Technique), crée un tag annoté `vX.Y.Z`, push, puis publie la release sur GitHub via `gh`. Demande validation avant toute action publique. Renseigne la `delivery` des stories embarquées. Argument-hint : `[major\|minor\|patch] [--no-push] [--draft] [--pre <suffix>]`. `/status` te signale quand des livraisons non taguées s'accumulent.

> **Ne pas confondre `/sync` avec `/doc-feature`** : `/sync` recale un document d'intention récent que tu viens de modifier dans un track structuré. `/doc-feature` (voir Utilitaires) cartographie une feature **ancienne ou jamais passée par le pipeline**, en partant du code livré, sans dossier de track préalable.

## Utilitaires (hors pipeline)

| Skill                | Rôle                                                                                       |
|----------------------|--------------------------------------------------------------------------------------------|
| `/status`            | **Reprendre un projet après une pause** — recense les stories de `docs/story/`, déduit pour chacune l'étape atteinte (des artifacts présents et du `metadata.json`) et sa fraîcheur (active ≤ 7 j / en pause / dormante > 30 j), relève l'état du dépôt (branche, working tree, commits depuis le dernier tag, stash et commits non poussés), vérifie le décor de phase 0, puis conseille **une** reprise en nommant le skill à taper. **Lecture seule stricte** : n'écrit aucun fichier, pas même `metadata.json` — ce n'est pas une passe du pipeline. `/status <slug>` zoome sur une story, `/status --all` inclut les livrées |
| `/grill`             | **Passer une idée au gril** — t'interroge sans relâche sur une idée, une décision ou un document existant (`/grill 047-f-filtres` éprouve son plan) jusqu'à une compréhension partagée : un arbre de décision parcouru round par round, une réponse recommandée à chaque question, le code lu avant de te demander ce qu'il dit déjà. **N'écrit aucun fichier** : rend une synthèse (tranché / lu dans le code / contredit / resté ouvert) et nomme le skill qui l'intégrera. Le moment le plus rentable : entre le plan et l'implémentation, dans une session neuve |
| `/test-scenario`     | Tester un scénario utilisateur via Playwright MCP (navigateur piloté en live)              |
| `/adr`               | Rédiger un Architecture Decision Record (`docs/adr/NNNN-slug.md`) depuis un artifact (pitch, plan, review, report) ou un topic libre — format MADR léger, backlinks et index automatiques. **Tu n'as pas à y penser toi-même** : huit skills du pipeline te le proposent quand la décision le mérite (voir ci-dessous) |
| `/estimate`          | **Chiffrer le temps « tout compris » d'une story à facturer** (feature, refacto, tech) — toutes phases comprises (cadrage, implem, tests, review, doc, release en forfait fixe 30 min), pas seulement le code. Lit `brief.md`/`pitch.md`/`plan.md` selon ce qui existe (plus la matière est riche, plus c'est fiable), chiffre chaque phase justifiée par un signal + une marge d'incertitude, **en heures**, en **deux colonnes** (référence sans IA / temps réel avec assistant IA — l'écart = la marge). Produit `docs/story/NNN-<f\|r\|t>-<slug>/estimate.md`. Du temps, pas de montant. |
| `/doc-feature`       | **Cartographier une feature existante** en lisant le code (entités, flux, routes, services, templates, points d'extension) — stack-agnostique avec détection auto (Sylius, Symfony, autre). Produit `docs/feature-map/NNN-slug/overview.md`. Utile pour onboarder sur un module legacy ou documenter une zone du code jamais passée par le pipeline. À distinguer de `/sync` (qui met à jour une doc d'intention récente). |
| `/help`              | Ce sommaire — pour se rappeler le workflow et les skills disponibles                       |

### Les ADR viennent à toi

Un ADR ne se perd pas au moment où on prend la décision — il se perd au moment où on passe à la suite. C'est pourquoi **huit skills proposent** d'en graver un, chacun au moment où il voit passer une décision d'architecture :

| Quand | Qui propose | Ce qu'il a sous les yeux |
|---|---|---|
| Au cadrage | `/feature-plan`, `/refactor-plan`, `/tech-plan` | §Approche retenue et §Alternatives écartées — le plan a déjà fait le travail d'argumentation |
| À l'exécution | `/feature-implem`, `/refactor-implem`, `/tech-implem` | Un choix tranché **hors plan**, en cours de route : le cas le plus précieux, rien d'autre ne le trace |
| À la clôture | `/review`, `/sync` | Une décision jamais justifiée, un écart d'approche assumé, un réalignement durable |

La proposition tient en **une ligne**, arrive **en clôture**, et n'attend **aucune réponse** : tu lances `/adr` si tu veux, sinon tu continues. Elle n'apparaît que si la décision passe le test d'ADR-ité (structurante · coûteuse à inverser · alternative sérieuse écartée · question du futur lecteur — 3 sur 4 minimum) et qu'aucun ADR ne couvre déjà le sujet. Le silence est le cas normal.

Des plugins complémentaires (ex: `sylius`, `symfony`) peuvent exposer des skills plus tactiques (procédures spécifiques au framework : créer une Resource, diagnostiquer un Twig Hook, etc.). Ils se combinent naturellement avec le workflow via l'auto-découverte de Claude Code.

## Règles framework

Le workflow établit le stack du projet (Symfony, Sylius) et charge les références correspondantes, **bundlées avec le plugin**. Il lit `docs/stack.md` s'il existe (produit par `/forge:stack`) : c'est un simple chargement, sans scan. Sinon, il le détecte depuis `composer.json` / `package.json`. Dans une même session, il ne relit pas ce qu'une skill précédente a déjà chargé.

- procédure de chargement / détection du stack
- règles Symfony — Doctrine, services, forms, Twig, QA, sécu, perf
- delta e-commerce Sylius — Resources, channels, thèmes, Twig Hooks…

Les skills concernées chargent ces références automatiquement — rien à lire manuellement. Les conventions propres à ton projet (commandes QA exactes, credentials de test, noms de thèmes utilisés, branches…) vivent dans l'`AGENTS.md` à la racine du projet — les skills le lisent en complément des références stack.

## Outillage et autorisations

Les skills d'implémentation (`/feature`, `/refactor`, `/tech`) **n'imposent aucun outillage** : elles ne présument ni de ton gestionnaire de paquets, ni de ton lanceur de tests. Les commandes réelles se lisent dans ton `AGENTS.md`, la référence stack, ou ton manifeste de tâches (`Makefile`, `package.json`, `composer.json`, `justfile`…) — et si elles ne les trouvent pas, elles demandent au lieu de deviner.

Conséquence pratique : **c'est à ton projet de pré-autoriser son outillage**, dans son `.claude/settings.json`. Sans ça, Claude Code demandera confirmation à chaque commande de build ou de test — c'est fonctionnel, juste bavard.

```jsonc
{
  "permissions": {
    "allow": ["Bash(make:*)", "Bash(vendor/bin/*:*)", "Bash(npm:*)"]
  }
}
```

C'est aussi le bon endroit pour poser une interdiction **dure** (`permissions.deny`) : contrairement aux `allowed-tools` d'un skill — qui ne font que pré-autoriser — un `deny` projet est souverain et ne se contourne pas.
