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

     PHASES 1 À 3 — CADRER, IMPLÉMENTER, CLÔTURER : une story emprunte UN SEUL des trois tracks.
     La phase dit quand ; le track dit avec quelles skills. Dans chaque track ci-dessous :
     les plans = phase 1, l'implem = phase 2, review → report → sync → commit = phase 3.

                        TRACK FEATURE (valeur utilisateur, structurante)
 (optionnel : besoin flou)
 ┌───────────────────┐
 │ feature-interview │┄┐
 └─────────┬─────────┘ ┊ brief.md
           ▼           ▼
 ┌───────────────┐   ┌──────────────┐   ┌────────────────┐   ┌────────┐   ┌────────┐   ┌──────┐   ┌────────┐
 │ feature-pitch │──▶│ feature-plan │──▶│ feature-implem │──▶│ review │──▶│ report │──▶│ sync │──▶│ commit │
 └───────┬───────┘   └───────┬──────┘   └────────┬───────┘   └────┬───┘   └────┬───┘   └───┬──┘   └────┬───┘
         │                   │                   │                │            │           │           │
     pitch.md             plan.md         code+migrations     review.md    report.md    doc sync    commit
                                          +nouveaux tests                              +changelog    +push

                    TRACK REFACTO (comportement figé, code restructuré)
 ┌───────────────┐   ┌─────────────────┐   ┌────────┐   ┌────────┐   ┌──────┐   ┌────────┐
 │ refactor-plan │──▶│ refactor-implem │──▶│ review │──▶│ report │──▶│ sync │──▶│ commit │
 └───────┬───────┘   └────────┬────────┘   └────┬───┘   └────┬───┘   └───┬──┘   └────┬───┘
         │                    │                 │            │           │           │
      plan.md           verrou tests        review.md    report.md    doc sync    commit
                          + étapes                                   +changelog    +push
                        incrémentales

               TRACK TECH (perf, résilience, observabilité, sécu — non user-facing)
 ┌───────────┐   ┌─────────────┐   ┌────────┐   ┌────────┐   ┌──────┐   ┌────────┐
 │ tech-plan │──▶│ tech-implem │──▶│ review │──▶│ report │──▶│ sync │──▶│ commit │
 └─────┬─────┘   └──────┬──────┘   └────┬───┘   └────┬───┘   └───┬──┘   └────┬───┘
       │                │               │            │           │           │
    plan.md         baseline        review.md    report.md    doc sync    commit
                  + kill switch                              +changelog    +push
                + étapes mesurées

                       UTILITAIRES (hors pipeline, à la demande)
 ┌─────────────────┐    ┌─────┐    ┌──────┐
 │  test-scenario  │    │ adr │    │ help │  ← tu y es
 └─────────────────┘    └──┬──┘    └──────┘
 Playwright MCP live       │       ce sommaire
                           ▼
                  docs/adr/NNNN-slug.md
                  (depuis pitch/plan/review/report
                   ou topic libre)

 ┌──────────────┐    ┌─────────┐    ┌──────────┐
 │  doc-feature │    │ release │    │ estimate │
 └──────┬───────┘    └────┬────┘    └────┬─────┘
        │                 │              │
        ▼                 ▼              ▼
 docs/feature-map/    vX.Y.Z +       estimate.md
 NNN-slug/overview.md CHANGELOG.md   (temps facturable
 (rétro-doc à partir  + tag annoté    tout compris, en
  du code livré)      + GitHub rel.   heures : réf. + IA)
```

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

**Un format commun à tous les documents** : `brief.md`, `pitch.md`, `plan.md`, `review.md`, `report.md` et `estimate.md` partagent un contrat de format unique (`references/document-format.md`). Chaque document sert **un seul but**, dans **un seul registre** — annoncé dans son en-tête : le brief et le pitch sont **fonctionnels** (aucun nom de classe, de service ou de framework), les plans et la review sont **techniques**, le report est **factuel**, l'estimation est **économique**. Les documents de décision (review, report, estimation) ouvrent sur leur `## Synthèse` : on doit pouvoir ne lire qu'elle. Les trois plans partagent le même squelette de sections, quel que soit le track — leurs spécificités (caractérisation d'un refacto, kill switch d'un tech) sont des sections additionnelles. La timeline, elle, ne vit dans aucun `.md` : elle est dans le `metadata.json` de la story.

## Les deux axes : phases et tracks

Le pipeline se lit sur deux axes qu'il ne faut pas confondre :

