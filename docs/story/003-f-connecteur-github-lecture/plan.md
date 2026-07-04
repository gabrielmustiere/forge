# Plan technique — Lire à distance un repo GitHub et vérifier qu'il est éligible forge

> Pitch : `docs/story/003-f-connecteur-github-lecture/pitch.md`
> Stack : symfony

## Approche retenue

Le connecteur est structuré autour d'une **abstraction d'accès distant** (`RepositoryReaderInterface`) résolue par provider via un **registry `tagged_iterator`**, avec une seule implémentation concrète pour l'instant (`GitHubRepositoryReader`, adossée à un **scoped HTTP client** `symfony/http-client`). Un service d'orchestration `ProjectVerifier` traduit le résultat de la lecture — ou l'absence d'implémentation pour un provider — en un **`VerificationResult`** (VO immuable : statut + horodatage cohérents) que le caller applique sur l'entité `Project` via `applyVerification()` avant de persister — le verifier reste **pur**, il ne mute pas l'entité. La vérification est **synchrone** : elle est invoquée dans `ProjectManager::create()/update()` (déclaration et édition), par une **LiveAction `verify`** sur le composant `ProjectList` existant (liste, rafraîchissement du badge sans reload, dans la lignée du pattern `confirmingId`/`delete` déjà en place), et par une **route POST `app_project_verify` (CSRF)** déléguant à `ProjectManager::reverify()` pour le bouton « vérifier l'accès » de la fiche projet (`show.html.twig`, page server-rendered non-Live).

GitHub est interrogé via l'**API REST**, en **ciblant précisément le sous-arbre `docs/story/`** — jamais l'arbre entier du repo. Séquence : (1) `GET /repos/{owner}/{repo}` → `default_branch` (valide aussi l'existence du repo et l'accès) ; (2) `GET /contents/docs?ref={branch}` → localiser l'entrée `story` (type `dir`) et récupérer son **tree SHA** (absence de `docs` ou de `story` → `NotForge`) ; (3) `GET /git/trees/{story_sha}?recursive=1` → un **unique appel récursif borné au sous-arbre `docs/story`**, qui ramène dossiers + fichiers par story. Comme on ne rapatrie que ce sous-arbre (et non tout le repo), le risque de troncature est **éliminé en pratique**, l'appel reste économe, et le résultat suffit à l'éligibilité aujourd'hui comme à alimenter `mapping-etapes` demain, sans refonte du reader.

**Alternatives écartées** :

- **Vérification asynchrone (Messenger)** : nécessiterait un worker `messenger:consume` permanent, absent en usage local mono-utilisateur → statut « non vérifié » qui ne se résout jamais. Gain UX nul dans ce contexte.
- **Git trees récursif sur la racine du repo** : ramènerait tout l'arbre du dépôt → risque de troncature (`truncated: true`) sur les gros repos et transfert inutile. Écarté au profit du ciblage borné à `docs/story/`.
- **API GitHub contents pour tout lister** (`/contents/docs/story/**`) : navigation dossier par dossier = N+1 pour les fichiers de chaque story. On n'utilise `contents` que pour **localiser** le tree SHA de `docs/story` (2 niveaux), puis un seul appel git trees récursif borné.
- **GraphQL / serveur MCP** : surdimensionnés pour une lecture d'arborescence en lecture seule ; MCP ajoute une dépendance externe non justifiée.
- **Statut recalculé à la volée** (pas de persistance) : rappellerait GitHub à chaque rendu de la liste → rate-limit et couplage UI/réseau. Écarté au pitch (statut persisté).

## Entités et modèle de données

### Modification de `App\Entity\Project`

`src/Entity/Project.php` :

| Champ                 | Type                                         | Nullable | Contrainte / défaut                          |
|-----------------------|----------------------------------------------|----------|----------------------------------------------|
| `verificationStatus`  | `VerificationStatus` (enumType, colonne `verification_status`) | non | défaut applicatif `VerificationStatus::Unverified` |
| `verifiedAt`          | `?\DateTimeImmutable` (colonne `verified_at`)| oui      | `null` tant qu'aucune vérification n'a eu lieu |

