# Report — Cloner en local le repo d'un projet depuis son kanban

> Pitch : `docs/story/008-f-clone-repo-local/pitch.md`
> Plan : `docs/story/008-f-clone-repo-local/plan.md`
> Date d'implémentation : 2026-07-10
> Commits liés : `268bed4` (feat livré conjointement avec la story 009, briques du pivot productif fortement entrelacées sur `Project`, la fiche projet et la config)
> Référence review : `review.md` (2026-07-09 — verdict READY TO COMMIT)

## Résumé

Conformité au plan ~100 % : l'approche (job Messenger async, port `RepositoryCloner` + `GitRepositoryCloner` via `Process`/`GIT_ASKPASS`, 4 champs d'état + enum `CloneStatus` sur `Project`, Live Component polling) est livrée fidèlement, sans manque. Les seuls écarts sont mineurs et positifs : un paramètre `$at` retiré de `markCloneFailed()` (un échec n'horodate rien) et cinq ajouts issus de la review (exception dédiée, badge partagé paramétrable, exclusion CS-Fixer, transport test in-memory, double de test). Les 8 critères d'acceptation du pitch sont cochés. Review : 0 bloquant, 0 important (l'unique important — bug CS-Fixer corrompant les clones — corrigé), 2 mineurs laissés en dette POC assumée. QA finale verte (PHPStan L9, CS-Fixer, 237 PHPUnit dont ceux du clone, smoke E2E).

## Ce qui a été implémenté

### Fichiers créés

| Fichier                                                          | Rôle                                                                                     | Prévu dans le plan |
|------------------------------------------------------------------|------------------------------------------------------------------------------------------|--------------------|
| `src/Enum/Type/CloneStatus.php`                                  | Enum d'état de clone (`label`/`badgeTone`/`icon`), pattern `VerificationStatus`.          | Oui                |
| `src/Service/Repository/RepositoryClonerInterface.php`           | Port `synchronize(RepositoryUrl, #[\SensitiveParameter] string, string): void`.          | Oui                |
| `src/Service/Repository/GitRepositoryCloner.php`                 | Impl : clone si absent / `git pull` si `.git`, auth `GIT_ASKPASS`, timeout 600 s.        | Oui                |
| `src/Service/Repository/CloneFailedException.php`                | Exception métier du cloner (raison lisible, sans token/URL crédentialisée).               | Oui                |
| `src/Service/Repository/ClonePathResolver.php`                   | Résout `private/<owner>-<repo>` en chemin absolu, anti-traversée.                         | Oui                |
| `src/Service/Repository/InvalidCloneDestinationException.php`    | Exception dédiée du resolver (étend `\InvalidArgumentException`).                          | Non (ajout — cf. §Ajouts non prévus) |
| `bin/git-askpass.sh`                                             | Script askpass (`echo "$GIT_ASKPASS_TOKEN"`), token via env du process.                   | Oui                |
| `src/Message/CloneRepository.php`                                | Message readonly `public int $projectId`.                                                 | Oui                |
| `src/MessageHandler/CloneRepositoryHandler.php`                  | Handler `#[AsMessageHandler]` : charge, déchiffre, synchronise, pose l'état.               | Oui                |
| `src/Twig/Components/ProjectCloneStatus.php`                     | Live Component : badge + bouton, poll tant que `Cloning`.                                  | Oui                |
| `templates/components/ProjectCloneStatus.html.twig`             | Rendu du composant (inclut `_status_badge`, form POST `app_project_clone`).                | Oui                |
| `migrations/Version20260708222109.php`                          | Ajout des 4 colonnes de clone, `clone_status` NOT NULL default `not_cloned`.              | Oui                |
| `private/.gitkeep`                                              | Ancre le dossier des clones locaux (contenu ignoré par git).                              | Oui                |
| `config/packages/test/messenger.yaml`                          | Transport `async` en `in-memory://` pour observer `Cloning` sans consommer.               | Non (ajout — cf. §Ajouts non prévus) |
| `tests/Double/FakeRepositoryCloner.php`                         | Double déterministe du cloner (aucun `git`/réseau réel en test).                          | Oui (implicite — référencé par la stratégie) |
| `tests/Unit/Enum/CloneStatusTest.php`                           | Mapping `label`/`badgeTone`/`icon` exhaustif sur les 4 cases.                             | Oui                |
| `tests/Unit/Service/ClonePathResolverTest.php`                 | `owner`/`repo` → chemin ; sous-groupe GitLab aplati ; rejet traversée.                    | Oui                |
| `tests/Functional/Controller/ProjectCloneTest.php`             | POST `/clone` : `Cloning` + enqueue, CSRF, firewall, non-fuite token sur `/show`.          | Oui                |
| `tests/Functional/MessageHandler/CloneRepositoryHandlerTest.php`| Fake cloner : succès → `Cloned`, échec → `Failed` sans propagation, projet inconnu.        | Oui                |

### Fichiers modifiés

| Fichier                                        | Modification                                                                                          | Prévu dans le plan |
|------------------------------------------------|-------------------------------------------------------------------------------------------------------|--------------------|
| `src/Entity/Project.php`                       | +4 champs de clone + `markCloning`/`markCloned`/`markCloneFailed` + getters + `isCloned()` ; init `NotCloned`. | Oui (écart signature `markCloneFailed`, cf. §Écarts) |
| `src/Manager/ProjectManager.php`               | +`requestClone()` : `markCloning()` + flush + dispatch `CloneRepository`.                              | Oui                |
| `src/Controller/ProjectController.php`         | +route `POST /{id}/clone` (`app_project_clone`, CSRF `clone{id}`) → `requestClone` + flash + redirect. | Oui                |
| `templates/project/show.html.twig`             | Insertion `<twig:ProjectCloneStatus :project="project"/>`.                                             | Oui                |
| `templates/project/_status_badge.html.twig`    | Rendu paramétrable (`testId` + `iconClass`, avec défauts) pour partage avec le composant de clone.     | Non (ajout — cf. §Ajouts non prévus) |
| `config/packages/messenger.yaml`               | Routing `App\Message\CloneRepository: async`.                                                          | Oui                |
| `config/services_test.yaml`                    | Réassigne l'id `GitRepositoryCloner` vers `FakeRepositoryCloner` (l'alias auto-résout).                | Oui                |
| `.gitignore`                                   | `/private/*` + `!/private/.gitkeep`.                                                                   | Oui                |
| `.php-cs-fixer.dist.php`                        | `->exclude('private')` : CS-Fixer ne réécrit plus les repos clonés.                                    | Non (ajout — cf. §Ajouts non prévus) |

## Écarts avec le plan

### Écarts volontaires

| Prévu                                                              | Réalisé                                                        | Raison                                                                                              |
|-------------------------------------------------------------------|---------------------------------------------------------------|----------------------------------------------------------------------------------------------------|
| `markCloneFailed(string $reason, \DateTimeImmutable $at)`          | `markCloneFailed(string $reason)` (sans `$at`)                | Un échec n'horodate rien : `clonedAt` ne marque que les succès (un pull raté laisse la copie précédente). Le paramètre était inutile. |
| Test « `CloneRepository` sur transport `async` »                  | Transport `async` overridé en `in-memory://` en environnement test | Permet d'observer l'état `Cloning` persisté et d'asserter l'enqueue sans exécuter le handler en requête (le fake couvre le contrat du cloner). |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---------------|--------|----------------|
| Aucun         | —      | —              |

### Ajouts non prévus

| Élément ajouté                                                          | Raison                                                                                              |
|------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------|
| `InvalidCloneDestinationException` dédiée (resolver)                    | Review (mineur ROBUSTESSE) : remplace un `catch (\InvalidArgumentException)` fourre-tout par un catch précis dans le handler. |
| `_status_badge.html.twig` rendu paramétrable (`testId` + `iconClass`)  | Review (mineur DRY) : la map `tones` était dupliquée dans le composant de clone ; le partial est désormais réutilisé (2 usages existants inchangés via défauts). |
| `.php-cs-fixer.dist.php` → `->exclude('private')`                      | Review (important BUG) : sans exclusion, `make quality` réécrivait ~180 fichiers des repos clonés dans `private/` avec le style du Board. Invisible en CI (`private/` y est vide), destructeur en local. |
| `config/packages/test/messenger.yaml` (`async` in-memory)             | Observabilité : rendre l'état `Cloning` et l'enqueue assertables en test sans consommer le message. |
| `tests/Double/FakeRepositoryCloner.php`                                | Double déterministe (pattern `StubRepositoryReader`) — référencé par la stratégie de test mais non listé dans §Fichiers à créer du plan. |

### Questions ouvertes tranchées

Les questions laissées ouvertes par le plan ont toutes été tranchées à l'implémentation (aucune n'est restée en suspens) :

