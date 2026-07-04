# Report — Lire à distance un repo GitHub et vérifier qu'il est éligible forge

> Pitch : `docs/story/003-f-connecteur-github-lecture/pitch.md`
> Plan : `docs/story/003-f-connecteur-github-lecture/plan.md`
> Date d'implémentation : 2026-07-05
> Commits liés : working tree non commité au moment du report
> Référence review : `review.md`

## Résumé

Feature livrée intégralement (12 sous-tâches) et reviewée **READY TO COMMIT** — conformité au plan estimée à ~90 %. Les 8 critères d'acceptation du pitch sont couverts et vérifiés (dont un contrôle réel sur le projet `enao` → statut Éligible via liste et fiche). Trois écarts structurants, tous volontaires : (1) neutralisation réseau en test par un **double reader** (`StubRepositoryReader` + `services_test.yaml`) plutôt que le `MockHttpClient` bindé décidé au plan ; (2) ajout d'une **route POST `app_project_verify`** + `ProjectManager::reverify()` pour le bouton « vérifier » de la fiche (non-Live) ; (3) ajout d'un **VO `VerificationResult`** pour garder `ProjectVerifier` pur. Périmètre : 11 fichiers suivis modifiés + ~15 nouveaux (~660 lignes). QA finale verte (`make ci` : 72 tests / 199 assertions, E2E 3/3, PHPStan niveau 9, CS-Fixer clean). Dette résiduelle : 1 mineur review accepté (web-profiler `http_client` dev-only), 1 résiduel E2E (réseau non isolé en env test).

## Ce qui a été implémenté

### Fichiers créés

| Fichier                                                     | Rôle                                                                              | Prévu dans le plan |
|-------------------------------------------------------------|-----------------------------------------------------------------------------------|--------------------|
| `src/Enum/Type/VerificationStatus.php`                      | Enum backed string des 6 statuts + `label()`/`badgeTone()`/`icon()`.              | Oui                |
| `src/Service/Repository/RepositoryReaderInterface.php`      | Contrat `supports(Provider)` + `readStoryTree()`, auto-taggé `app.repository_reader`. | Oui             |
| `src/Service/Repository/RepositoryReaderRegistry.php`       | Résout le reader supportant un provider (`tagged_iterator`), `null` sinon.        | Oui                |
| `src/Service/Github/GitHubRepositoryReader.php`             | Implémentation GitHub : branche par défaut → tree SHA `docs/story` → git trees récursif borné → `StoryTree`. | Oui |
| `src/Service/Github/StoryTree.php`                          | VO immuable : stories parsées ; `hasStories()`.                                   | Oui                |
| `src/Service/Github/StoryFolder.php`                        | VO : identifiant `NNN-<f\|r\|t>-<slug>` + noms de fichiers.                        | Oui                |
| `src/Service/Repository/ProjectVerifier.php`                | Orchestre provider→reader→statut ; renvoie un `VerificationResult`.               | Oui (renvoi via VO — cf. §Ajouts) |
| `src/Service/Repository/VerificationResult.php`             | VO immuable `status` + `verifiedAt` cohérents, appliqué par le caller.            | Non (ajout — cf. §Ajouts non prévus) |
| `src/Service/Repository/RepositoryUnreachableException.php` | Exception métier : injoignable / réseau / timeout.                               | Oui                |
| `src/Service/Repository/RepositoryAccessDeniedException.php`| Exception métier : token invalide / 401-403.                                     | Oui                |
| `src/Service/Repository/RepositoryReaderException.php`      | Interface marqueur des exceptions métier du reader.                              | Non (ajout — cf. §Ajouts non prévus) |
| `migrations/Version20260704214937.php`                      | Colonnes `verification_status` (DEFAULT 'unverified') + `verified_at` (nullable), réversible. | Oui  |
| `templates/project/_status_badge.html.twig`                | Partial badge : mappe `badgeTone` → classes DA (ok/warning/danger/neutral).      | Non (ajout — cf. §Ajouts non prévus) |
| `tests/Double/StubRepositoryReader.php`                     | Double déterministe (décision par nom de dépôt) remplaçant le reader en env test. | Non (ajout — cf. §Écarts volontaires) |
| `config/services_test.yaml`                                | Substitue `GitHubRepositoryReader` par le double en env test.                     | Oui (mécanisme changé — cf. §Écarts) |
| `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php`  | `MockHttpClient` : arbre avec/sans `docs/story`, 401/403, rate-limit, 404, timeout, tronqué. | Oui |
| `tests/Unit/Service/Github/StoryTreeTest.php`               | Parsing convention, regroupement fichiers, `hasStories()`.                        | Oui                |
| `tests/Unit/Service/Repository/ProjectVerifierTest.php`     | Mapping réponses/exceptions → statut ; GitLab → `UnsupportedProvider` sans appel. | Oui               |
| `tests/Unit/Enum/VerificationStatusTest.php`                | `label()`/`badgeTone()` par cas.                                                  | Oui                |
| `tests/Functional/Twig/ProjectListVerifyTest.php`           | LiveAction `verify` (reader stubbé) met à jour statut + horodatage.               | Oui                |