- `verificationStatus` initialisé à `Unverified` dans le constructeur (pas de colonne `nullable`, valeur par défaut posée côté PHP + `DEFAULT 'unverified'` en base pour le backfill des lignes existantes).
- Accesseurs : `getVerificationStatus()`, `getVerifiedAt()`, et un setter combiné `applyVerification(VerificationStatus $status, \DateTimeImmutable $at): static` (garantit statut et horodatage cohérents, évite un setter isolé qui laisserait `verifiedAt` désynchronisé).
- Aucun impact sur les champs D2 existants ni sur la contrainte d'unicité `UNIQ_PROJECT_URL`.

### Nouvel enum `App\Enum\Type\VerificationStatus`

Backed string, dans `src/Enum/Type/` (convention projet). Cas : `Unverified = 'unverified'`, `Eligible = 'eligible'`, `NotForge = 'not_forge'`, `InvalidToken = 'invalid_token'`, `Unreachable = 'unreachable'`, `UnsupportedProvider = 'unsupported_provider'`. Méthodes de présentation `label()`, `badgeTone()` (renvoie une **tonalité sémantique** `ok`/`neutral`/`warning`/`danger` — le mapping tonalité→classes CSS de la DA vit dans le partial `templates/project/_status_badge.html.twig`, pas dans l'enum, qui reste découplé de la DA) et `icon()` — même pattern que `Provider`.

### Value object `App\Service\Github\StoryTree` (hors ORM)

Représente le résultat de lecture : liste immuable de stories (`StoryFolder`) parsées depuis l'arbre distant, chacune portant son identifiant `NNN-<f|r|t>-<slug>` et la liste des noms de fichiers présents. Expose `hasStories(): bool` (consommé par l'éligibilité) ; la structure fichiers-par-story est déjà disponible pour `mapping-etapes` sans y être exploitée ici.

### Value object `App\Service\Repository\VerificationResult` (hors ORM)

Résultat immuable d'une vérification : `status` (`VerificationStatus`) + `verifiedAt` (`\DateTimeImmutable`), cohérents entre eux. Produit par `ProjectVerifier::verify()`, appliqué par le caller (`ProjectManager`/controller) via `Project::applyVerification()`. Sépare le **calcul** du statut de sa **persistance** — le verifier reste pur et testable sans entité, et l'ordre d'implémentation (verifier avant l'entité) est préservé.

## Mécanismes framework mobilisés

- **`RepositoryReaderInterface` + `tagged_iterator`** (`symfony:service-tags`) : sélection de l'implémentation par `supports(Provider)`. Un provider sans reader (GitLab) est détecté par le registry → statut `UnsupportedProvider`, aucun appel réseau. Prépare V2 sans toucher l'orchestration.
- **Scoped HTTP client** (`symfony:http-client-request`) : un client `github.client` dédié (base_uri `https://api.github.com`, en-têtes `Accept: application/vnd.github+json` + `X-GitHub-Api-Version`). Le token n'est PAS en config (il est par-projet) → passé en `auth_bearer` à chaque requête.
- **Exceptions typées** (`symfony:http-client-response`) : le reader capte `ClientException` (401/403 → invalid_token, 404 → unreachable/not_forge selon le chemin), `ServerException`, `TransportExceptionInterface` (réseau/timeout → unreachable) et les remonte via des exceptions métier (`RepositoryUnreachableException`, `RepositoryAccessDeniedException`) que `ProjectVerifier` traduit en statut.
- **Live Component** (pattern existant `ProjectList`) : LiveAction `verify(int $id)` + getter du statut pour le badge, sans introduire de nouveau composant.
- **Manager comme point d'orchestration** : `ProjectVerifier` injecté dans `ProjectManager` ; la vérification est un effet de bord contrôlé de create/update, pas dispersé dans le controller.

## Fichiers à créer