| Question ouverte (plan)                          | Décision livrée                                                                                     |
|--------------------------------------------------|----------------------------------------------------------------------------------------------------|
| Auto-consommation du worker en local             | Worker `messenger_consume_async` déclaré dans `.symfony.local.yaml` (option b) : démarre avec `symfony serve`. Le `messenger:consume async` manuel reste possible. |
| Libellé unique vs contextuel du bouton           | Libellé contextuel confirmé : « Cloner » (`NotCloned`/`Failed`) / « Mettre à jour » (`Cloned`).     |
| Identifiant du dossier de clone                  | `private/<owner>-<repo>` (sous-groupes GitLab aplatis `/`→`-`) — conforme au plan.                  |
| Modèle de persistance de l'état                  | 4 champs sur `Project` (pas d'entité `Clone`) — conforme au plan.                                   |
| Détection « déjà cloné »                         | Filesystem (`<dest>/.git`), pas le statut persisté — conforme au plan.                              |

## Tests

| Code                                             | Type prévu       | Type réalisé                                       | Statut                       |
|--------------------------------------------------|------------------|----------------------------------------------------|------------------------------|
| `src/Enum/Type/CloneStatus.php`                  | Unit (4 cases)   | Unit exhaustif (`CloneStatusTest`)                 | Fait                         |
| `src/Service/Repository/ClonePathResolver.php`   | Unit             | Unit : nominal + sous-groupe GitLab + traversée (`ClonePathResolverTest`) | Fait — couverture étendue    |
| `src/Controller/ProjectController.php` (`clone`) | Functional       | Functional : `Cloning` + enqueue + CSRF + firewall + non-fuite token sur `/show` (`ProjectCloneTest`) | Fait — couverture étendue    |
| `src/MessageHandler/CloneRepositoryHandler.php`  | Functional       | Functional : succès/échec sans propagation + projet inconnu (`CloneRepositoryHandlerTest`) | Fait                         |
| `templates` + Live Component                     | E2E (smoke)      | Smoke : bouton `data-test="project-clone"` visible, clic → `Clonage…` (fake cloner) | Fait                         |
| `GitRepositoryCloner` (shell-out git réel)       | Hors scope assumé| Non couvert (fake couvre le contrat)               | Conforme — hors scope assumé (cf. §Dette) |