### Fichiers modifiés

| Fichier                                              | Modification                                                                      | Prévu dans le plan |
|------------------------------------------------------|-----------------------------------------------------------------------------------|--------------------|
| `src/Entity/Project.php`                             | `verificationStatus` (défaut `Unverified`) + `verifiedAt` + `applyVerification()`/getters. | Oui       |
| `src/Manager/ProjectManager.php`                     | Injecte `ProjectVerifier` ; `verify()` privé appelé dans `create()`/`update()` ; `reverify()` public. | Oui + ajout `reverify()` (cf. §Ajouts) |
| `src/Controller/ProjectController.php`               | Route POST `app_project_verify` (CSRF) → `manager->reverify()` + flash.            | Non (ajout — cf. §Ajouts non prévus) |
| `src/Twig/Components/ProjectList.php`                | LiveAction `verify(#[LiveArg] int $id)` + persistance du statut.                  | Oui                |
| `templates/components/ProjectList.html.twig`         | Colonne « Statut » : badge + bouton « vérifier l'accès » (LiveAction).            | Oui                |
| `templates/project/show.html.twig`                   | Badge de statut + carte « Dernière vérification » (horodatage / « Jamais ») + bouton form POST. | Oui |
| `config/packages/framework.yaml`                     | Scoped client `github.client` (base_uri, en-têtes, timeout 5s, max_duration 10s). | Oui                |
| `fixtures/AppFixtures.php`                            | GitHub → `Unverified`, GitLab → `UnsupportedProvider` (via `applyVerification`, sans réseau). | Oui (enrichi — cf. §Ajouts) |
| `tests/Functional/Controller/ProjectControllerTest.php` | Couvre la route `verify` : nominal (Eligible + horodatage) et rejet CSRF.       | Non (ajout, suit la route) |
| `tests/e2e/projects.spec.ts`                         | Assertion badge repliée dans le test existant « declare then delete ».            | Oui                |

## Écarts avec le plan

### Écarts volontaires