- **La phase dit *quand*.** Quatre phases, dans l'ordre : **0** poser le décor (une fois par projet), puis **1** cadrer, **2** implémenter, **3** clôturer — traversées par chaque story.
- **Le track dit *avec quelles skills*.** À phase égale, une feature, un refacto et une évolution technique n'appellent pas les mêmes skills. Le track se choisit à l'entrée de la phase 1 et ne change pas les phases traversées : la phase 3 est identique pour les trois.

## Phase 0 — Poser le décor

Avant le premier track, en tout début de projet (ou lors d'un pivot), poser le décor **une fois pour toutes** : pourquoi ce produit existe, ce qu'on va construire, et sur quoi on marche. Les trois documents sont **vivants** (4 modes : Création / Enrichir / Éditer / Pivot, avec changelog) et vivent à la racine de `docs/`, pas dans une story.

| Skill              | Rôle                                                                                                | Produit                   |
|--------------------|-----------------------------------------------------------------------------------------------------|---------------------------|
| `/vision`          | Atelier challengeur sur problème, audience, valeur, North Star, principes, anti-objectifs            | `docs/vision.md`          |
| `/product-backlog` | Atelier fonctionnel : domaines → capacités → parcours → règles transverses → backlog MVP/V2/V3       | `docs/product-backlog.md` |
| `/stack`           | Scanne tous les manifestes (langages, backend, frontend, données, ops, CI), interroge pour combler les trous, prouve chaque techno par un fichier source | `docs/stack.md` |

**Vision** — pourquoi ce produit existe, pour qui, quelle valeur il crée, comment on mesure le succès, et ce qu'on refuse explicitement de faire. Lue par `/product-backlog` puis par `/feature-pitch` à chaque nouvelle feature pour challenger l'alignement (problème adressé, audience, principes, impact North Star). Pas de lancement à chaque feature : c'est un document fondateur, révisé seulement lors d'un pivot.

**Product-backlog** — le pont entre la stratégie (vision) et le cadrage feature par feature : carte des capacités fonctionnelles + backlog priorisé de features candidates. On le révise quand le périmètre évolue (nouvelle capacité, repriorisation, pivot). `/feature-pitch` le lit pour reprendre le pitch initial d'une ligne de backlog, ses capacités couvertes et ses dépendances. **Facultatif** (on peut aller direct vision → feature-pitch), mais recommandé dès qu'on a plus de 3-4 features pressenties.

**Stack** — sur quoi on marche, avant de cadrer la moindre évolution. Indispensable sur un legacy non documenté, utile sur un projet neuf dès que la stack se stabilise. Lu **en priorité par les skills techniques** (`/feature-plan`, `/refactor-plan`, `/tech-plan`, `/review`) pour décider sur des bases factuelles. Il **constate** l'existant — il ne justifie pas un choix (c'est le rôle d'`/adr`) ni ne décide d'une évolution (`/tech-plan`).

`docs/stack.md` est un document **vivant** (mêmes 4 modes que vision/backlog : Création / Enrichir / Éditer / Pivot, avec changelog). Il est lu **en priorité par les tracks techniques** (`/feature-plan`, `/refactor-plan`, `/tech-plan`, `/review`) pour décider sur des bases factuelles. Il constate l'existant — il ne justifie pas un choix (c'est le rôle d'`/adr`) ni ne décide d'une évolution (`/tech-plan`).

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
| 3     | `/review`            | Code review (sécu, perf, qualité, conformité plan)                               | `docs/story/NNN-f-slug/review.md`  |
| 3     | `/report`            | Documenter ce qui a été fait vs ce qui était prévu                               | `docs/story/NNN-f-slug/report.md`  |
| 3     | `/sync`              | Réaligner pitch et plan avec la réalité du code                                  | Mise à jour `pitch.md` + `plan.md` |
| 3     | `/commit`            | En dernier : embarque code + report + docs réalignées, Conventional Commits + push | Commit                           |

## Track refacto — Comportement figé, code restructuré

Pour restructurer du code sans toucher au comportement externe (dette, couplage, préparer une feature à venir, extraire un service, décomposer une god class…).

**Principe** : comportement externe strictement préservé, verrou tests de caractérisation AVANT de toucher, exécution incrémentale réversible.

| Phase | Skill             | Rôle                                                              | Produit                           |
|-------|-------------------|-------------------------------------------------------------------|-----------------------------------|
| 1     | `/refactor-plan`  | Cadrer un refacto (motivation, cible, caractérisation, étapes)   | `docs/story/NNN-r-slug/plan.md`   |
| 2     | `/refactor-implem`| Exécuter : verrou tests puis étapes incrémentales, non-régression | Code restructuré + tests          |
| 3     | `/review`         | Code review focus non-régression                                  | `docs/story/NNN-r-slug/review.md` |
| 3     | `/report`         | Documenter l'exécution vs le plan                                 | `docs/story/NNN-r-slug/report.md` |
| 3     | `/sync`           | Réaligner le plan si la stratégie a dévié                         | Mise à jour `plan.md`             |
| 3     | `/commit`         | En dernier : embarque tout, souvent un commit par étape           | Commits                           |

## Track tech — Perf, résilience, observabilité, sécu (non user-facing)

Pour ajouter ou modifier une brique technique observable (latence, taux d'erreur, format de log, timing) qui n'apporte pas de nouvelle valeur utilisateur fonctionnelle : cache, retry, circuit breaker, queue async, logs structurés, index SQL, health check, CSP, bump de CVE…

**Principe** : métrique cible chiffrée obligatoire, baseline mesurée AVANT toute modif, kill switch activable, étapes incrémentales avec mesure après chaque.

| Phase | Skill          | Rôle                                                               | Produit                           |
|-------|----------------|--------------------------------------------------------------------|-----------------------------------|
| 1     | `/tech-plan`   | Cadrer l'évolution (problème, brique, métriques cibles, rollback) | `docs/story/NNN-t-slug/plan.md`   |
| 2     | `/tech-implem` | Exécuter : baseline, kill switch, étapes mesurées, validation      | Code + config + observabilité     |
| 3     | `/review`      | Code review (kill switch, compatibilité, non-régression)          | `docs/story/NNN-t-slug/review.md` |
| 3     | `/report`      | Documenter les critères atteints / non atteints vs le plan         | `docs/story/NNN-t-slug/report.md` |
| 3     | `/sync`        | Réaligner le plan si la stratégie a dévié                          | Mise à jour `plan.md`             |
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

## Phase 3 — Clôturer (`/review`, `/report`, `/sync`, `/commit`)

Les trois tracks (feature, refacto, tech) partagent les mêmes étapes de clôture après l'implémentation, **dans cet ordre**. Ce sont des skills communs : seuls le contenu et le ton des artifacts changent selon le track. Le `/commit` vient **en dernier** : review, report et sync travaillent tous sur le diff du working tree (non committé), puis le commit embarque d'un coup le code, le `report.md` et les documents réalignés.

### `/report` — Documenter la livraison réelle
Crée `report.md` dans le dossier de track (`docs/story/NNN-<f|r|t>-slug/`). Documente **ce qui a été fait vs ce qui était prévu** : écart entre intention (`pitch.md`/`plan.md`) et exécution réelle — ajouts non prévus, choix qui ont dévié, dette laissée, métriques effectivement obtenues (en track tech : valeur cible vs mesurée, kill switch armé ou non). C'est la **mémoire factuelle** de la livraison, utile pour les rétros, l'onboarding futur et la traçabilité produit.

### `/sync` — Réaligner la doc d'intention avec le code
Met à jour `pitch.md` ou `plan.md` quand l'implémentation a obligé à dévier (modèle de données ajusté, route renommée, lib remplacée, étape rajoutée…). Le but : que la doc d'intention **se lise comme si elle avait été écrite correctement dès le départ**, sans cicatrice de l'historique de décisions. Une **phase finale** propage aussi les écarts vers les **documents de phase 0** quand ils existent (modes Enrichir/Éditer, toujours validés) : `stack.md` gagne une dépendance/service détecté dans le diff, `product-backlog.md` marque la feature livrée, et `vision.md` **évolue avec le produit** — une feature qui étend le périmètre enrichit la vision, une feature qui contredit un anti-objectif le fait retirer. La vision **suit** les features, elle ne les bloque pas ; seule une divergence stratégique large est renvoyée vers un `/vision` en mode Pivot.

**Différence `/report` vs `/sync`** : `/report` raconte l'histoire de la livraison **une fois pour toutes** (document figé, lecture chronologique). `/sync` met à jour le document d'intention **en place**, comme une révision documentaire. Les deux sont complémentaires : on garde la trace dans `report.md` et on rend les docs d'intention à nouveau fiables pour les futurs lecteurs.

### `/report-and-sync` — Les deux en une passe
Enchaîne `/report` puis `/sync` sur une même story, dans la foulée, **avant le commit**. Court-circuite le `/sync` si le report conclut à une conformité totale. Pratique juste après une livraison pour préparer la doc en une seule commande.

### `/commit` — Construire et pousser les commits
**Dernière étape** : une fois report et sync passés, lit le diff git, regroupe les changements en lots cohérents, propose des messages au format **Conventional Commits en français** (`feat(scope): …`, `fix(scope): …`, `refactor(scope): …`, etc.), demande validation, commit et push. Le commit embarque ainsi le code, le `report.md` et les docs réalignées en une fois. Sur un track refacto, on a souvent **un commit par étape** pour préserver la réversibilité.

> **Ne pas confondre `/sync` avec `/doc-feature`** : `/sync` recale un document d'intention récent que tu viens de modifier dans un track structuré. `/doc-feature` (voir Utilitaires) cartographie une feature **ancienne ou jamais passée par le pipeline**, en partant du code livré, sans dossier de track préalable.

## Utilitaires (hors pipeline)

| Skill                | Rôle                                                                                       |
|----------------------|--------------------------------------------------------------------------------------------|
| `/test-scenario`     | Tester un scénario utilisateur via Playwright MCP (navigateur piloté en live)              |
| `/adr`               | Rédiger un Architecture Decision Record (`docs/adr/NNNN-slug.md`) depuis un artifact (pitch, plan, review, report) ou un topic libre — format MADR léger, backlinks et index automatiques |
| `/estimate`          | **Chiffrer le temps « tout compris » d'une story à facturer** (feature, refacto, tech) — toutes phases comprises (cadrage, implem, tests, review, doc, release en forfait fixe 30 min), pas seulement le code. Lit `brief.md`/`pitch.md`/`plan.md` selon ce qui existe (plus la matière est riche, plus c'est fiable), chiffre chaque phase justifiée par un signal + une marge d'incertitude, **en heures**, en **deux colonnes** (référence sans IA / temps réel avec assistant IA — l'écart = la marge). Produit `docs/story/NNN-<f\|r\|t>-<slug>/estimate.md`. Du temps, pas de montant. |
| `/doc-feature`       | **Cartographier une feature existante** en lisant le code (entités, flux, routes, services, templates, points d'extension) — stack-agnostique avec détection auto (Sylius, Symfony, autre). Produit `docs/feature-map/NNN-slug/overview.md`. Utile pour onboarder sur un module legacy ou documenter une zone du code jamais passée par le pipeline. À distinguer de `/sync` (qui met à jour une doc d'intention récente). |
| `/release`           | **Créer une release versionnée bout en bout** — détermine le bump SemVer (major/minor/patch) depuis les Conventional Commits depuis le dernier tag, met à jour `CHANGELOG.md` (format Keep a Changelog), crée un tag annoté `vX.Y.Z`, push, puis publie la release sur GitHub via `gh`. Demande validation avant toute action publique. Argument-hint : `[major\|minor\|patch] [--no-push] [--draft] [--pre <suffix>]`. |
| `/help`              | Ce sommaire — pour se rappeler le workflow et les skills disponibles                       |

