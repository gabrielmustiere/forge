# Vision — Forge Board

> Pitch en une phrase : un **atelier de pilotage** pour le PO non-technique et le dev solo qui travaillent avec le workflow forge, qui résout **le fossé entre exprimer un besoin et le voir cadré** en projetant les stories en kanban **et** en permettant de déclencher les skills de cadrage forge directement depuis l'interface, sur un repo cloné localement.

_Document vivant — enrichi au fil du cycle de vie, refondu lors d'un pivot stratégique. Date de dernière mise à jour : 2026-07-08._

_Nom « Forge Board » provisoire — à confirmer._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture du haut vers le bas = ordre chronologique. Détails fins dans `git log`.

| Date | Nature | Axe | Motif |
|------|--------|-----|-------|
| 2026-07-08 | Pivot | — | Refonte depuis `docs/vision.md.archive-2026-07-08` — motif : l'app passe de « miroir qui observe » à « atelier qui agit » ; nouvelle audience cible (PO non-technique) ; l'app clone, exécute les skills de cadrage via Symfony AI et pousse `docs/story/` sur le repo. |

## Le problème

Le workflow forge produit ses stories via des **skills lancés en terminal** (`/forge:feature-interview`, `/forge:feature-pitch`…). Pour entrer dans ce workflow — exprimer un besoin, le cadrer, le voir devenir une story dans le repo — il faut aujourd'hui savoir ouvrir un terminal, se placer dans le bon repo et invoquer une commande. C'est une **barrière d'accès infranchissable pour un profil non-technique** : un PO qui a une idée de feature n'a *aucune porte d'entrée* dans le workflow. Il doit passer par un dev, qui devient un goulot d'étranglement entre l'intention et le cadrage.

En parallèle, le board existant sait déjà *afficher* l'avancement des stories (kanban lecture seule), mais il s'arrête là : il **observe**, il ne laisse rien **produire**. Voir qu'une story existe ne dit rien sur comment en créer une.

**Comment c'est résolu aujourd'hui** : le PO dicte son besoin à un dev, qui ouvre un terminal et lance les skills forge à sa place. Le board, lui, ne sert qu'à consulter l'état après coup.
**Pourquoi c'est insuffisant** : le PO ne peut pas amorcer un cadrage seul — il dépend d'un tiers technique disponible ; l'intention se perd ou se déforme dans la transmission orale ; le board voit l'avancement mais n'offre aucun levier pour le déclencher.
**Ampleur** : à chaque nouveau besoin produit — c'est le point d'entrée de tout le pipeline forge. Tant qu'il reste réservé au terminal, il exclut structurellement quiconque n'écrit pas de commandes.

## L'audience

### Utilisateur principal

- **Persona (cible stratégique) : le PO non-technique.** Il a des idées de features, connaît le produit et les utilisateurs, mais ne touche ni terminal ni Claude Code. Aujourd'hui il ne peut qu'expliquer son besoin à un dev et espérer qu'il soit bien retranscrit.
- **Volume cible** : un profil en ligne de mire pour le pivot. Le POC ne l'ouvre pas encore techniquement (cf. « POC mono-utilisateur » ci-dessous) — c'est la boussole, pas la population du POC.
- **Ce qui le bloque aujourd'hui** : aucune interface pour exprimer un besoin et le transformer en story cadrée sans passer par un dev en terminal.

### Utilisateurs secondaires

- **Le dev solo forge (socle hérité)** — il utilise déjà le board pour se resituer sur l'avancement de ses stories, et pilote le cadrage. Le pivot lui ajoute un point d'entrée unique pour déclencher le cadrage depuis l'interface, plutôt que de jongler entre clones et terminaux.

### POC — mono-utilisateur assumé

Le POC se fait **en solo** : le dev joue à lui seul les deux rôles (il déclenche le cadrage pour valider le parcours de bout en bout). Le **vrai multi-utilisateur** (comptes, déploiement, permissions PO ≠ dev) est **déféré à un horizon ultérieur** — on veut prouver le mécanisme avant d'ouvrir l'app à un second humain.

### Hors cible explicite

