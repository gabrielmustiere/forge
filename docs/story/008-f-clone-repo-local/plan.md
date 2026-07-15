# Plan technique — Cloner en local le repo d'un projet depuis son kanban

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/008-f-clone-repo-local/`
> **Amont** : `pitch.md`

## Approche retenue

Le clone est le **premier job asynchrone** du projet. On introduit un **port** `RepositoryClonerInterface` (calqué sur `RepositoryReaderInterface`) avec une implémentation unique `GitRepositoryCloner` qui shell-out `git` via `symfony/process` — le clone étant uniforme entre GitHub et GitLab, pas besoin de registry par provider (contrairement au reader). Un message Messenger `CloneRepository(projectId)` routé sur le transport `async` (Doctrine, déjà configuré) porte le travail hors requête HTTP : le contrôleur passe le statut à `Cloning` **en synchrone** (garde anti double-clic) puis dispatche ; le handler exécute clone-ou-pull et pose `Cloned` ou `Failed`. L'état de clone est persisté en **quatre champs sur `Project`** (pas d'entité dédiée — YAGNI, cf. `CLAUDE.md`), avec des méthodes de transition cohérentes façon `applyVerification()`. La bascule d'état est reflétée en direct par un **Live Component** qui poll tant que le statut est `Cloning`, sans Mercure. L'authentification git passe par **`GIT_ASKPASS`** (token en variable d'env du worker, jamais en argv ni dans `.git/config`).

### Mécanismes mobilisés

- **Port + impl unique** (`RepositoryClonerInterface` / `GitRepositoryCloner`) : isole le shell-out git derrière une interface → substituable par un fake en test, aucune modif vendor. Suit le patron `RepositoryReaderInterface` déjà en place.
- **`symfony/process`** : `Process` pour lancer `git clone` / `git pull` avec timeout généreux (600 s), env `GIT_ASKPASS`/`GIT_TERMINAL_PROMPT=0`, sans token en argv.
- **Symfony Messenger** (`#[AsMessageHandler]`, transport `async` Doctrine) : premier job async ; routing `App\Message\CloneRepository: async` dans `messenger.yaml`.
- **`#[AsLiveComponent]`** (ux-live-component) : composant `ProjectCloneStatus` avec `data-poll` conditionnel tant que `Cloning` → badge + bouton, bascule sans reload.
- **`TokenCipher`** (existant) : déchiffrement du token au plus près de l'appel (variable locale, jamais loggée).
- **`RepositoryUrlNormalizer` / `RepositoryUrl`** (existants) : dérivation `owner`/`repo` (déjà validés `[A-Za-z0-9._-]+`) pour le nom de dossier et l'URL de clone.
- **CSRF** (`isCsrfTokenValid`) : jeton `clone{id}`, comme `verify{id}`.

### Alternatives écartées

- **Clone synchrone dans le contrôleur** (comme `verify`) : un gros repo bloquerait la requête et l'UI — le pitch impose l'asynchrone.
- **Entité `Clone` dédiée** : spéculatif au POC ; un seul champ réellement utile aujourd'hui (`local_path`), extraction possible plus tard si l'exécution de skills complexifie l'état.
- **Registry de cloners par provider** (comme le reader) : inutile, `git clone` est identique GitHub/GitLab ; une seule impl suffit.
- **Mercure / Turbo Streams** pour le temps réel : absent du stack, surdimensionné ; le polling du Live Component suffit pour un basculement d'état ponctuel.
- **Token dans l'URL de clone** (`https://x-access-token:TOKEN@host/...`) : le token finirait dans `.git/config` et potentiellement les logs — remplacé par `GIT_ASKPASS`.

## Modèle de données

### Modification de `App\Entity\Project`

`src/Entity/Project.php` — ajout de quatre champs d'état de clone :