| Prévu                                                                                          | Réalisé                                                                                                | Raison                                                                                                                                                          |
|------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Neutralisation réseau en test : « **`MockHttpClient` dans le vrai reader** » (question ouverte tranchée), sans binding d'un reader de test. | Double reader `StubRepositoryReader` bindé via `config/services_test.yaml` pour les tests fonctionnels/E2E ; `MockHttpClient` conservé dans le seul test unitaire du reader (`GitHubRepositoryReaderTest`). | Double plus lisible et pilotable (statut décidé par nom de dépôt : `*eligible*`/`*denied*`/`*offline*`/sinon `NotForge`) pour les tests fonctionnels et E2E ; le `MockHttpClient` reste pertinent pour couvrir le parsing HTTP réel (unitaire). Le plan proposait d'ailleurs cette option (« binder un `RepositoryReaderInterface` de test **OU** `MockHttpClient` ») dans §Fichiers à modifier. |
| E2E : « après déclaration, le badge de statut est présent (**reader neutralisé en env test**) ». | Assertion badge repliée dans le test existant « declare then delete » ; **pas de neutralisation réseau en env test** (serveur E2E en dev sur `forge-board.wip`, pas de `webServer` env test). | Éviter une 2ᵉ déclaration réseau + pollution DB. Le seul appel réseau restant est inhérent à la feature (la déclaration vérifie), token invalide → 401 rapide, save jamais bloqué. Résiduel documenté (cf. review §Mineurs, §Dette). |
| `badgeTone()` « **mappe vers les classes de badge de la DA** ». | `badgeTone()` renvoie une **tonalité sémantique** (`'ok'\|'neutral'\|'warning'\|'danger'`) ; le mapping tonalité→classes CSS vit dans le partial `_status_badge.html.twig`. | Découple l'enum de la DA (pas de classes CSS dans le PHP) ; le template porte la présentation. Conforme à l'esprit du plan (mêmes 4 tonalités nommées). |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---------------|--------|----------------|
| Aucun         | Toutes les sous-tâches et tous les fichiers du plan (dont les 5 fichiers de test prévus) ont été livrés. | — |

### Ajouts non prévus

| Élément ajouté                                                                 | Raison                                                                                                                                                                       |
|--------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| VO `VerificationResult` (`src/Service/Repository/VerificationResult.php`)       | Garde `ProjectVerifier` pur : il ne mute pas l'entité, il renvoie `status` + horodatage cohérents ; c'est le caller (`ProjectManager`) qui applique via `applyVerification()`. Préserve l'ordre du plan (sous-tâche 5 avant 6, l'entité n'existe pas encore quand le verifier est écrit). |
| Route POST `app_project_verify` (`ProjectController::verify`, CSRF) + `ProjectManager::reverify()` (public) | La fiche `show.html.twig` n'est **pas** un composant Live → un déclencheur serveur (form CSRF → `reverify()`) est nécessaire pour le bouton « vérifier » de la fiche. Le plan ne prévoyait que la LiveAction `verify` de `ProjectList` (liste). |
| Interface marqueur `RepositoryReaderException`                                  | Type commun des exceptions métier du reader (`Unreachable`/`AccessDenied`), traduites en statut par `ProjectVerifier`. Le plan ne listait que les deux exceptions concrètes. |
| Partial `templates/project/_status_badge.html.twig`                            | Support du mapping tonalité→classes DA (cf. §Écarts `badgeTone`), factorisé entre liste et fiche.                                                                            |
| Fixtures enrichies (GitLab → `UnsupportedProvider`, GitHub → `Unverified`)      | Le plan suggérait « ex. `Unverified` ». Léger enrichissement pour illustrer la variété des badges, posé sans réseau via `applyVerification()`.                               |

## Tests

| Code                                            | Type prévu        | Type réalisé                                              | Statut                    |
|-------------------------------------------------|-------------------|----------------------------------------------------------|---------------------------|
| `GitHubRepositoryReader`                        | Unit (`MockHttpClient`) | Unit `MockHttpClient` : arbre avec/sans story, 401/403, rate-limit (403 + `X-RateLimit-Remaining: 0`), 404 repo vs 404 docs, transport/timeout, tronqué, token en bearer jamais dans l'URL. | Fait — couverture étendue |
| `StoryTree` / `StoryFolder`                     | Unit              | Unit : filtre convention, regroupement fichiers, `hasStories()`. | Fait                |
| `ProjectVerifier`                               | Unit              | Unit : Eligible/NotForge/InvalidToken/Unreachable ; GitLab → `UnsupportedProvider` avec `expects(never)`. | Fait |
| `VerificationStatus`                            | Unit              | Unit : `label()`/`badgeTone()` par cas.                  | Fait                      |
| `ProjectList::verify` (Live)                    | Functional        | Functional : reader stubbé → statut + `verifiedAt` persistés. | Fait                 |
| Route `app_project_verify` (fiche)              | Non prévu         | Functional : nominal (Eligible + horodatage) + rejet CSRF. | Fait — ajout suivant la nouvelle route |
| Déclaration → badge                             | E2E (Playwright)  | E2E : assertion badge repliée dans « declare then delete ». | Fait — réseau non isolé en env test (cf. §Écarts, §Dette) |