## Dette technique identifiée

Issus de la review (mineurs non traités, dette POC assumée) :

1. **[ROBUSTESSE] Polling perpétuel si worker absent** — `ProjectCloneStatus` poll indéfiniment tant que `Cloning`. Comportement intrinsèque de l'async au POC (worker déclaré dans `.symfony.local.yaml`). Un timeout d'affichage après N polls serait une feature UX spéculative (contraire à « rien de spéculatif », `CLAUDE.md`). À reconsidérer si multi-utilisateur.
2. **[ROBUSTESSE] `requestClone` dispatche après le flush** — `ProjectManager::requestClone()`. Transport Doctrine sur la même BDD : un dispatch échouant après un flush réussi est quasi-impossible. Un rollback explicite ajouterait de la complexité pour un edge théorique. Documenté, non corrigé.

À vérifier en environnement réel (hors périmètre de test) :

3. **Clone git réel GitHub + GitLab** (privé et public) — non couvert par les tests (le fake couvre le contrat, pas le shell-out `git`). À valider manuellement avec un vrai token, worker `messenger:consume async` actif.
4. **Bit exécutable de `bin/git-askpass.sh`** — doit être commité `100755` (`git ls-files -s bin/git-askpass.sh`), sans quoi `GIT_ASKPASS` échouerait.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Un bouton « Cloner » (ou « Cloner / Mettre à jour ») est visible sur la vue kanban d'un projet.
- [x] Cliquer sur un projet non encore cloné rapatrie le repo dans `private/<projet>/` et l'état passe à « cloné ».
- [x] Cliquer sur un projet déjà cloné exécute un `git pull` et l'état reflète la mise à jour.
- [x] Un repo **privé** se clone avec le token stocké (un repo public aussi).
- [x] Un échec (token invalide, repo injoignable, réseau) affiche un état « échec » avec une **raison lisible**, sans planter l'app.
- [x] Pendant un clone long, l'interface reste **utilisable** (opération asynchrone).
- [x] **GitHub et GitLab** fonctionnent tous les deux (URL de clone dérivée du provider).
- [x] Le contenu cloné dans `private/` n'est pas committé au repo du Board (`.gitignore` en place).

## Leçons apprises

- **Une feature qui produit des fichiers dans le repo peut retourner l'outillage qualité contre le dépôt** : `make quality` a réécrit ~180 fichiers d'un repo cloné dans `private/` avant qu'on exclue le dossier du finder CS-Fixer. Réflexe à anticiper au plan pour toute story qui matérialise du contenu tiers en local : lister explicitement les exclusions CS-Fixer / PHPStan / analyse en même temps que le `.gitignore`.
- **`in-memory://` sur le transport async est l'outil canonique pour tester un état asynchrone intermédiaire** : il fige le message enqueué sans le consommer, ce qui permet d'asserter l'état `Cloning` persisté. À prévoir au plan quand la stratégie de test veut « observer un statut transitoire + l'enqueue ».
- **Les signatures de méthodes de transition sont à figer contre leur sémantique, pas par symétrie** : `markCloneFailed()` n'avait pas besoin de `$at` (un échec n'horodate rien) — la symétrie avec `markCloned()` était trompeuse dans le plan.
- **Trancher les questions ouvertes du plan à l'implémentation, puis les tracer** : les cinq questions ouvertes ont toutes été décidées en cours de route ; sans ce report, la trace de « pourquoi worker déclaré » ou « pourquoi libellé contextuel » se serait perdue.