| Fichier                                                        | Rôle                                                                              |
|----------------------------------------------------------------|-----------------------------------------------------------------------------------|
| `src/Enum/Type/VerificationStatus.php`                         | Enum backed string des statuts + `label()`/`badgeTone()`/`icon()`.               |
| `src/Service/Repository/RepositoryReaderInterface.php`         | Contrat `supports(Provider)` + `readStoryTree(RepositoryUrl, string $plainToken): StoryTree`. |
| `src/Service/Repository/RepositoryReaderRegistry.php`          | Résout le reader supportant un provider (`tagged_iterator`), `null` si aucun.     |
| `src/Service/Github/GitHubRepositoryReader.php`                | Implémentation GitHub : résout la branche par défaut, localise le tree SHA de `docs/story`, lit ce sous-arbre récursivement (borné), construit le `StoryTree`. |
| `src/Service/Github/StoryTree.php`                             | Value object immuable : stories parsées + fichiers présents ; `hasStories()`.    |
| `src/Service/Github/StoryFolder.php`                           | Value object : identifiant `NNN-<f|r|t>-<slug>` + noms de fichiers de la story.   |
| `src/Service/Repository/ProjectVerifier.php`                   | Orchestre provider→reader→statut ; renvoie un `VerificationResult` (statut + horodatage) appliqué par le caller. |
| `src/Service/Repository/VerificationResult.php`                | VO immuable statut + horodatage cohérents (garde `ProjectVerifier` pur, ne mute pas l'entité). |
| `src/Service/Repository/RepositoryUnreachableException.php`    | Exception métier : repo/branche injoignable, réseau, timeout.                     |
| `src/Service/Repository/RepositoryAccessDeniedException.php`   | Exception métier : token invalide / accès refusé (401/403).                       |
| `src/Service/Repository/RepositoryReaderException.php`         | Interface marqueur commune des exceptions métier du reader (traduites en statut). |
| `migrations/Version<YYYYMMDDHHMMSS>.php`                       | Ajout colonnes `verification_status` (DEFAULT 'unverified') et `verified_at` (nullable) sur `project`. |
| `templates/project/_status_badge.html.twig`                   | Partial badge : mappe la tonalité (`badgeTone`) vers les classes DA ; factorisé liste + fiche. |
| `config/services_test.yaml` + `tests/Double/StubRepositoryReader.php` | Env test : double déterministe (statut décidé par nom de dépôt) remplaçant `GitHubRepositoryReader` — aucun appel réseau réel côté fonctionnel/E2E. |
| `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php`     | `MockHttpClient` : arbre avec/sans `docs/story/`, 401/403, 404, timeout, arbre tronqué. |
| `tests/Unit/Service/Github/StoryTreeTest.php`                  | Parsing : filtre `docs/story/\d{3}-[frt]-<slug>/`, regroupement fichiers par story, `hasStories()`. |
| `tests/Unit/Service/Repository/ProjectVerifierTest.php`        | Mapping réponses/exceptions → statut ; GitLab → `UnsupportedProvider` sans appel. |
| `tests/Unit/Enum/VerificationStatusTest.php`                   | `label()`/`badgeTone()` par cas.                                                  |
| `tests/Functional/Twig/ProjectListVerifyTest.php`             | LiveAction `verify` (reader stubbé) met à jour statut + horodatage.               |

## Fichiers à modifier

| Fichier                                                | Modification                                                                                     |
|--------------------------------------------------------|--------------------------------------------------------------------------------------------------|
| `src/Entity/Project.php`                               | Ajout `verificationStatus` (défaut `Unverified`) + `verifiedAt` + `applyVerification()` / getters. |
| `src/Manager/ProjectManager.php`                       | Injecter `ProjectVerifier` ; `verify()` privé en fin de `create()`/`update()` (statut persisté dans la même transaction) + `reverify()` public pour le bouton de la fiche. |
| `src/Controller/ProjectController.php`                 | Route POST `app_project_verify` (CSRF) → `ProjectManager::reverify()` + flash : déclencheur du bouton « vérifier » de la fiche (page non-Live). |
| `src/Twig/Components/ProjectList.php`                  | LiveAction `verify(#[LiveArg] int $id)` → `ProjectVerifier` + persistance ; getter statut pour le badge. |
| `templates/components/ProjectList.html.twig`          | Colonne « Statut » : badge (`verificationStatus.label`/`badgeTone`/`icon`) + bouton « vérifier l'accès » (`data-action="live#action"`, `data-live-action-param="verify"`). |
| `templates/project/show.html.twig`                     | Afficher le badge de statut + date de dernière vérification + bouton « vérifier ».               |
| `config/packages/framework.yaml`                       | Déclarer le scoped client `github.client` (base_uri, en-têtes par défaut, timeout court).        |
| `fixtures/AppFixtures.php`                             | Poser un statut sans réseau (via `applyVerification`) sur les projets d'exemple : GitHub `Unverified`, GitLab `UnsupportedProvider` (illustre la variété des badges). |
| `config/services_test.yaml` (env test)                | Binder le double `StubRepositoryReader` en remplacement de `GitHubRepositoryReader` (option « reader de test » retenue) — jamais de réseau réel côté fonctionnel/E2E ; `MockHttpClient` reste utilisé dans le test unitaire du reader. |
| `tests/e2e/projects.spec.ts`                          | Étendre : assertion badge repliée dans le test existant « declare then delete » (évite une 2ᵉ déclaration réseau). Réseau non isolé en env test — appel de vérification inhérent à la déclaration assumé (cf. §Risques). |

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **API REST/GraphQL** : aucune API exposée. L'app *consomme* l'API GitHub en lecture via un scoped client ; elle n'expose rien.
- **i18n** : libellés FR (labels de statut, bouton « vérifier l'accès », date). Pas de contenu multilingue.
- **Permissions** : inchangé — firewall `login` existant, ni voter ni rôle nouveau.
- **Emails / notifications** : non.
- **Migration de données** : ajout de deux colonnes sur `project` ; `verification_status DEFAULT 'unverified'` backfille les lignes existantes, `verified_at` nullable → `null`. Aucun script de backfill applicatif.
- **Comportement par défaut** : un projet existant s'affiche `Unverified` jusqu'à sa prochaine édition ou un clic « vérifier l'accès ». Un projet GitLab affiche `UnsupportedProvider` sans tentative d'appel.

## Ordre d'implémentation

1. [ ] Enum `VerificationStatus` (+ `label()`/`badgeTone()`/`icon()`) et son test unitaire.
2. [ ] Value objects `StoryFolder` / `StoryTree` (parsing convention `docs/story/`) + test unitaire du parsing.
3. [ ] `RepositoryReaderInterface` + `RepositoryReaderRegistry` (tag + résolution par provider) + exceptions métier.
4. [ ] Scoped client `github.client` dans `framework.yaml` ; `GitHubRepositoryReader` (résolution branche par défaut → localisation tree SHA de `docs/story` → git trees récursif borné → construction `StoryTree`) + test `MockHttpClient` (tous les cas d'erreur).
5. [ ] `ProjectVerifier` (provider→reader→statut, GitLab sans appel) + test unitaire du mapping.
6. [ ] Entité `Project` : champs `verificationStatus`/`verifiedAt` + `applyVerification()` / getters.
7. [ ] Migration `make:migration` (colonnes + DEFAULT) relue pour réversibilité.
8. [ ] Brancher `ProjectVerifier` dans `ProjectManager::create()/update()` (synchrone, statut persisté).
9. [ ] `ProjectList` : LiveAction `verify` + getter badge (liste) ; route POST `app_project_verify` + `ProjectManager::reverify()` (fiche) ; `config/services_test.yaml` : double `StubRepositoryReader` (réseau neutralisé côté fonctionnel/E2E).
10. [ ] Templates : partial `_status_badge.html.twig` (tonalité→classes DA) ; colonne statut + bouton dans `ProjectList.html.twig` ; badge + carte « Dernière vérification » + form POST dans `show.html.twig` ; fixtures ajustées (GitHub `Unverified`, GitLab `UnsupportedProvider`).
11. [ ] Tests functional (LiveAction verify + route `app_project_verify` nominal/CSRF) + extension E2E (badge replié dans « declare then delete »).
12. [ ] QA finale : PHP-CS-Fixer + PHPStan (niveau du projet) + `make phpunit` + `make playwright` + `make ci`.

## Stratégie de test

| Code                                              | Type            | Ce qu'on vérifie                                                                    |
|---------------------------------------------------|-----------------|-------------------------------------------------------------------------------------|
| `GitHubRepositoryReader`                          | Unit            | `MockHttpClient` : arbre avec `docs/story/` conforme → `StoryTree` peuplé ; sans → vide ; 401/403 → `RepositoryAccessDeniedException` ; 404 repo → `RepositoryUnreachableException` ; timeout/transport → unreachable ; `truncated: true` géré (pas d'exception silencieuse). **Aucun appel réseau réel.** |
| `StoryTree` / `StoryFolder`                       | Unit            | Filtre `docs/story/\d{3}-[frt]-<slug>/`, ignore les chemins hors convention, regroupe les fichiers par story, `hasStories()`. |
| `ProjectVerifier`                                 | Unit            | GitHub joignable + stories → `Eligible` ; joignable sans story → `NotForge` ; exceptions → `InvalidToken`/`Unreachable` ; provider GitLab → `UnsupportedProvider` **sans invoquer de reader**. |
| `VerificationStatus`                              | Unit            | `label()` / `badgeTone()` par cas.                                                  |
| `ProjectList::verify` (Live)                      | Functional      | Instanciation directe (endpoint `/_components` derrière firewall, cf. leçon D2) : reader stubbé → statut + `verifiedAt` mis à jour et persistés. |
| `ProjectController::verify` (route fiche)         | Functional      | Route POST `app_project_verify` : nominal → statut + horodatage mis à jour ; token CSRF forgé → statut inchangé. |
| Déclaration → badge                               | E2E (Playwright)| Badge de statut visible après déclaration, replié dans « declare then delete ». Réseau non isolé en env test : l'appel de vérification inhérent à la déclaration subsiste (assumé, cf. §Risques). |

**Hors scope tests pour cette story** :

- Pas d'appel réseau réel vers GitHub à aucun niveau (unit/functional/E2E) — le reader est mocké ou binder de test.
- Pas de test de la logique de mapping fichiers→colonne (relève de `mapping-etapes`) : on vérifie seulement que l'arbre est correctement lu et parsé.
- Pas de test de rate-limit réel (simulé via `MockHttpClient` avec en-têtes/statut correspondants).

## Risques et points d'attention

- **Sécurité du token** : le token déchiffré ne doit jamais fuiter. Mitigation : `TokenCipher::decrypt()` appelé au plus près de l'appel (variable locale, non stockée), passé en `auth_bearer` (jamais dans l'URL ni un log). Vérifier que le web-profiler / monolog ne dumpe pas les en-têtes du client en dev (scrubbing ou niveau de log adéquat). Un test functional D2 vérifie déjà l'absence du token dans le HTML — étendre l'esprit au flux de vérif.
- **Rate-limit GitHub** : ~60 req/h non authentifié, 5000/h authentifié. Mitigation : appels uniquement à la déclaration/édition/clic (jamais au rendu de liste, statut lu en base) ; timeout court ; un dépassement (403 + `X-RateLimit-Remaining: 0`) est traduit en `Unreachable` lisible plutôt qu'en exception.
- **Arbre tronqué** (`truncated: true`) : neutralisé **par conception** — l'appel récursif est borné au sous-arbre `docs/story` (petit), pas à la racine du repo, donc la troncature est extrêmement improbable. Filet de sécurité : si `truncated: true` survient malgré tout, `Eligible` dès qu'au moins une story conforme est visible dans la portion reçue.
- **Coût en appels (3 par vérification)** : `repo` + `contents/docs` + `git/trees` récursif. Mitigation : appels uniquement à la déclaration/édition/clic (jamais au rendu de liste) ; borné à `docs/story` ; échec d'un appel intermédiaire mappé en statut lisible (`Unreachable`/`InvalidToken`/`NotForge` selon le code et l'étape).
- **Vérif synchrone qui ralentit l'enregistrement** : un GitHub lent suspend le submit du formulaire. Mitigation : timeout court (~5s) sur le scoped client, fallback `Unreachable` — l'enregistrement du projet aboutit toujours, seul le statut reflète l'échec réseau.
- **Repo public + token invalide** : un repo public reste lisible même avec un token invalide (l'API tolère). Décision : on **privilégie la lecture réussie** — `InvalidToken` n'est signalé que si un appel échoue en 401/403 ; si la lecture aboutit, on statue `Eligible`/`NotForge` selon le contenu. Reflète l'objectif réel (peut-on lire `docs/story/` ?).
- **E2E non isolé du réseau** : pas de `webServer` en env test (serveur E2E ciblant `forge-board.wip` en dev, préférence établie du projet). L'appel de vérification inhérent à la déclaration subsiste donc en E2E → dépendance résiduelle à la connectivité `api.github.com`. Décision : **assumé** (token invalide → 401 rapide, `save` jamais bloqué) ; rejouer `make playwright` avec/sans accès sortant pour confirmer le repli. Un `webServer` env test isolerait complètement mais contredirait la préférence `forge-board.wip` — non retenu.

## Questions ouvertes

Toutes tranchées au cadrage du plan :

- **Résolution de branche** → **tranché : 2 appels**. `GET /repos/{owner}/{repo}` pour lire `default_branch`, puis navigation vers `docs/story`. Le 1er appel valide aussi l'existence du repo et l'accès (diagnostic 404/401 net).
- **Ciblage de l'appel / arbre tronqué** → **tranché : appel borné à `docs/story`**. On localise le tree SHA de `docs/story` (via `contents`) puis un unique `git/trees/{sha}?recursive=1` sur ce seul sous-arbre — on ne lit jamais tout le repo. La troncature devient un cas résiduel (filet : `Eligible` si ≥1 story visible). Pas de 7e statut « partiel ».
- **Token invalide sur repo public** → **tranché : privilégier la lecture réussie**. `InvalidToken` uniquement sur échec 401/403 ; si la lecture aboutit, statut selon le contenu.
- **Neutralisation du réseau en test** → **révisé à l'implémentation (cf. `report.md` §Écarts + changelog)** : deux niveaux plutôt qu'un seul. **Unitaire** — `MockHttpClient`/`JsonMockResponse` dans `GitHubRepositoryReader` (couvre le parsing réel des réponses, sans réseau). **Fonctionnel/E2E** — binding d'un double `StubRepositoryReader` via `config/services_test.yaml` (statut piloté par nom de dépôt : `*eligible*`/`*denied*`/`*offline*`/sinon `NotForge`), plus lisible que des réponses HTTP mockées à ce niveau. Le plan proposait déjà cette alternative en §Fichiers à modifier (« reader de test OU `MockHttpClient` »).

---

## Changelog

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| 2026-07-05 | Sync post-implémentation  | Réalignement sur le code livré (cf. `report.md`). §Approche + §Entités : ajout du VO `VerificationResult` (verifier pur, appliqué par le caller). §Fichiers à créer/modifier : route POST `app_project_verify` + `ProjectManager::reverify()` (bouton fiche non-Live), `RepositoryReaderException`, partial `_status_badge.html.twig`, double `StubRepositoryReader`. §Entités : `badgeTone()` renvoie une tonalité mappée dans le template, pas des classes CSS. Neutralisation réseau en test révisée (§Fichiers, §Ordre, §Stratégie de test, §Questions ouvertes) : double reader (fonctionnel/E2E) + `MockHttpClient` (unitaire). E2E replié dans « declare then delete » avec risque réseau documenté (§Risques). Fixtures : GitLab `UnsupportedProvider`. Pitch inchangé (aucun écart sur les règles métier / critères). |
