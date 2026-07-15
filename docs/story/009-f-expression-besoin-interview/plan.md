# Plan technique — Exprimer un besoin depuis le board et le cadrer en brief soumis en revue

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/009-f-expression-besoin-interview/`
> **Amont** : `pitch.md`
> **ADR** : `docs/adr/0002-execution-skills-cadrage-cli-claude-headless.md`

## Approche retenue

On calque **le socle asynchrone de la story 008** (clone) : une entité à état pilotée par des jobs Symfony Messenger, un port shell-out isolé derrière une interface (substituable par un double en test), et un rendu live sans reload. La nouveauté est un **dialogue multi-tours** : chaque message de l'utilisateur déclenche un job `RunInterviewTurn` qui shell-out `claude -p` (ADR-0002) dans le clone local du projet (`Project::getLocalPath()`, déjà persisté par la 008) ; le skill `feature-interview` pose ses questions et **rend la main** (prouvé au POC), la réponse est stockée, et la boucle continue via `--resume <session_id>` jusqu'à ce que le skill écrive `brief.md` dans le clone. La production du brief est détectée **sur le filesystem** (`git status --porcelain`), pas devinée dans le texte. À la validation, un second job `SubmitBrief` publie le brief sur une **copie de travail isolée** (branche `forge/<slug>` app-owned + push `--force` idempotent, pour qu'un `retry()` aboutisse après un push réussi mais une PR échouée) puis ouvre une **PR draft GitHub** via le client HTTP existant. À chaque état terminal (`Submitted`/`Abandoned` avec brief), le dossier de story non suivi est **purgé du clone maintenu** (`StoryWorkspaceCleaner`) pour éviter que la détection de brief d'une interview suivante ne se contamine. L'app tourne en **local mono-utilisateur** (stack.md) : `claude` s'exécute avec la **session locale ambiante** (OAuth), sans clé API ni `--bare` — le mode clé API reste la voie de durcissement serveur (suite ADR-0002).

### Mécanismes mobilisés

- **Symfony Messenger (transport Doctrine async)** : `RunInterviewTurn` et `SubmitBrief`, routés async comme `CloneRepository`. Handlers idempotents, échec métier traduit en état `Failed` **non re-propagé** (pattern `CloneRepositoryHandler`).
- **`symfony/process`** : shell-out `claude -p` (runner) et `git` (push), en tableau d'argv (pas d'interpolation shell → pas d'injection). Timeout borné, secret par **env** jamais en argv (`GIT_ASKPASS` + `bin/git-askpass.sh` réutilisé pour le push).
- **Port + `supports()` registry** : `InterviewRunnerInterface` (une impl) ; `PullRequestOpenerInterface` + `PullRequestOpenerRegistry` (mirroir `RepositoryReaderRegistry` — GitHub aujourd'hui, GitLab enfichable plus tard).
- **Client HTTP scopé `github.client`** : `POST /repos/{owner}/{repo}/pulls` (`draft: true`) en `auth_bearer`, `guardStatus()` (pattern `GitHubRepositoryReader`).
- **`TokenCipher`** : déchiffrement du token au plus près de l'exécution (jamais persisté en clair, jamais loggé).
- **Live Component + `DefaultActionTrait`** : composant `ProjectInterview` avec `LiveAction` (`start`, `send`, `conclude`, `validate`, `retry`, `abandon`) et **poll** sur les états `isInFlight()` (le worker fait le travail hors requête ; réhydratation de l'entité `LiveProp` à chaque cycle → pattern `ProjectCloneStatus`). `send` envoie un message de tour ; `conclude` pousse le `CONCLUSION_MESSAGE` (bouton « Conclure le cadrage », en écho au skill coopératif qui signale « prêt à conclure ») ; `retry` rejoue un dépôt `Failed`.
- **`Symfony\Component\Uid\Uuid`** : génération du `sessionId` de session `claude` à la création de l'interview.
- **Doubles de test via `services_test.yaml`** : `FakeInterviewRunner` + `FakePullRequestOpener` + `FakeBriefPusher` (aucun `claude`/`git`/réseau réel en test — pattern `FakeRepositoryCloner`).

### Alternatives écartées

- **Champs d'interview sur `Project`** : fige une seule interview par projet dans le schéma et n'est pas extensible aux autres skills à venir → entité `Interview` dédiée.
- **Transcript en colonne JSON sur `Interview`** : moins requêtable et non idiomatique → entité enfant `InterviewMessage` (OneToMany).
- **Créer la branche dans le clone maintenu (008)** : muterait le clone que le `git pull --ff-only` garde propre → copie de travail **isolée** pour le push.
- **Réimplémenter l'agent en PHP (Symfony AI)** : abandonne la fidélité au skill — écarté et gravé en ADR-0002.
- **Clé API + `--bare` dès la V1** : friction inutile (pas de clé, app locale) pour zéro gain au POC → session ambiante, clé API déférée au serveur.

## Modèle de données

### Nouvelle entité `App\Entity\Interview`

`src/Entity/Interview.php` — une interview de cadrage rattachée à un projet.

| Champ             | Type                                   | Nullable | Contrainte                                          |
|-------------------|----------------------------------------|----------|-----------------------------------------------------|
| `id`              | int (PK auto)                          | non      |                                                     |
| `project`         | `ManyToOne` (`Project`)                | non      | `JoinColumn(name: 'project_id', onDelete: 'CASCADE')` |
| `status`          | `InterviewStatus` (enumType)           | non      | `options: ['default' => 'awaiting']`                |
| `sessionId`       | string(255) (`session_id`)             | non      | UUID de session `claude` généré à la création       |
| `storySlug`       | string(255) (`story_slug`)             | oui      | `NNN-f-<slug>` de la story produite (une fois le brief détecté) |
| `pullRequestUrl`  | string(255) (`pull_request_url`)       | oui      | URL de la PR draft (une fois ouverte)               |
| `lastError`       | TEXT (`last_error`)                     | oui      | Raison lisible du dernier échec (sans token)        |
| `createdAt`       | datetime_immutable (`created_at`)      | non      |                                                     |
| `updatedAt`       | datetime_immutable (`updated_at`)      | oui      | Rebougé à chaque transition                         |
| `messages`        | `OneToMany` (`InterviewMessage`)       | —        | `mappedBy: 'interview'`, `cascade: ['persist','remove']`, `orderBy: ['createdAt' => 'ASC']` |

Attributs au niveau classe :

```php
#[ORM\Entity(repositoryClass: InterviewRepository::class)]
```

Méthodes de transition cohésives (statut + champs liés posés ensemble, jamais l'un sans l'autre — pattern `Project::markCloned()`) :

- `addUserMessage(string $content)` / `addAssistantMessage(string $content)` — crée l'`InterviewMessage`, met à jour `updatedAt`.
- `markThinking()` — un tour (ou le dépôt) part en tâche de fond ; efface `lastError`.
- `markAwaiting()` — le skill a rendu la main, on attend le prochain message.
- `markBriefReady(string $storySlug)` — brief détecté, pose `storySlug`, en attente de validation.
- `markSubmitting()` — dépôt lancé.
- `markSubmitted(string $pullRequestUrl)` — PR draft ouverte (terminal succès).
- `markFailed(string $reason)` — pose `lastError` ; état **récupérable** (l'utilisateur peut renvoyer / re-tenter).
- `markAbandoned()` — terminal, libère le créneau « 1 active/projet » (aucun effet distant).
- `lastUserMessage(): string`, `isFirstTurn(): bool` (aucun message assistant encore).

### Nouvelle entité `App\Entity\InterviewMessage`

`src/Entity/InterviewMessage.php` — un tour de conversation (fil ré-affichable après reload).

| Champ         | Type                              | Nullable | Contrainte                                             |
|---------------|-----------------------------------|----------|--------------------------------------------------------|
| `id`          | int (PK auto)                     | non      |                                                        |
| `interview`   | `ManyToOne` (`Interview`)         | non      | `JoinColumn(name: 'interview_id', onDelete: 'CASCADE')` |
| `role`        | `MessageRole` (enumType)          | non      | `user` / `assistant`                                   |
| `content`     | TEXT                              | non      |                                                        |
| `createdAt`   | datetime_immutable (`created_at`) | non      |                                                        |

### Nouvel enum `App\Enum\Type\InterviewStatus`

`src/Enum/Type/InterviewStatus.php` — backed string, même forme que `CloneStatus` (`label()`, `badgeTone()`, `icon()`).

| Case          | Valeur          | label        | badgeTone | Terminal ? |
|---------------|-----------------|--------------|-----------|------------|
| `Awaiting`    | `awaiting`      | En attente   | neutral   | non (actif) |
| `Thinking`    | `thinking`      | Réflexion…   | neutral   | non (poll) |
| `BriefReady`  | `brief_ready`   | À valider    | warning   | non (actif) |
| `Submitting`  | `submitting`    | Dépôt…       | neutral   | non (poll) |
| `Submitted`   | `submitted`     | Proposée     | ok        | **oui**    |
| `Failed`      | `failed`        | Échec        | danger    | non (récupérable) |
| `Abandoned`   | `abandoned`     | Abandonnée   | neutral   | **oui**    |

Une **interview active** = statut hors terminal (`Submitted`, `Abandoned`). `isInFlight()` (poll UI) = `Thinking` ou `Submitting`.

### Nouvel enum `App\Enum\Type\MessageRole`

`src/Enum/Type/MessageRole.php` — backed string `User = 'user'` / `Assistant = 'assistant'` (convention projet : enums dans `src/Enum/Type/`).

## Périmètre

### Fichiers à créer

| Fichier                                                        | Rôle                                                                                  |
|----------------------------------------------------------------|---------------------------------------------------------------------------------------|
| `src/Entity/Interview.php`                                     | Entité à état de l'interview (transitions cohésives).                                  |
| `src/Entity/InterviewMessage.php`                              | Un tour du fil (role/content/createdAt).                                               |
| `src/Enum/Type/InterviewStatus.php`                            | Enum d'état (`label/badgeTone/icon`), actif/terminal/in-flight.                        |
| `src/Enum/Type/MessageRole.php`                                | Enum `user`/`assistant`.                                                               |
| `src/Repository/InterviewRepository.php`                       | `findActiveForProject()` (garde « 1 active », statut non terminal) **+ `findLatestForProject()`** (affichage de la dernière interview, terminale comprise). |
| `src/Manager/InterviewManager.php`                             | Orchestration : `start/submitMessage/conclude/submitBrief/retry/abandon`, gardes + dispatch async + purge terminale du dossier de story. |
| `src/Manager/InterviewNotAllowedException.php`                 | Échec de garde (`start` sur projet non cloné / interview déjà active).                 |
| `src/Message/RunInterviewTurn.php`                             | Ordre async « exécuter un tour d'interview » (porte `interviewId`).                    |
| `src/Message/SubmitBrief.php`                                  | Ordre async « publier le brief en PR draft » (porte `interviewId`).                    |
| `src/MessageHandler/RunInterviewTurnHandler.php`              | Recharge, appelle le runner, stocke la réponse, détecte le brief, pose l'état.         |
| `src/MessageHandler/SubmitBriefHandler.php`                   | Recharge, pousse (copie isolée) + ouvre la PR draft, pose l'état.                      |
| `src/Service/Interview/InterviewRunnerInterface.php`         | Port : `converse(sessionId, workingDir, userMessage, isFirstTurn): InterviewTurnResult`. |
| `src/Service/Interview/ClaudeInterviewRunner.php`            | Impl shell-out `claude -p`/`--resume` (session ambiante), parse JSON, coût, erreurs.  |
| `src/Service/Interview/InterviewTurnResult.php`              | DTO readonly : `assistantText`, `costUsd`.                                             |
| `src/Service/Interview/InterviewFailedException.php`        | Échec d'un tour (message lisible, sans secret).                                        |
| `src/Service/Interview/ProducedBriefLocator.php`            | Détecte `docs/story/NNN-f-slug/brief.md` non suivi (`git status --porcelain`) → slug. |
| `src/Service/Interview/BriefPusherInterface.php`            | Port : `push(cloneDir, storySlug, plainToken, url): string` (nom de branche).         |
| `src/Service/Interview/GitBriefPusher.php`                  | Copie de travail isolée + branche `forge/<slug>` + commit + **push `--force`** (idempotent, branche app-owned jamais mergée) via `GIT_ASKPASS`. |
| `src/Service/Interview/BriefPushFailedException.php`       | Échec de push (token lecture seule, réseau, conflit).                                  |
| `src/Service/Interview/StoryWorkspaceCleaner.php`          | Purge best-effort de `docs/story/<slug>/` non suivi dans le clone maintenu, à chaque état terminal (après `markSubmitted` et à l'abandon avec brief). Empêche la contamination inter-interviews. |
| `src/Service/Github/PullRequestOpenerInterface.php`         | Port : `supports(Provider)`, `open(url, token, head, title, body): string`.           |
| `src/Service/Github/GitHubPullRequestOpener.php`           | Impl `POST /pulls` (`draft:true`) via `github.client`.                                 |
| `src/Service/Github/PullRequestOpenerRegistry.php`         | Sélection par provider (mirroir `RepositoryReaderRegistry`).                           |
| `src/Service/Github/PullRequestFailedException.php`        | Échec d'ouverture de PR (statut/quota/réseau).                                         |
| `src/Controller/InterviewController.php`                    | `GET /projects/{id}/interview` (page + composant).                                    |
| `src/Twig/Components/ProjectInterview.php`                  | Live Component chat : LiveActions + poll.                                              |
| `templates/interview/show.html.twig`                        | Page hôte du composant.                                                                |
| `templates/components/ProjectInterview.html.twig`          | Rendu du fil, saisie, boutons Valider/Abandonner, lien PR, badge d'état.               |
| `migrations/Version<YYYYMMDDHHMMSS>.php`                    | Tables `interview` + `interview_message`.                                              |
| `tests/Double/FakeInterviewRunner.php`                     | Runner déterministe (scénario piloté par le message ; simule l'écriture du brief).    |
| `tests/Double/FakeBriefPusher.php`                         | Push simulé (renvoie un nom de branche, aucun git réel).                               |
| `tests/Double/FakePullRequestOpener.php`                   | Ouverture de PR simulée (renvoie une URL ; `pr-fail` → échec).                         |
| `tests/Unit/Enum/InterviewStatusTest.php`                  | `label/badgeTone/icon`, actif/terminal/in-flight.                                      |
| `tests/Unit/Entity/InterviewTest.php`                      | Transitions cohésives + `isFirstTurn/lastUserMessage`.                                 |
| `tests/Unit/Service/Interview/ProducedBriefLocatorTest.php`| Détection du brief non suivi (repo git temporaire).                                    |
| `tests/Unit/Service/Github/GitHubPullRequestOpenerTest.php`| `MockHttpClient` : nominal + 401/403/quota.                                            |
| `tests/Functional/Interview/InterviewFlowTest.php`         | Parcours start → thinking → awaiting → brief ready → submit → submitted (doubles).     |
| `tests/e2e/project-interview.spec.ts`                      | Smoke : exprimer un besoin, voir une réponse, valider (dépend du fake dev).            |

### Fichiers à modifier

| Fichier                                            | Modification                                                                                      |
|----------------------------------------------------|--------------------------------------------------------------------------------------------------|
| `src/Entity/Project.php`                           | Relation inverse `OneToMany $interviews` (optionnelle) + helper `isCloned()` si utile aux gardes. |
| `templates/project/show.html.twig`                 | Bouton « Exprimer un besoin » → `app_project_interview`, actif uniquement si `CloneStatus::Cloned`. |
| `config/packages/messenger.yaml`                   | Router `RunInterviewTurn` et `SubmitBrief` sur le transport `async`.                              |
| `config/services.yaml`                             | Binder les params du runner (`CLAUDE_BIN`, `CLAUDE_MODEL`, chemin plugin forge, allowedTools) ; tag registry PR opener. |
| `config/services_test.yaml`                        | Aliaser les 3 doubles (`InterviewRunnerInterface`, `BriefPusherInterface`, `PullRequestOpenerInterface`). |
| `.env`, `.env.example`                             | `CLAUDE_BIN=claude`, `CLAUDE_MODEL=claude-haiku-4-5` (+ commentaire : `ANTHROPIC_API_KEY` = mode serveur, déféré). |

## Impacts transverses

- **Multi-tenant / multi-thème** : non (mono-utilisateur, POC ; DA « Paper »).
- **API REST/GraphQL** : aucune exposée. **Sortante** : appel GitHub `POST /repos/{owner}/{repo}/pulls`.
- **i18n** : libellés du bouton, des états `InterviewStatus`, des boutons Valider/Abandonner et des messages d'échec (FR par défaut).
- **Permissions** : inchangé au sens des rôles (firewall `login`). **Nouvelle exigence** : le token du projet doit avoir le **droit d'écriture** (push + PR) — à documenter côté déclaration/édition (pas de champ nouveau, exigence de scope).
- **Emails / notifications** : non.
- **Migration de données** : **oui** — création des tables `interview` et `interview_message` (aucun backfill : nouvelles données).
- **Sécurité** : token jamais en clair (`GIT_ASKPASS` + `auth_bearer`) ; `claude` exécuté avec `--allowedTools` restreint aux outils du skill ; pas de sandbox conteneur en V1 locale (déféré serveur, suite ADR-0002). Commande en argv (pas de shell) → pas d'injection.
- **Comportement par défaut** : un projet non cloné n'a pas le bouton (indisponible) ; le kanban reste inchangé (la PR draft vit sur sa branche, hors projection).

## Ordre d'exécution

1. [ ] Enums `InterviewStatus` + `MessageRole` (+ test unitaire enum).
2. [ ] Entités `Interview` + `InterviewMessage` (relations, transitions cohésives) + relation inverse sur `Project` (+ test unitaire entité).
3. [ ] `InterviewRepository::findActiveForProject()` (garde) + `findLatestForProject()` (affichage).
4. [ ] Migration Doctrine (`symfony console make:migration`) + relue (tables + FK `onDelete: CASCADE`).
5. [ ] Port `InterviewRunnerInterface` + `InterviewTurnResult` + `InterviewFailedException` ; impl `ClaudeInterviewRunner` (argv, session ambiante, parse JSON, timeout).
6. [ ] `ProducedBriefLocator` (`git status --porcelain`) (+ test unitaire).
7. [ ] Port `PullRequestOpenerInterface` + registry + `GitHubPullRequestOpener` (+ test `MockHttpClient`) + `PullRequestFailedException`.
8. [ ] Port `BriefPusherInterface` + `GitBriefPusher` (copie isolée) + `BriefPushFailedException`.
9. [ ] Messages `RunInterviewTurn` / `SubmitBrief` + handlers ; routage async (`messenger.yaml`).
10. [ ] `InterviewManager` (gardes « cloné » + « 1 active », dispatch après flush).
11. [ ] `InterviewController` + `ProjectInterview` Live Component + templates (fil, saisie, boutons, poll) ; bouton sur `project/show.html.twig`.
12. [ ] Binding config (`services.yaml`, `.env(.example)`) + doubles en `services_test.yaml`.
13. [ ] Tests fonctionnels du parcours (doubles) + smoke E2E.
14. [ ] QA finale (`make quality` : PHPStan L9 + CS-Fixer + build ; `make phpunit` ; `make playwright`).

## Stratégie de test

| Code                                             | Type            | Ce qu'on vérifie                                                                 |
|--------------------------------------------------|-----------------|----------------------------------------------------------------------------------|
| `src/Enum/Type/InterviewStatus.php`              | Unit            | `label/badgeTone/icon` exhaustifs ; classification actif/terminal/in-flight.     |
| `src/Entity/Interview.php`                        | Unit            | Chaque transition pose statut + champs liés ; `isFirstTurn/lastUserMessage`.     |
| `src/Service/Interview/ProducedBriefLocator.php` | Unit            | Repo git temporaire : brief non suivi détecté (slug), absence → `null`.          |
| `src/Service/Github/GitHubPullRequestOpener.php` | Unit            | `MockHttpClient` : PR nominale (URL) ; 401/403 refus ; quota ; corps illisible.  |
| Parcours interview (manager + handlers + composant) | Functional  | start (cloné requis, 1 active) → Thinking → Awaiting → BriefReady → Submitting → Submitted, via doubles ; échec runner → Failed récupérable ; abandon libère le créneau. |
| Bouton « Exprimer un besoin »                    | Functional      | Présent/actif si `Cloned`, absent/inactif sinon.                                 |
| Parcours navigateur                              | E2E (smoke)     | Exprimer un besoin → réponse affichée → validation → état « Proposée » (fake dev). |

**Hors scope tests pour cette story** :

- Pas de test réseau réel GitHub ni de `claude`/`git` réel (les doubles couvrent) — conformément à la règle « jamais d'appel réseau réel ».
- `ClaudeInterviewRunner` et `GitBriefPusher` (shell-out réel) : non testés unitairement (I/O externe) — couverts indirectement par les doubles en fonctionnel ; le contrat de construction de commande peut être extrait/testé si le coût le justifie (question ouverte).

## Risques et mitigations

- **Exécution d'un agent à privilèges locaux** : `claude` shell-out tourne avec les droits de l'utilisateur. Mitigation V1 : `--allowedTools` restreint aux outils du skill, `--permission-mode acceptEdits`, timeout, argv (pas de shell). Sandbox conteneur = durcissement serveur, déféré (suite ADR-0002).
- **Persistance de session `--resume`** : en V1 ambiante, les sessions vivent dans le dossier `claude` par défaut (`~/.claude`) — `--resume` fonctionne entre process. Un redémarrage machine ne perd pas la session tant que le dossier persiste. Server/API-key → `CLAUDE_CONFIG_DIR` dédié (suite ADR).
- **Le brief est écrit dans le clone maintenu (008)** : le skill tourne avec `cwd` = clone → fichiers **non suivis** ajoutés dans `private/<owner>-<repo>/docs/story/…`. Sans effet sur le `git pull --ff-only` de la 008 (branche par défaut inchangée). Risque résiduel **tranché** : au-delà du conflit potentiel post-merge, une 2ᵉ interview sur le même projet re-détectait le brief non suivi de la précédente (contamination). → **`StoryWorkspaceCleaner` purge le dossier de story à chaque état terminal** (post-implémentation, cf. review).
- **Token à droit d'écriture** : un token lecture seule échoue au push → `Failed` lisible, brief préservé localement (re-tentable). Nouvelle exigence de scope à documenter.
- **Contenu généré poussé sans relecture humaine** : borné par la **PR draft** (jamais mergée par l'app) + la validation explicite avant dépôt.
- **Coût / abonnement** : session ambiante = coût sur l'abonnement. Modèle par défaut **Haiku** (`CLAUDE_MODEL`) pour borner ; le JSON de sortie expose le coût (traçable, non persisté en V1).
- **Concurrence** : la règle « 1 active/projet » + la garde de statut dans les handlers (idempotence, pattern 008) bornent double-clic et double-livraison.

## Questions ouvertes

> _Tranchées post-implémentation (le code fait foi) — cf. `report.md`._

- **Nettoyage post-submit** du dossier de story non suivi : **tranché → on nettoie.** `StoryWorkspaceCleaner` purge `docs/story/<slug>/` du clone maintenu à chaque état terminal (`markSubmitted` + abandon avec brief). Sans quoi la 2ᵉ interview d'un projet se contaminait (correctif de review).
- **Copie isolée : mécanisme** : **tranché → `git clone --local`** du clone maintenu (partage d'objets) + recopie du dossier de story non suivi ; clone 008 laissé intact.
- **Push idempotent** : **tranché → `git push --force`** sur la branche app-owned `forge/<slug>` (jamais mergée par l'app), pour qu'un `retry()` aboutisse après un push réussi mais une PR échouée.
- **Modèle par défaut** : Haiku (`CLAUDE_MODEL=claude-haiku-4-5`) retenu par défaut ; Sonnet reste une bascule config si la reconnaissance de code sur un vrai repo l'exige (hypothèse vision #2). → à éprouver en réel.

Encore ouvertes :

- **Reprise d'une interview interrompue** : le design la rend quasi gratuite (entité + session `claude` persistées → rouvrir la page réhydrate le fil), mais **aucun test ni parcours UX dédié** ne la garantit — statu quo à confirmer avant de la promettre. → dette (cf. `report.md`).
- **Extraction testable de la construction de commande** `claude` : non extraite (couverte par les doubles) — à revoir si le coût le justifie.
- **Fake runner en env `dev`** (E2E sans `claude`) : E2E finalement **scopé à la précondition + garde** (pas de worker en E2E, comme la 008) — happy path complet à valider en environnement réel.
- **Cas 422 « PR déjà ouverte »** : renvoie toujours `Failed` plutôt que de resurface la PR existante — edge rare non couvert. → dette.
