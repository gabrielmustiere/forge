![Forge](documentation/banner.png)

**Forge** — le pipeline de développement Claude Code stack-agnostique. Une seule skill installable, `workflow`, qui pilote tout le cycle de développement (de la vision projet jusqu'au commit) en un enchaînement d'étapes courtes, validées une à une.

- **Marketplace** : `forge`
- **Source** : `gabrielmustiere/forge` (ce repo)

> Les skills Symfony, Sylius et éditoriales vivent désormais dans une marketplace séparée : [`gabrielmustiere/skills`](https://github.com/gabrielmustiere/skills).

## Installation

Dans une session Claude Code ouverte sur n'importe quel projet :

```
/plugin marketplace add gabrielmustiere/forge
/plugin install workflow@forge
/reload-plugins
```

Les skills du plugin sont toujours namespacées par le nom du plugin :

```
/workflow:help
/workflow:feature-pitch
/workflow:doc-feature
```

Mettre à jour le catalogue : `/plugin marketplace update forge` puis `/reload-plugins`.

## Tutoriel — Prendre en main le plugin `workflow`

Le plugin `workflow` est le plus structurant de la Forge : il pilote tout le cycle de développement (de la vision projet jusqu'au commit) en un pipeline d'étapes courtes, validées une à une avec l'utilisateur. Ce tutoriel t'amène pas-à-pas de l'installation à ta première feature livrée.

### Philosophie en trois idées

1. **Une étape = une skill = un artefact.** Chaque skill produit un fichier markdown (`pitch.md`, `plan.md`, `review.md`, `report.md`) qui sert d'entrée à la suivante. Tu ne passes jamais à l'étape d'après sans validation explicite (`ok`, `go`, `validé`).
2. **Trois tracks symétriques selon la nature du changement.** Une feature visible utilisateur, un refacto qui ne change rien dehors, ou une évolution technique (perf/résilience/sécu/observabilité) qu'un monitoring voit. Le pipeline est le même, seuls les premiers skills changent.
3. **Stack-agnostique avec règles framework auto-chargées.** Le workflow détecte ton stack (Symfony, Sylius…) via `composer.json` / `package.json` et charge les bonnes conventions de QA, sécu, perf au bon moment. Tes conventions projet (commandes exactes, credentials de test…) vivent dans le `CLAUDE.md` à la racine.

### Carte mentale du pipeline

```
PHASE 0 (une fois)
  /workflow:vision           → docs/vision.md (problème, audience, North Star)
  /workflow:product-backlog  → docs/product-backlog.md (domaines, capacités, MVP/V2/V3)

CHOIX DU TRACK
  Feature (user-facing)  : feature-pitch → feature-plan → feature
  Refacto (comportement figé) : refactor-plan → refactor
  Tech (perf/sécu/obs)   : tech-plan → tech

FIN DE CYCLE (commune aux 3 tracks)
  review → commit → report → sync
```

Tout vit dans `docs/story/NNN-<f|r|t>-<slug>/` (compteur global → tri lexicographique = timeline du projet). Exemple : `docs/story/042-f-checkout-express/pitch.md`.

### Premier réflexe — `/workflow:help`

Avant tout, tape `/workflow:help` dans Claude Code. Tu obtiens le sommaire du pipeline, les tracks, et le rappel des artifacts. Quand tu es perdu en cours de feature ("je suis où ?"), c'est le geste à faire.

### Tour des skills, dans l'ordre où tu les rencontreras

#### Phase 0 — Poser le décor (documents vivants)

Ces deux skills se lancent en tout début de projet. Ils sont facultatifs au sens strict (tu peux sauter direct au track feature), mais fortement recommandés dès que le projet dépasse 3-4 features. **Leur particularité : ce sont des documents vivants** que tu reviendras enrichir, éditer ou pivoter tout au long de la vie du projet — pas des artefacts gravés une fois pour toutes.

Les deux skills partagent les **mêmes 4 modes d'usage**, et maintiennent chacun un **changelog interne** qui trace l'historique des évolutions (date, mode, axe ciblé, motif court) :

- **Création** — premier passage, fichier vierge. Atelier complet.
- **Enrichir** — ajout d'un élément sans toucher au reste (nouvelle audience, nouvel anti-objectif, nouvelle capacité, nouvelle ligne de backlog…). *Le cas le plus fréquent sur un projet vivant — quelques minutes au lieu d'une demi-journée.*
- **Éditer** — correction ou reformulation d'un élément existant (préciser un principe vague, ajuster une métrique, reformuler une capacité, repriorisation).
- **Pivot** — refonte complète. L'ancien fichier est archivé sous `docs/<nom>.md.archive-AAAA-MM-JJ` et un nouveau est rédigé. Typiquement, un pivot de vision entraîne un pivot du backlog.

Si l'évolution est ciblée (`Enrichir` ou `Éditer`), le skill saute la phase de challenge complète et déroule une mini-procédure dédiée. Le mode est **toujours demandé explicitement** quand le fichier existe — jamais deviné.

- **`/workflow:vision`** — Atelier de cadrage qui produit `docs/vision.md`. Claude joue le rôle d'un sparring partner et challenge tes réponses : *quel problème exactement, pour qui, comment mesure-t-on le succès (North Star), quels principes non-négociables, et qu'est-ce qu'on refuse explicitement de faire (anti-objectifs) ?* Le document devient le verrou d'alignement de toutes les features futures. Relance-le en mode `Enrichir` quand une nouvelle audience ou un nouvel anti-objectif émerge, en mode `Éditer` pour corriger un point, en mode `Pivot` lors d'un changement stratégique majeur.

- **`/workflow:product-backlog`** — Une fois la vision validée, ce skill la traduit en carte des **domaines fonctionnels** → **capacités** → **parcours utilisateur** → **règles transverses** → **backlog priorisé MVP/V2/V3**. Il pose le périmètre fonctionnel et l'ordre de bataille. Relance-le en mode `Enrichir` à chaque nouvelle capacité ou feature à intégrer au backlog, en mode `Éditer` pour repriorisation, en mode `Pivot` (typiquement après un pivot de vision). Un enrichissement de vision non répercuté sur le backlog est un signal fort à corriger.

#### Track feature — Apporter de la valeur utilisateur

Pour tout changement qu'un utilisateur final ou un admin peut décrire ("je vois maintenant un bouton X qui fait Y"). C'est le track le plus complet, en 3 phases avant le commit.

- **`/workflow:feature-pitch`** — Atelier de cadrage de l'idée. Claude lit `docs/vision.md` et `docs/product-backlog.md` pour challenger l'alignement (cette feature sert quelle capacité ? quel principe ? quel impact North Star ?), puis cadre le pitch : problème utilisateur, persona, valeur, scope MVP vs hors-scope, critères d'acceptation, risques. Produit `docs/story/NNN-f-slug/pitch.md`. **Refuse les formulations vagues** — c'est la skill qui te force à savoir ce que tu fais avant de coder.

- **`/workflow:feature-plan`** — Une fois le pitch validé, plan technique : architecture, modèle de données, contrats d'API, impacts existants, stratégie de migration, plan de tests. Produit `docs/story/NNN-f-slug/plan.md`. Le plan est validé avant écriture de la moindre ligne de code.

- **`/workflow:feature`** — Implémentation guidée, sous-tâche par sous-tâche, avec QA continue (lint, types, tests) à chaque étape. Tu valides chaque sous-tâche avant de passer à la suivante. Produit le code, les migrations, les tests.

#### Track refacto — Comportement figé, code restructuré

Pour restructurer du code (dette, couplage, préparer une feature à venir, extraire un service) **sans toucher au comportement externe** (mêmes réponses, events, logs, timings).

- **`/workflow:refactor-plan`** — Cadrage : motivation, périmètre cible, **tests de caractérisation** à poser comme verrou avant de modifier, étapes incrémentales réversibles. Produit `docs/story/NNN-r-slug/plan.md`.

- **`/workflow:refactor`** — Exécution avec **verrou tests d'abord**, puis étapes incrémentales : à chaque étape les tests doivent rester verts. Si une étape casse, on revient et on découpe plus fin.

#### Track tech — Perf, résilience, observabilité, sécu (non user-facing)

Pour les changements qu'un observateur externe (test, monitoring, log consumer, autre service) détecte, mais pour **mieux** : plus rapide, plus résilient, plus sûr. Cache, retry, circuit breaker, queue async, logs structurés, index SQL, CSP, bump de CVE…

- **`/workflow:tech-plan`** — Cadrage avec **métrique cible chiffrée obligatoire** (ex: "latence p95 < 200 ms vs 800 ms aujourd'hui"), baseline à mesurer avant toute modif, kill switch activable, étapes incrémentales mesurées. Produit `docs/story/NNN-t-slug/plan.md`.

- **`/workflow:tech`** — Exécution : tu mesures la baseline, tu poses le kill switch, puis chaque étape se termine par une nouvelle mesure. Si la métrique régresse, on annule.

#### Fin de cycle — Commune aux trois tracks

Après l'implémentation, le pipeline converge sur quatre skills qui s'enchaînent toujours dans le même ordre.

- **`/workflow:review`** — Code review du diff : sécurité (OWASP, secrets), qualité (lint, typing, complexité), conformité au plan (ce que dit `plan.md` est-il bien là ?), non-régression. Produit `review.md` avec un statut bloquant / non-bloquant.
- **`/workflow:commit`** — Génère un message Conventional Commits en français à partir du diff et du contexte de la story, puis commit et push. Pas de message générique : il décrit l'**intention** (le pourquoi), pas le quoi.
- **`/workflow:report`** — Compte rendu honnête de ce qui a été fait **vs ce qui était prévu** dans `pitch.md` / `plan.md`. Liste les écarts, les compromis pris en cours de route, les TODOs ouverts. Produit `report.md`.
- **`/workflow:sync`** — Si le report révèle que la doc d'intention a divergé du code livré, ce skill réaligne `pitch.md` / `plan.md` sur la réalité, pour que la story reste lisible dans 6 mois.

### Track "fast" — Bugfix express (hors pipeline structuré)

Pour les modifs qui cochent **toutes** ces cases : moins de 3 fichiers, pas de migration, pas de nouveau service/entité, pas d'impact transverse. Tu codes, tu lances la QA du stack, tu vises `/workflow:review` (optionnel) puis `/workflow:commit`. Pas de feature-pitch ni de design pour un typo ou un nullcheck oublié.

### Utilitaires hors pipeline

- **`/workflow:test-scenario`** — Joue un scénario utilisateur en live dans un navigateur piloté par Playwright MCP. Utile pour valider une feature en bout de chaîne.
- **`/workflow:adr`** — Rédige un Architecture Decision Record (`docs/adr/NNNN-<slug>.md`, format MADR léger) sur une décision technique structurante. Trois modes d'entrée : depuis un artifact existant (`pitch.md`, `plan.md`, `review.md`, `report.md`), depuis un slug de story, ou depuis un topic libre. Mode atelier (contexte, drivers, options, conséquences) avec validation explicite avant écriture, puis backlinks automatiques dans l'artifact source, dans l'index `docs/adr/README.md` et dans le `report.md` de la story si applicable.
- **`/workflow:doc-feature`** — Cartographie une feature **existante** (legacy non documentée) en un `overview.md` rétro-ingénierié. Stack-aware (Symfony, Sylius).
- **`/workflow:migrate-legacy`** — Migre les anciens formats workflow : dossiers `docs/story/<f|r|t>-NNN-<slug>/` → `NNN-<f|r|t>-<slug>/`, artifacts `feature.md`/`design.md` → `pitch.md`/`plan.md` dans les stories feature, et `feature.md` → `overview.md` dans `docs/feature-map/`. Via `git mv` pour préserver l'historique.
- **`/workflow:import-external`** — Importe une doc produite par Spec Kit, BMAD-METHOD ou GSD vers le format workflow.
- **`/workflow:release`** — Tag SemVer annoté + `CHANGELOG.md` Keep a Changelog + release GitHub. À lancer en fin de jalon, pas après chaque feature.

### Première feature de bout en bout — exemple concret

Imaginons qu'on démarre un projet de gestion d'événements, et qu'on veut livrer la première feature : "permettre à un organisateur de publier une page d'événement".

```
Session 1 — Pose les fondations (une fois pour toute la vie du projet)
  /workflow:vision           → docs/vision.md
  /workflow:product-backlog  → docs/product-backlog.md

Session 2 — Première feature
  /workflow:feature-pitch    → docs/story/001-f-publier-page-evenement/pitch.md
  /workflow:feature-plan     → docs/story/001-f-publier-page-evenement/plan.md
  /workflow:feature          → code + migrations + tests
  /workflow:review           → docs/story/001-f-publier-page-evenement/review.md
  /workflow:commit           → commit + push
  /workflow:report           → docs/story/001-f-publier-page-evenement/report.md
  /workflow:sync             → réalignement éventuel pitch.md / plan.md
```

À l'issue de la session 2, `docs/story/001-f-publier-page-evenement/` contient cinq fichiers qui racontent l'histoire complète de la feature (du pitch à la livraison) — relisable dans 6 mois sans contexte.

### Bonnes pratiques pour démarrer

- **Valide explicitement à chaque étape.** Claude attend `ok` / `go` / `validé`. Si tu ne valides pas, il ne passe pas à la suite — c'est le verrou anti-dérive.
- **Garde ton `CLAUDE.md` à jour.** Commandes QA exactes (`./bin/phpunit`, `yarn build`…), credentials de test, noms de thèmes utilisés, branches. Les skills le lisent à chaque exécution.
- **Ne saute pas le `report.md`.** C'est lui qui révèle les écarts entre intention et réalité, et donc l'utilité du `sync` qui suit.
- **Si tu hésites sur le track**, demande-toi : *un user voit-il quelque chose de nouveau ?* (→ feature) *Un monitoring détecte-t-il une différence ?* (→ tech) *Personne ne voit la différence dehors ?* (→ refacto).
- **`/workflow:help` est ton GPS** quand tu perds le fil du pipeline.

## Licence

Distribué sous licence [Apache 2.0](LICENSE). © 2026 Gabriel Mustiere.