## Dette technique identifiée

Issus de la review (mineurs) :

1. **[SECU] Web-profiler `http_client` en dev** — le HTML du profiler dev contient le token en clair (`auth_bearer`). **Accepté dev-only** : profiler désactivé en prod, outil mono-utilisateur local, token appartenant à l'utilisateur ; les surfaces exigées par le critère #7 (HTML servi, logs applicatifs) restent propres. Mitigation si multi-utilisateur un jour : désactiver le collector `http_client`.

Au-delà de la review :

2. **E2E non isolé du réseau** — pas de `webServer` en env test (serveur E2E en dev sur `forge-board.wip`). L'appel réseau inhérent à la déclaration subsiste ; un dépassement/timeout `api.github.com` pourrait rendre l'E2E dépendant de la connectivité sortante. Action : rejouer `make playwright` sur un runner avec/sans accès sortant pour confirmer le repli (`Unreachable`/`InvalidToken`) et la latence.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Déclarer ou éditer un projet GitHub déclenche une vérification dont le résultat s'affiche en badge (liste + fiche) — vérif synchrone dans `ProjectManager::create()/update()`.
- [x] Un repo GitHub joignable, token valide, avec `docs/story/NNN-<f|r|t>-<slug>/` → statut **éligible** (vérifié en réel sur `enao`).
- [x] Un repo joignable sans `docs/story/` conforme → **non-forge**, projet conservé.
- [x] Token invalide → **token invalide** (401/403) ; repo/URL inexistant ou erreur réseau → **injoignable**.
- [x] Bouton « vérifier l'accès » re-déclenche la vérification et met à jour statut + horodatage sans re-créer le projet (LiveAction sur la liste, route POST CSRF sur la fiche).
- [x] Un projet GitLab → **provider non scannable** sans aucun appel réseau (registry → pas de reader ; test `expects(never)`).
- [x] Le token n'apparaît ni dans le HTML servi, ni dans les logs applicatifs (vérifié en réel + test unitaire dédié). *Nuance dev-only : web-profiler, cf. §Dette #1.*
- [x] La liste s'affiche **sans appel réseau** : badges lus en base (profiler : 0 `http_client`).

## Leçons apprises

- **Une fiche non-Live impose un second déclencheur** : la LiveAction couvre la liste (composant Live), mais un bouton sur une page server-rendered (`show.html.twig`) exige une route POST CSRF distincte + une méthode manager publique (`reverify()`). Anticiper les deux points d'entrée dès le plan quand l'action existe à la fois en liste (Live) et en fiche (statique).
- **Séparer résultat et application d'état simplifie l'ordre d'implémentation** : renvoyer un VO (`VerificationResult`) plutôt que muter l'entité permet d'écrire et tester le verifier (sous-tâche 5) avant que l'entité ne porte les champs (sous-tâche 6), et garde le service pur.
- **Le choix « `MockHttpClient` vs double de test » n'est pas exclusif** : `MockHttpClient` reste le bon outil pour couvrir le parsing HTTP réel (unitaire), tandis qu'un double bindé (`services_test.yaml`) est plus lisible/pilotable pour les tests fonctionnels et E2E. Un plan gagnerait à trancher par niveau de test plutôt que globalement.
- **Isoler le réseau en E2E a un coût de cohérence** : un `webServer` en env test contredirait la préférence établie (`forge-board.wip`). Accepter l'appel réseau inhérent à la feature (déclaration = vérification) et documenter le résiduel est plus honnête que forcer une isolation qui diverge de la config réelle.
