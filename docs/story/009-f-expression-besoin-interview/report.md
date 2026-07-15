# Report — Exprimer un besoin depuis le board et le cadrer en brief soumis en revue

> **But** : constater l'écart entre l'intention et le code livré — écarts, dette, suites.
> **Registre** : factuel
> **Story** : `docs/story/009-f-expression-besoin-interview/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

Story livrée **conforme au plan à ~95 %** : les 14 sous-tâches de l'ordre d'implémentation sont réalisées, les 8 critères d'acceptation sont atteints, et la review pré-merge est verte (0 bloquant, 2 importants corrigés). Trois écarts structurants, tous assumés : un **service ajouté** (`StoryWorkspaceCleaner`) qui purge le dossier de story non suivi à chaque état terminal — correctif de review contre la contamination inter-interviews et réponse tranchée à la question ouverte « nettoyage post-submit » ; le **push rendu idempotent** (`git push --force` sur la branche app-owned `forge/<slug>`) ; et un **jeu de LiveActions élargi** (`start/send/conclude/validate/retry/abandon` au lieu de `start/submit/validate/abandon`). Périmètre : ~35 fichiers source/tests, 2 migrations, **237 PHPUnit verts** (PHPStan L9, PHP-CS-Fixer, smokes E2E OK). Dette POC assumée : `Bash` dans la liste blanche d'outils, 422 « PR déjà ouverte » non resurfacée, reprise d'interview interrompue sans test/UX dédié.

## Périmètre livré

### Fichiers créés

| Fichier                                              | Rôle                                                              | Prévu dans le plan |
|------------------------------------------------------|-------------------------------------------------------------------|--------------------|
| `src/Entity/Interview.php`                           | Entité à état, transitions cohésives (`markThinking/markBriefReady/…`) | Oui           |
| `src/Entity/InterviewMessage.php`                    | Un tour du fil (role/content/createdAt)                          | Oui                |
| `src/Enum/Type/InterviewStatus.php`                  | Enum d'état (`label/badgeTone/icon`, actif/terminal/in-flight)   | Oui                |
| `src/Enum/Type/MessageRole.php`                      | Enum `user`/`assistant`                                          | Oui                |
| `src/Repository/InterviewRepository.php`             | `findActiveForProject()` (garde) **+ `findLatestForProject()`** (affichage) | Écart volontaire (cf. §) |
| `src/Manager/InterviewManager.php`                   | Orchestration `start/submitMessage/conclude/submitBrief/retry/abandon`, gardes + dispatch async, purge terminale | Oui (avec ajouts, cf. §) |
| `src/Manager/InterviewNotAllowedException.php`       | Échec de garde (non cloné / 1 active)                            | Non (ajout — cf. §Ajouts) |
| `src/Message/RunInterviewTurn.php`                   | Ordre async « exécuter un tour » (porte `interviewId`)          | Oui                |
| `src/Message/SubmitBrief.php`                        | Ordre async « publier le brief en PR draft »                    | Oui                |
| `src/MessageHandler/RunInterviewTurnHandler.php`     | Recharge, runner, stocke, détecte le brief, pose l'état          | Oui                |
| `src/MessageHandler/SubmitBriefHandler.php`          | Push isolé + PR draft, pose l'état, **purge post-`markSubmitted`** | Oui (purge = ajout) |
| `src/Service/Interview/InterviewRunnerInterface.php` | Port `converse(...)`                                             | Oui                |
| `src/Service/Interview/ClaudeInterviewRunner.php`    | Shell-out `claude -p`/`--resume` (session ambiante), parse JSON  | Oui                |
| `src/Service/Interview/InterviewTurnResult.php`      | DTO readonly `assistantText`/`costUsd`                          | Oui                |
| `src/Service/Interview/InterviewFailedException.php` | Échec d'un tour (message lisible, sans secret)                  | Oui                |
| `src/Service/Interview/ProducedBriefLocator.php`     | Détecte `brief.md` non suivi (`git status --porcelain`) → slug   | Oui                |
| `src/Service/Interview/BriefPusherInterface.php`     | Port `push(...)`                                                 | Oui                |
| `src/Service/Interview/GitBriefPusher.php`           | Copie isolée + branche `forge/<slug>` + commit + **push `--force`** | Oui (force = écart, cf. §) |
| `src/Service/Interview/BriefPushFailedException.php`  | Échec de push (token lecture seule, réseau, conflit)            | Oui                |
| `src/Service/Interview/StoryWorkspaceCleaner.php`    | **Purge best-effort de `docs/story/<slug>/` non suivi** à chaque état terminal | Non (ajout — cf. §Ajouts) |
| `src/Service/Github/PullRequestOpenerInterface.php`  | Port `supports()`/`open(...)`                                   | Oui                |
| `src/Service/Github/GitHubPullRequestOpener.php`     | Impl `POST /pulls` (`draft:true`) via `github.client`           | Oui                |
| `src/Service/Github/PullRequestOpenerRegistry.php`   | Sélection par provider (miroir `RepositoryReaderRegistry`)      | Oui                |
| `src/Service/Github/PullRequestFailedException.php`  | Échec d'ouverture de PR                                          | Oui                |
| `src/Controller/InterviewController.php`             | `GET /projects/{id}/interview` (page + composant)               | Oui                |
| `src/Twig/Components/ProjectInterview.php`           | Live Component chat : LiveActions **élargies** + poll            | Oui (actions = écart, cf. §) |
| `templates/interview/show.html.twig`                 | Page hôte du composant                                           | Oui                |
| `templates/components/ProjectInterview.html.twig`    | Fil, saisie, boutons, lien PR, badge d'état                     | Oui                |
| `migrations/Version20260709182741.php`               | Tables `interview` + `interview_message` (FK CASCADE)           | Oui                |
| `config/packages/test/messenger.yaml`               | Transport `sync` en test (parcours fonctionnel déterministe)    | Non (ajout — cf. §Ajouts) |
| `tests/Double/FakeInterviewRunner.php`               | Runner déterministe (scénario + simulation d'écriture du brief) | Oui                |
| `tests/Double/FakeBriefPusher.php`                   | Push simulé (renvoie un nom de branche)                         | Oui                |
| `tests/Double/FakePullRequestOpener.php`            | Ouverture de PR simulée (`pr-fail` → échec)                     | Oui                |
| `tests/Unit/…` + `tests/Functional/…` + `tests/e2e/…` | Enum, entité, `ProducedBriefLocator` (repo git réel), `GitHubPullRequestOpener` (`MockHttpClient`), parcours fonctionnel, **non-contamination + purge + `StoryWorkspaceCleaner`**, smoke E2E | Oui (+ tests de review) |

### Fichiers modifiés

| Fichier                                              | Modification                                                      | Prévu dans le plan |
|------------------------------------------------------|-------------------------------------------------------------------|--------------------|
| `src/Entity/Project.php`                             | Relation inverse `OneToMany $interviews` + helper `isCloned()`   | Oui                |
| `src/Controller/ProjectController.php`               | Lien / accès vers l'interview depuis la fiche projet             | Oui (implicite UI) |
| `templates/project/show.html.twig`                   | Bouton « Exprimer un besoin » actif si `Cloned`                  | Oui                |
| `config/packages/messenger.yaml`                     | Routage `RunInterviewTurn` + `SubmitBrief` sur `async`           | Oui                |
| `config/services.yaml`                               | Params runner (`CLAUDE_BIN/MODEL/ALLOWED_TOOLS`, plugin dir) + tag registry PR | Oui         |
| `config/services_test.yaml`                          | Alias des 3 doubles                                             | Oui                |
| `.env`, `.env.example`                               | `CLAUDE_BIN/MODEL/ALLOWED_TOOLS` (+ note `ANTHROPIC_API_KEY` déféré serveur) | Oui        |

## Écarts avec le plan

### Écarts volontaires

| Prévu                                                        | Réalisé                                                                                  | Raison                                                                                              |
|--------------------------------------------------------------|------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------|
| Push simple `branch:branch` (implicite au plan)              | `git push --force` sur la branche app-owned `forge/<slug>`                                | Rendre le dépôt **idempotent** : après un push réussi mais une PR échouée, `retry()` rejoue `SubmitBrief` (nouveau SHA) → non-fast-forward rejeté → `Failed` définitif. La branche `forge/<slug>` n'est jamais mergée par l'app → réécrasable sans risque. Review §Importants (ROBUSTESSE, corrigé). |
| LiveActions `start / submit / validate / abandon`            | `start / send / conclude / validate / retry / abandon`                                    | `submit`→`send` (envoi d'un message de tour, plus juste) ; **`conclude`** (bouton « Conclure le cadrage » → `InterviewManager::CONCLUSION_MESSAGE`, en écho au skill coopératif `b7ce883` qui annonce « prêt à conclure ») ; **`retry`** pour re-tenter un dépôt `Failed` (couple avec le push idempotent). |
| Repository : `findActiveForProject()` seul                   | `findActiveForProject()` (garde « 1 active ») **+ `findLatestForProject()`** (affichage) | Le composant doit ré-afficher la **dernière** interview d'un projet, y compris terminale (`Submitted`/`Abandoned`), que `findActiveForProject()` exclut par construction. Deux besoins distincts → deux méthodes. |

### Non implémenté

| Élément prévu                                                | Raison                                                              | Action requise                                              |
|--------------------------------------------------------------|--------------------------------------------------------------------|-------------------------------------------------------------|
| Aucun — les 14 sous-tâches du plan sont livrées.             | —                                                                  | —                                                           |

### Ajouts non prévus

| Élément ajouté                                                                          | Raison                                                                                                                                                    |
|-----------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/Service/Interview/StoryWorkspaceCleaner.php` (+ `StoryWorkspaceCleanerTest`)       | Purge best-effort de `docs/story/<slug>/` non suivi dans le clone maintenu, à chaque état terminal : après `markSubmitted` (`SubmitBriefHandler`) et à l'abandon avec brief produit (`InterviewManager::abandon`). **Corrige la contamination inter-interviews** (la 2ᵉ interview d'un projet re-détectait le brief de la précédente et basculait `BriefReady` sur le mauvais slug). Review §Importants (BUG, corrigé) — **tranche la question ouverte « nettoyage post-submit » : on nettoie.** |
| `InterviewManager::conclude()` + `CONCLUSION_MESSAGE` + `InterviewManager::retry()`     | Actions métier derrière les LiveActions `conclude`/`retry` (cf. §Écarts volontaires).                                                                    |
| `src/Manager/InterviewNotAllowedException.php`                                           | Traduit les échecs de garde (`start` sur projet non cloné / interview déjà active) — implicite dans « gardes » du plan, matérialisé en exception dédiée. |
| `config/packages/test/messenger.yaml`                                                   | Transport `sync` en environnement test pour un parcours fonctionnel déterministe (worker non consommé en test).                                          |
| 4 tests de review (`testTerminalInterviewDoesNotContaminateTheNextOne`, `testAbandonPurgesAProducedBrief`, assertion de purge sur le happy path, `StoryWorkspaceCleanerTest`) | Couvrent le nettoyage post-terminal et la non-régression de contamination.                                                                               |