- Les équipes multi-utilisateurs à comptes/rôles distincts — **pas au POC** (repoussé, pas nié : c'est l'horizon d'ouverture au PO).
- Les développeurs qui n'utilisent pas forge : sans la convention `docs/story/`, l'app n'a ni à projeter ni à produire.

## La proposition de valeur

### Bénéfice utilisateur

- **Pour le PO** : passer d'une idée à une **story cadrée poussée dans le repo** — brief, pitch — **sans dev et sans terminal**. L'intention n'est plus déformée par une transmission orale : le PO l'exprime, le skill la cadre, le résultat atterrit versionné dans le repo.
- **Pour le dev** : garder la lecture kanban de l'avancement **et** déclencher un cadrage depuis un point unique, sans quitter l'app ni se replacer manuellement dans un repo.

### Pourquoi nous, plutôt qu'eux

Un outil de ticketing générique (Linear, Jira, GitHub Issues) collecte une idée brute mais ne la **cadre** pas : il produit un titre et une description, pas un `pitch.md` structuré conforme à la méthode forge. Forge Board orchestre les **skills de cadrage réels** sur le repo réel, et livre un artefact exploitable directement par la suite du pipeline (`/feature-plan`, `/feature-implem`).

### Unfair advantage

La connaissance intime de la convention forge (`docs/story/NNN-<f|r|t>-<slug>/`, livrables par skill, tracks) **couplée** à l'orchestration des skills via **Symfony AI** sur un repo cloné localement. C'est cette combinaison — convention + moteur d'exécution headless — qu'aucun outil générique ne sait reproduire.

## Métriques de succès

North Star **hiérarchisée** : le pivot introduit une métrique de *production* qui devient primaire, tout en conservant la métrique de *lisibilité* héritée comme socle. On assume ce double axe, avec une vigilance explicite (cf. hypothèse #4) : une boussole à deux têtes risque de diluer les arbitrages, donc la production prime en cas de conflit.

### North Star

1. **Primaire — Besoin exprimé → story cadrée poussée, sans dev.** Taux de réussite et délai du parcours « le PO exprime un besoin » → « une story cadrée (`docs/story/…`) est produite et poussée sur le repo » sans intervention technique. C'est la métrique qui dit que le pivot réussit. Mesurable par observation directe du parcours de bout en bout.
2. **Socle hérité — Temps pour se resituer.** Délai entre « je veux savoir où en est le projet X » et « je le sais » (lecture du tableau). Reste mesurable par observation directe.

### Métriques secondaires

- **Acquisition** : sans objet au POC (usage personnel).
- **Activation** : un cadrage déclenché depuis le board qui aboutit à un livrable poussé sur le repo.
- **Rétention** : le board est utilisé pour *amorcer* des cadrages, pas seulement pour consulter l'avancement.
- **Monétisation** : sans objet.

### Fidélité (métrique de qualité héritée)

**Zéro écart** entre l'état affiché sur une carte et l'état réel des fichiers du repo — la projection kanban ne doit jamais mentir. Ce principe de la vision d'origine survit au pivot : la moitié « lisibilité » de la valeur en dépend.

### Seuils

- À 3-6 mois (POC) : le parcours « clone → cadrage déclenché depuis le board → `docs/story/` poussé » fonctionne de bout en bout en solo.
- À 1 an : un PO non-technique produit un cadrage exploitable sans dev, via une app ouverte à un second utilisateur.
- À 3 ans : horizon non planifié (POC à valider d'abord).

### Signal d'arrêt

Le cadrage produit depuis le board est trop souvent inexploitable (l'IA headless ne suffit pas, ou le PO ne parvient pas à exprimer un besoin assez net), au point qu'on retourne systématiquement lancer les skills à la main en terminal.

## Principes produit

1. **L'app agit — mais reste bornée.** Forge Board n'observe plus seulement : il clone en local, exécute les skills de **cadrage** via Symfony AI, et pousse `docs/story/` sur le repo distant. C'est le cœur du pivot.
2. **Cadrage only — jamais de code.** L'app ne génère **jamais** de code applicatif (`src/`, tests, migrations). Elle s'arrête au cadrage (interview → pitch, éventuellement plan). L'implémentation reste l'affaire du dev, en terminal. C'est la **ligne rouge permanente** qui borne « l'app agit ».
3. **La projection lecture-seule est préservée.** Le kanban qui reflète l'avancement (état déduit des fichiers, jamais saisi) reste intact — il porte la moitié de la valeur. La lecture ne ment jamais (Fidélité).
4. **La convention forge est le contrat.** Identité d'une story = `NNN-<f|r|t>-<slug>` ; le mapping fichiers → étape et la structure `docs/story/` restent la source de vérité, en lecture comme en production.
5. **Prouver avant d'ouvrir.** On valide le mécanisme en solo (POC mono-utilisateur) avant d'investir dans comptes, déploiement et permissions PO.

## Anti-objectifs

Ce qu'on **refuse explicitement** de faire, et pourquoi :

- **Générer du code applicatif depuis l'app** — l'app cadre, elle n'implémente pas. Toucher à `src/` reste hors périmètre, définitivement (principe #2).
- **Multi-utilisateur / comptes / déploiement au POC** — on prouve le mécanisme en solo d'abord ; l'ouverture au PO non-technique est un horizon, pas le POC.
- **Piloter le cycle de vie git au-delà du strict nécessaire** — l'app clone et pousse `docs/story/`, mais ne devient pas un client git généraliste (branches, merges, résolution de conflits à la main, gestion de PR).

> **Anti-objectifs abandonnés lors du pivot** (ils appartenaient à la vision « miroir » archivée) : « l'app observe, elle n'agit pas » ; « lecture seule absolue — l'app ne modifie jamais le repo » ; « pas de cycle de vie git ». Le pivot les révoque assumément : l'app agit, écrit et pousse.

## Hypothèses critiques

| # | Hypothèse | Comment l'invalider | Statut |
|---|-----------|---------------------|--------|
| 1 | Symfony AI peut exécuter un skill de cadrage forge **en headless**, de façon fiable, et produire un livrable (`brief.md`, `pitch.md`) réellement exploitable | Déclencher un skill de cadrage depuis le board sur un repo cloné réel et vérifier la qualité du `docs/story/` produit vs un lancement manuel en terminal | À tester |
| 2 | Un PO non-technique peut exprimer, via une GUI, un besoin **assez net** pour amorcer un skill sans l'aide d'un dev | Faire cadrer un besoin à un profil non-technique via l'interface et mesurer si le livrable est exploitable sans reprise | À tester |
| 3 | Le **commit + push automatique** de contenu généré vers le repo distant est **maîtrisable** (pas de pollution, pas d'écrasement, pas de fuite) | Prototyper le push (branche dédiée ? étape de validation avant push ?) et éprouver les cas limites (repo occupé, conflit, contenu douteux) | À tester — **à border au `/stack`** |
| 4 | Une North Star **double** (produire + se resituer) reste une boussole utilisable sans diluer les arbitrages | Observer sur plusieurs décisions produit si le double axe tranche ou paralyse ; si dilution, promouvoir la production comme unique North Star | À surveiller |
| 5 | *(héritée)* La structure `docs/story/` et les noms de livrables restent assez réguliers pour déduire l'étape d'une story de façon fiable | Scanner plusieurs stories réelles et vérifier le déterminisme du mapping | Éprouvée par les stories 001-007 livrées |

## Risques externes

- **Fiabilité de l'IA headless (Symfony AI)** : un skill de cadrage exécuté sans humain dans la boucle peut produire un livrable médiocre ou hors-sujet. Mitigation : garder un point de relecture avant le push, cadrer le POC sur un seul skill maîtrisé avant d'élargir.
- **Push automatique vers un repo tiers** : pousser du contenu généré non relu est un risque de pollution / d'écrasement / de fuite d'information. Mitigation : brancher le push sur une branche dédiée et/ou une étape de validation — à trancher au `/stack`.
- **Évolution du plugin forge** : un renommage de livrable ou un changement de convention casse à la fois le mapping (lecture) et l'orchestration des skills (production). Mitigation : centraliser la connaissance de la convention en un point unique.
- **Accès repo distant** : quotas, authentification, latence pour cloner et pousser. Mitigation : abstraire l'accès repo derrière une interface, prévoir un fallback.

## Horizons

### 3-6 mois (POC)

- **Bouton clone** : depuis la vue kanban d'un projet, cloner le repo GitHub/GitLab dans `private/`.
- **Déclencher un skill de cadrage** depuis le board sur le repo cloné (via Symfony AI), en solo.
- **Produire et pousser** le `docs/story/` résultant sur le repo distant.
- Le tout en **mono-utilisateur** : prouver le mécanisme de bout en bout.

### 1 an

- **Ouverture au PO non-technique** : comptes, déploiement, permissions PO ≠ dev.
- **UX d'expression de besoin** dédiée au profil non-technique (formulaire guidé, dialogue).
- Éventuel enchaînement de plusieurs étapes de cadrage (interview → pitch → plan).

### 3 ans

Non planifié à ce stade — le POC doit d'abord valider que le cadrage produit depuis le board est exploitable.

## Notes pour les features à venir

Pointeurs bruts pour `/product-backlog` et `/feature-pitch` — **ne pas concevoir ici, juste lister** :

- **Clone local d'un repo** : bouton depuis le kanban ; cible `private/` ; gérer clone déjà présent (re-clone ? pull ?), auth (le token de lecture stocké suffit-il pour un `git clone` HTTPS ?), feedback (clone long / échec).
- **Exécution d'un skill de cadrage via Symfony AI** : le cœur du pivot — déclencher un skill headless sur le clone, capturer la sortie, gérer les erreurs. Candidat `/adr` (Symfony AI en headless : faisabilité, fiabilité).
- **Commit + push du livrable produit** : où pousser (branche dédiée ?), quand valider avant push, comment éviter pollution/écrasement. À border au `/stack` (risque externe).
- **Entrée d'expression de besoin (PO)** : l'UI par laquelle un besoin est saisi puis passé au skill — au POC en solo, enrichie pour le PO non-technique à 1 an.
- **Coexistence lecture distante (API) / clone local** : la lecture `docs/story/` via API (story 003) et le clone local cohabitent — clarifier qui sert quoi (projection vs production).
- **Ouverture multi-utilisateur** (horizon 1 an) : comptes, rôles PO/dev, déploiement, sécurité des tokens en contexte partagé.