Des plugins complémentaires (ex: `sylius`, `symfony`) peuvent exposer des skills plus tactiques (procédures spécifiques au framework : créer une Resource, diagnostiquer un Twig Hook, etc.). Ils se combinent naturellement avec le workflow via l'auto-découverte de Claude Code.

## Règles framework

Le workflow détecte automatiquement le stack du projet (Symfony, Sylius) via `composer.json` / `package.json` et charge les références correspondantes, **bundlées avec le plugin** :

- procédure de détection du stack
- règles Symfony — Doctrine, services, forms, Twig, QA, sécu, perf
- delta e-commerce Sylius — Resources, channels, thèmes, Twig Hooks…

Les skills concernées chargent ces références automatiquement après détection — rien à lire manuellement. Les conventions propres à ton projet (commandes QA exactes, credentials de test, noms de thèmes utilisés, branches…) vivent dans le `CLAUDE.md` à la racine du projet — les skills le lisent en complément des références stack.

## Outillage et autorisations

Les skills d'implémentation (`/feature`, `/refactor`, `/tech`) **n'imposent aucun outillage** : elles ne présument ni de ton gestionnaire de paquets, ni de ton lanceur de tests. Les commandes réelles se lisent dans ton `CLAUDE.md`, la référence stack, ou ton manifeste de tâches (`Makefile`, `package.json`, `composer.json`, `justfile`…) — et si elles ne les trouvent pas, elles demandent au lieu de deviner.

Conséquence pratique : **c'est à ton projet de pré-autoriser son outillage**, dans son `.claude/settings.json`. Sans ça, Claude Code demandera confirmation à chaque commande de build ou de test — c'est fonctionnel, juste bavard.

```jsonc
{
  "permissions": {
    "allow": ["Bash(make:*)", "Bash(vendor/bin/*:*)", "Bash(npm:*)"]
  }
}
```

C'est aussi le bon endroit pour poser une interdiction **dure** (`permissions.deny`) : contrairement aux `allowed-tools` d'un skill — qui ne font que pré-autoriser — un `deny` projet est souverain et ne se contourne pas.