| Champ              | Type                                    | Nullable | Contrainte / défaut                          |
|--------------------|-----------------------------------------|----------|----------------------------------------------|
| `cloneStatus`      | `CloneStatus` (enumType, string)        | non      | `options: ['default' => 'not_cloned']`       |
| `clonedAt`         | `datetime_immutable`                    | oui      | horodatage du dernier clone/pull réussi       |
| `localPath`        | `string(255)`                           | oui      | chemin absolu du clone (persisté au succès)   |
| `lastCloneError`   | `text`                                  | oui      | raison lisible du dernier échec (sans token)  |

Colonnes BDD en snake_case : `clone_status`, `cloned_at`, `local_path`, `last_clone_error`.

Méthodes de transition (cohérence statut + champs liés, façon `applyVerification()`) :

```php
public function markCloning(): static;                                   // Cloning + reset lastCloneError
public function markCloned(string $localPath, \DateTimeImmutable $at): static;   // Cloned + localPath + clonedAt + reset erreur
public function markCloneFailed(string $reason, \DateTimeImmutable $at): static; // Failed + lastCloneError
public function getCloneStatus(): CloneStatus;
public function getClonedAt(): ?\DateTimeImmutable;
public function getLocalPath(): ?string;
public function getLastCloneError(): ?string;
```