## Tests

| Code                                                        | Type prévu       | Type réalisé                                          | Statut                       |
|-------------------------------------------------------------|------------------|-------------------------------------------------------|------------------------------|
| `src/Enum/Type/InterviewStatus.php`                         | Unit             | Unit (`label/badgeTone/icon` exhaustifs, classif.)   | Fait                         |
| `src/Entity/Interview.php`                                  | Unit             | Unit (transitions cohésives, `isFirstTurn/lastUserMessage`) | Fait                  |
| `src/Service/Interview/ProducedBriefLocator.php`            | Unit             | Unit sur repo git réel (brief non suivi → slug, absence → null) | Fait                |
| `src/Service/Github/GitHubPullRequestOpener.php`            | Unit             | Unit `MockHttpClient` (nominal + 401/403/quota/**422**) | Fait — couverture étendue  |
| `src/Service/Interview/StoryWorkspaceCleaner.php`           | Non prévu        | Unit (`StoryWorkspaceCleanerTest`)                   | Fait — ajout de review       |
| Parcours interview (manager + handlers + composant)         | Functional       | Functional via doubles (start → Thinking → Awaiting → BriefReady → Submitting → Submitted ; échec runner → Failed ; abandon libère le créneau ; **non-contamination + purge**) | Fait — couverture étendue |
| Bouton « Exprimer un besoin »                               | Functional       | Functional (présent/actif si `Cloned`, absent sinon) | Fait                         |
| Parcours navigateur                                         | E2E (smoke)      | E2E scopé précondition + garde (pas de worker en E2E, comme la 008) | Conforme (écart assumé, hors happy path complet) |
| `ClaudeInterviewRunner` / `GitBriefPusher` (shell-out réel) | Hors scope assumé| Non testés unitairement (I/O externe, couverts par doubles) | Conforme (hors scope assumé) |

**QA finale** : PHPStan L9 ✓, PHP-CS-Fixer ✓, **237 PHPUnit ✓**, smokes E2E ✓.

## Dette technique identifiée

Issus de la review (mineurs non traités) :

1. **[SECU] `Bash` dans la liste blanche d'outils avec `acceptEdits`** — `.env` / `.env.example` (`CLAUDE_ALLOWED_TOOLS=Read,Write,Glob,Grep,Bash`) + `ClaudeInterviewRunner.php:74`. L'agent tourne aux droits de l'utilisateur, `--permission-mode acceptEdits`, piloté par du texte utilisateur libre → **surface RCE locale**. Arbitré : **gardé (dette POC assumée)** — le skill `feature-interview` a besoin de `Bash` (`git status`, exploration du repo). Sandbox conteneur déférée serveur (suite ADR-0002). POC local mono-utilisateur. **Critique à la bascule serveur.**
2. **[SCOPE] Fichiers MCP hors périmètre dans le working tree** — `mate/`, `mcp.json`, `.ai/mcp/mcp.json`, `composer.json` (allow-plugin + PSR-4 `Mate\`), worker `mate` dans `.symfony.local.yaml`. Outillage MCP sans rapport avec l'interview — à isoler dans un commit dédié pour garder l'historique lisible.
3. **[ROBUSTESSE] `locate()` renvoie le 1er brief non suivi dans l'ordre porcelain (alphabétique)** — `ProducedBriefLocator.php`. Non déterministe si plusieurs dossiers non suivis coexistent — **sans objet une fois `StoryWorkspaceCleaner` en place**, à garder à l'œil.

Au-delà de la review :

4. **Cas 422 « PR déjà ouverte » non resurfacé** — `GitHubPullRequestOpener` renvoie toujours `Failed` plutôt que de re-remonter la PR existante. Edge rare, non couvert — à traiter si le cas se présente.
5. **Reprise d'une interview interrompue sans test/UX dédié** — le design la rend quasi gratuite (entité + session `claude` persistées → rouvrir la page réhydrate le fil), mais aucun test ni parcours UX ne la garantit. Statu quo à confirmer avant de la promettre.
6. **Interview `claude` + push/PR GitHub réels non validés en environnement réel** — couverts uniquement par doubles. À éprouver avec un vrai repo cloné, worker `messenger:consume async` actif, session ambiante et token à droit d'écriture (cf. review §Hors review). **Critique avant toute démo réelle.**

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Sur un projet cloné, un bouton « Exprimer un besoin » ouvre une conversation ; indisponible si non cloné. *(Functional bouton + garde `isCloned()`)*
- [x] Besoin libre → questions d'interview ancrées sur le produit, tour par tour, contexte conservé entre les tours. *(dialogue `--resume`, fil `InterviewMessage`)*
- [x] À l'issue de l'interview, un `brief.md` fonctionnel est produit et présenté pour relecture avant tout dépôt. *(détection `git status --porcelain` → `BriefReady`)*
- [x] Tant que l'utilisateur n'a pas validé, rien n'est poussé ; il peut abandonner sans trace côté repo. *(`abandon` terminal, purge locale sans effet distant)*
- [x] À la validation, branche dédiée créée, brief déposé et poussé, PR draft GitHub ouverte, lien affiché. *(`GitBriefPusher` + `GitHubPullRequestOpener` draft)*
- [x] Token lecture seule / échec réseau → état d'échec lisible sans planter, brief local récupérable et re-tentable. *(`Failed` récupérable + `retry` idempotent)*
- [x] Une seule interview active par projet. *(`findActiveForProject()` + garde)*
- [x] Interface utilisable pendant un tour long ou un dépôt (asynchrone). *(jobs Messenger async + poll `isInFlight()`)*
- [x] Token jamais en clair ; branche principale et kanban jamais modifiés. *(`GIT_ASKPASS` + `auth_bearer` + `#[\SensitiveParameter]`, branche `forge/<slug>` hors `main`)*

**8/8 critères atteints.**

## Leçons apprises

- **Un brief écrit « non suivi » dans un clone partagé est un état global à nettoyer explicitement** : le plan avait pressenti le risque (§Risques : « nettoyage post-submit candidat ») mais l'avait laissé en question ouverte. En pratique c'était un **bug bloquant à la 2ᵉ utilisation** — un plan qui projette un artefact dans un espace de travail réutilisé doit trancher son cycle de vie (purge terminale) dès le cadrage, pas le déférer.
- **Le couple push + PR n'est pas atomique** : dès qu'un dépôt enchaîne deux effets distants (push git puis appel API), prévoir l'idempotence de la re-tentative dès le plan (branche app-owned réécrasable, `--force`) plutôt que de découvrir le non-fast-forward en review.
- **Un skill coopératif change la surface d'actions du composant** : le signal « prêt à conclure » côté skill (`b7ce883`) appelle une action `conclude` côté app non prévue au plan. Quand une story dépend d'un skill externe, lister ses signaux d'interaction avant de figer les LiveActions.
- **« Garde » et « affichage » sont deux requêtes de repository, pas une** : `findActiveForProject` (exclut le terminal) ne peut pas servir à ré-afficher la dernière interview. Anticiper la méthode de lecture d'affichage au plan quand des états terminaux doivent rester visibles.
- **Scoper l'E2E à la précondition + garde (comme la 008) est un choix cohérent** quand le happy path dépend d'un worker et d'un agent externe non déterministe — assumé, mais à compléter par une validation manuelle en environnement réel avant démo.