Le constructeur initialise `cloneStatus = CloneStatus::NotCloned`. Migration générée par `make:migration` (pas d'écriture à la main).

### Nouvel enum `App\Enum\Type\CloneStatus`

`src/Enum/Type/CloneStatus.php` — backed string enum, même pattern que `VerificationStatus` (`label()`, `badgeTone()`, `icon()`) :

| Case          | Valeur         | `label()`   | `badgeTone()` | `icon()` (tabler)              |
|---------------|----------------|-------------|---------------|--------------------------------|
| `NotCloned`   | `not_cloned`   | Non cloné   | `neutral`     | `tabler:cloud-off`             |
| `Cloning`     | `cloning`      | Clonage…    | `neutral`     | `tabler:cloud-download`        |
| `Cloned`      | `cloned`       | Cloné       | `ok`          | `tabler:cloud-check`           |
| `Failed`      | `failed`       | Échec       | `danger`      | `tabler:cloud-x`               |

## Périmètre

### Fichiers à créer

| Fichier                                                        | Rôle                                                                                     |
|----------------------------------------------------------------|------------------------------------------------------------------------------------------|
| `src/Enum/Type/CloneStatus.php`                                | Enum d'état de clone (`label`/`badgeTone`/`icon`).                                        |
| `src/Service/Repository/RepositoryClonerInterface.php`         | Port : `synchronize(RepositoryUrl, #[\SensitiveParameter] string $plainToken, string $destination): void`. |
| `src/Service/Repository/GitRepositoryCloner.php`               | Impl : clone si absent, `git pull` si `.git` présent ; auth via `GIT_ASKPASS` ; timeout. |
| `src/Service/Repository/CloneFailedException.php`              | Exception métier du cloner (message = raison lisible, sans token/URL crédentialisée).     |
| `src/Service/Repository/ClonePathResolver.php`                 | Résout `private/<owner>-<repo>` en chemin absolu depuis `%kernel.project_dir%/private`.   |
| `bin/git-askpass.sh`                                           | Script askpass minimal : `echo "$GIT_ASKPASS_TOKEN"` (token fourni via env du process).   |
| `src/Message/CloneRepository.php`                              | Message readonly `public int $projectId`.                                                 |
| `src/MessageHandler/CloneRepositoryHandler.php`                | Handler `#[AsMessageHandler]` : charge le projet, déchiffre, synchronise, pose l'état.    |
| `src/Twig/Components/ProjectCloneStatus.php`                   | Live Component : badge + bouton, poll tant que `Cloning`.                                 |
| `templates/components/ProjectCloneStatus.html.twig`           | Rendu du composant (badge réutilise `_status_badge`, form POST `app_project_clone`).      |
| `tests/Unit/Enum/CloneStatusTest.php`                          | Mapping label/tone/icon exhaustif.                                                        |
| `tests/Unit/Service/ClonePathResolverTest.php`                 | `owner`/`repo` → chemin attendu ; refus d'un chemin hors `private/`.                      |
| `tests/Functional/Controller/ProjectCloneTest.php`             | POST `/clone` : statut `Cloning` + message enqueué ; CSRF invalide → rien ; firewall.     |
| `tests/Functional/MessageHandler/CloneRepositoryHandlerTest.php`| Handler avec fake cloner : succès → `Cloned`, échec → `Failed`, pas d'exception propagée. |

### Fichiers à modifier

| Fichier                                          | Modification                                                                                 |
|--------------------------------------------------|----------------------------------------------------------------------------------------------|
| `src/Entity/Project.php`                         | +4 champs d'état de clone + méthodes `markCloning/markCloned/markCloneFailed` + getters ; init `NotCloned` au constructeur. |
| `src/Manager/ProjectManager.php`                 | +`requestClone(Project)` : `markCloning()` + flush + dispatch `CloneRepository` (inject `MessageBusInterface`). Idempotent si déjà `Cloning`. |
| `src/Controller/ProjectController.php`           | +route `POST /{id}/clone` (`app_project_clone`, CSRF `clone{id}`) → `manager->requestClone()` + flash + redirect show. |
| `templates/project/show.html.twig`               | Insérer `<twig:ProjectCloneStatus :project="project"/>` près du badge de vérification.        |
| `config/packages/messenger.yaml`                 | Router `App\Message\CloneRepository: async`.                                                  |
| `config/services_test.yaml`                      | Aliaser `RepositoryClonerInterface` → fake cloner (pas de réseau/git réel en test).           |
| `.gitignore`                                     | `private/*` + `!private/.gitkeep` (le clone ne doit pas polluer le repo du Board).            |
| `migrations/Version<YYYYMMDDHHMMSS>.php`         | Générée par `make:migration` (ajout des 4 colonnes, `clone_status` NOT NULL default `not_cloned`). |

## Impacts transverses

- **Multi-tenant** : non (mono-utilisateur).
- **Multi-thème** : non.
- **API REST/GraphQL** : non — action interne déclenchée depuis l'UI.
- **i18n** : libellés `CloneStatus::label()` + libellé du bouton (« Cloner » / « Mettre à jour ») + flash « Clonage lancé ». FR par défaut, structure existante.
- **Permissions** : firewall `login` existant suffit (l'action vit sous `/projects`, déjà protégé). Pas de voter.
- **Emails / notifications** : non.
- **Migration de données** : ajout de 4 colonnes ; `clone_status` NOT NULL avec défaut `not_cloned` → pas de backfill (le défaut couvre les lignes existantes). SQLite : `make:migration` gère la recréation de table.
- **Comportement par défaut** : un projet existant s'affiche en `Non cloné` avec le bouton ; kanban inchangé.

## Ordre d'exécution

1. [ ] `CloneStatus` enum + `tests/Unit/Enum/CloneStatusTest.php`.
2. [ ] `Project` : 4 champs + méthodes de transition + init constructeur.
3. [ ] `make:migration` + relecture (colonnes, défaut `not_cloned`, réversibilité `down()`).
4. [ ] `.gitignore` : `private/*` + `!private/.gitkeep`.
5. [ ] `ClonePathResolver` (+ test unit) et `CloneFailedException`.
6. [ ] `RepositoryClonerInterface` + `GitRepositoryCloner` + `bin/git-askpass.sh` (auth env, timeout, clone-ou-pull).
7. [ ] `CloneRepository` (message) + `CloneRepositoryHandler` + routing `messenger.yaml`.
8. [ ] `ProjectManager::requestClone()` (inject `MessageBusInterface`) + route contrôleur `/clone` (CSRF).
9. [ ] `config/services_test.yaml` : fake cloner ; puis `tests/Functional/…HandlerTest` et `…ProjectCloneTest`.
10. [ ] Live Component `ProjectCloneStatus` + template + insertion dans `show.html.twig`.
11. [ ] Smoke E2E Playwright (bouton visible, état `Clonage…` avec fake cloner).
12. [ ] QA finale : `make quality` (PHP-CS-Fixer + PHPStan L9 + build) puis `make ci`.

## Stratégie de test

| Code                                             | Type            | Ce qu'on vérifie                                                                       |
|--------------------------------------------------|-----------------|----------------------------------------------------------------------------------------|
| `src/Enum/Type/CloneStatus.php`                  | Unit            | `label`/`badgeTone`/`icon` exhaustifs sur les 4 cases.                                  |
| `src/Service/Repository/ClonePathResolver.php`   | Unit            | `owner`/`repo` → `private/<owner>-<repo>` absolu ; rejet si segment douteux.            |
| `src/Controller/ProjectController.php` (`clone`) | Functional      | POST valide → statut `Cloning` persistant + `CloneRepository` sur transport `async` ; CSRF invalide → aucun dispatch ; non authentifié → redirection login. |
| `src/MessageHandler/CloneRepositoryHandler.php`  | Functional      | Fake cloner : succès → `Cloned` + `localPath`/`clonedAt` ; `CloneFailedException` → `Failed` + `lastCloneError`, **sans** exception propagée (pas de retry). |
| `templates` + Live Component                     | E2E (smoke)     | Bouton `data-test="project-clone"` visible ; clic → état `Clonage…` (fake cloner, sans réseau). |

**Hors scope tests pour cette story** :

- Pas de test réseau/git réel : `GitRepositoryCloner` est validé indirectement (le fake couvre le contrat) ; un test d'intégration git réel serait fragile et lent — reporté.
- Pas de test du polling Live Component en tant que tel (mécanisme framework) — on teste l'état rendu, pas le timer.

## Risques et mitigations

- **Fuite du token** : jamais en argv (visible dans `ps`) ni dans `.git/config` → `GIT_ASKPASS` + env `GIT_ASKPASS_TOKEN`. `lastCloneError` ne doit contenir **ni** token **ni** URL crédentialisée (assainir le message d'erreur du process). Monolog ne logge pas l'env du `Process`.
- **Worker non consommé** : en async, si aucun worker ne consomme `async`, le statut reste `Cloning` indéfiniment. Mitigation : documenter `symfony console messenger:consume async` (ou déclarer un worker dans `.symfony.local.yaml` pour qu'il tourne avec `symfony serve`). À signaler dans le report.
- **Clone long / worker bloqué** : `Process` avec timeout 600 s ; au-delà, échec propre → `Failed`.
- **Double consommation / double-clic** : `markCloning()` + flush avant dispatch borne le double-clic ; `synchronize()` est idempotent (clone si absent, pull sinon) donc une double livraison Messenger est sans effet destructeur.
- **Dossier supprimé à la main** : la détection clone-vs-pull se fait sur le **filesystem** (présence de `<dest>/.git`), pas sur le statut persisté — robuste si l'utilisateur a effacé `private/<...>`.
- **`git` binaire absent** : prérequis d'exécution (documenté au stack) ; `GitRepositoryCloner` traduit l'absence en `CloneFailedException` lisible plutôt qu'une erreur brute.
- **Échec métier vs transitoire** : le handler **ne re-throw pas** un échec métier (token/URL) → pas de retry Messenger inutile ; seul un incident réellement transitoire pourrait justifier un throw (non retenu au POC pour rester simple).

## Questions ouvertes

_Toutes tranchées à l'implémentation (cf. `report.md` §Questions ouvertes tranchées) :_

- **Auto-consommation du worker en local** : **tranché — option (b)**. Un worker `messenger_consume_async` (`symfony console messenger:consume async --silent`) est déclaré dans `.symfony.local.yaml` : il démarre avec `symfony serve` (le `messenger:consume async` manuel reste possible hors de ce contexte).
- **Libellé unique vs contextuel du bouton** : **tranché — libellé contextuel**. Un seul bouton dont le libellé suit le statut : « Cloner » quand `NotCloned`/`Failed`, « Mettre à jour » quand `Cloned`.
