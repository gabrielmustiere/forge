# Plan technique — Afficher le kanban d'un projet

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/005-f-kanban-projet/`
> **Amont** : `pitch.md`

## Approche retenue

Le kanban est une **page server-rendered** (pas de SPA, pas de Live Component pour le board). Le contrôleur `ProjectController::show` délègue à un nouveau service d'orchestration **`ProjectBoardBuilder`** qui, sur le patron exact de `ProjectVerifier`, résout le reader adapté au provider via `RepositoryReaderRegistry::readerFor()` (garde-fou `failure` si aucun reader ne le supporte), normalise l'URL, déchiffre le token, appelle `RepositoryReaderInterface::readStoryTree()` (scan **live** à chaque ouverture, règle 8), puis mappe chaque `StoryFolder` via le `StoryStageMapper` existant (réutilisé tel quel). Il produit un value object **`Board`** : les stories groupées dans les quatre colonnes ordonnées (`PipelineStage::isOnPipeline()`), triées par numéro décroissant, plus le bandeau « À vérifier » (`PipelineStage::AVerifier`). Les exceptions du reader (`RepositoryUnreachableException`, `RepositoryAccessDeniedException`) sont **catchées** et traduites en un `BoardResult` d'échec — jamais remontées au template (garde-fou, règle 10), exactement comme `ProjectVerifier` transforme un échec en statut.

Aucune position n'est persistée : le board est **recalculé à la volée** à chaque affichage (aucune entité, aucune migration). Le **titre de carte est le slug humanisé** (zéro appel réseau au chargement) ; le **titre réel `# H1`** n'apparaît que dans le **drawer**, où le document choisi est rendu en markdown. Le drawer est un panneau Flowbite : un contrôleur Stimulus léger gère l'ouverture/fermeture et peuple la liste des documents de la story **depuis les `data-` de la carte cliquée** (aucun appel), et le contenu d'un document est chargé par un **`<turbo-frame loading="lazy">`** pointant une route dédiée `GET /projects/{id}/story/{storyId}/doc/{filename}`. Cette route lit **un seul** fichier via une nouvelle méthode `RepositoryReaderInterface::readFile()` et le rend avec le filtre Twig **`markdown_to_html`** (`twig/markdown-extra`), dont le converter est configuré en **mode sûr** dans `services.yaml` (`html_input: strip`, `allow_unsafe_links: false`, plus `ExternalLinkExtension` qui ouvre les liens tiers en nouvel onglet avec `rel="noopener noreferrer"` — contenu de repo tiers). Le reader gagne donc une seule capacité : lire le contenu d'un fichier précis.

> **Dépendance à installer** : `league/commonmark` et `twig/markdown-extra` ne sont **pas** installés (seul `twig/extra-bundle` l'est). Étape préalable : `composer require twig/markdown-extra` (tire `league/commonmark`). Le filtre `markdown_to_html` est alors fourni par `twig/extra-bundle`.

### Mécanismes mobilisés

- **Service d'orchestration pur (`ProjectBoardBuilder`)** : même patron que `ProjectVerifier` (`RepositoryReaderRegistry` pour résoudre le reader par provider + normalizer + `TokenCipher` + catch des exceptions métier). Injection par constructeur, `readonly`, `strict_types`. Choisi plutôt qu'un Live Component pour éviter le re-scan à chaque interaction.
- **Extension d'interface (`RepositoryReaderInterface::readFile`)** : ajout d'une capacité de lecture de contenu au contrat existant, implémentée par `GitHubRepositoryReader` (et à venir GitLab en V2). Pas de décoration : c'est une extension du contrat, pas un override de comportement.
- **`EntityValueResolver`** : la route `show` et la route drawer résolvent `Project` par `{id}` via le resolver (déjà en place sur `show`).
- **Turbo Frame lazy (`ux-turbo`)** : chargement à la demande du contenu d'un document dans le drawer, sans JS réseau custom.
- **Contrôleur Stimulus (`stimulus-bundle`)** : `story-drawer`, uniquement pour ouvrir/fermer le panneau et injecter la liste des documents depuis les `data-` de la carte.
- **Filtre `markdown_to_html` (`twig/markdown-extra` via `twig/extra-bundle`)** : rendu markdown dans le drawer. Le service **`twig.markdown.default`** (`Twig\Extra\Markdown\LeagueMarkdown`, consommé par le `MarkdownRuntime` du bundle) est **redéfini** en `services.yaml` pour s'appuyer sur un `League\CommonMark\MarkdownConverter` bâti sur un `Environment` explicite (core + GFM + `ExternalLinkExtension`) configuré sûr (`html_input: strip`, `allow_unsafe_links: false`, `external_link.open_in_new_window`). **NB** : cibler `twig.markdown.default` directement — un alias sur `Twig\Extra\Markdown\MarkdownInterface` est **ignoré** par le runtime du bundle. Pas d'extension maison — on configure le mécanisme existant.
- **Enum backed string (`Track`)** : convention projet `src/Enum/Type/` (comme `PipelineStage`, `Provider`, `VerificationStatus`), avec `label()`.

### Alternatives écartées

- **Kanban en Live Component** : chaque `LiveAction` ré-hydrate le composant ; soit on re-scanne le repo à chaque clic (coûteux, viole « scan une fois »), soit on sérialise tout le board en `LiveProp` (lourd). La page server-rendered + drawer par route est plus simple et plus fidèle au read-only.
- **Titre `# H1` lu sur chaque carte au chargement** : imposerait un appel GitHub par story à l'ouverture (~30 appels). Écarté au profit du slug sur la carte + H1 dans le drawer (cf. pitch règle 4, changelog).
- **Persister l'état scanné (colonne/board en base)** : contredit « état déduit, jamais saisi » et le scan live ; introduirait une migration et un risque de dérive. Recalcul à la volée retenu.
- **Fetch Stimulus custom pour le contenu du doc** : plus de JS à maintenir ; `ux-turbo` est déjà installé et le `<turbo-frame loading="lazy">` couvre le besoin sans code réseau maison.
- **Fabriquer une liste de docs côté serveur par re-scan de la story** : un appel arbre supplémentaire par ouverture de drawer ; la liste est déjà connue (`StoryCard.files` issu du scan initial), on la passe en `data-`.

## Modèle de données

**Aucun impact modèle.** Aucune entité créée ni modifiée, **aucune migration** : le board est recalculé à la volée à chaque affichage (règle 8, principe vision « état déduit, jamais saisi »). L'entité `Project` existante fournit déjà provider / url / token chiffré, tout ce dont `ProjectBoardBuilder` a besoin.

## Périmètre

### Fichiers à créer

| Fichier | Rôle |
|---|---|
| `src/Enum/Type/Track.php` | Enum `feature`/`refacto`/`tech` ; `fromLetter('f'\|'r'\|'t')`, `label()`. |
| `src/Service/Board/StoryId.php` | VO : `parse('005-f-kanban-projet')` → `number` (int), `track` (`Track`), `slug` ; `humanizedTitle()` (« Kanban projet »). |
| `src/Service/Board/StoryCard.php` | VO immuable d'une carte : id, number, track, slug, `humanizedTitle`, `PipelineStage`, `files` (docs présents ordonnés). |
| `src/Service/Board/Board.php` | VO du tableau : cartes groupées par colonne + bandeau ; `cardsFor(PipelineStage)`, `countFor()`, `banner()`, `bannerCount()`, `isEmpty()`. |
| `src/Service/Board/BoardResult.php` | Résultat d'orchestration : `success(Board)` \| `failure(reason)` ; garde-fou (règle 10). |
| `src/Service/Board/ProjectBoardBuilder.php` | Orchestration : `Project` → scan live → mapping → `BoardResult` (catch `Unreachable`/`AccessDenied`). |
| `src/Service/Board/StoryDocumentFetcher.php` | Lit un document d'une story (decrypt + `reader->readFile`) pour le drawer ; catch → exception friendly. |
| `src/Service/Board/StoryDocumentUnavailableException.php` | Exception « friendly » du fetcher : absorbe les erreurs bas niveau du reader (règle 10). |
| `src/Service/Github/DevFakeRepositoryReader.php` | Décorateur dev-only (`#[When('dev')]` + `#[AsDecorator]`) : sert des stories déterministes quand `APP_FAKE_REPOSITORY_READER=1`, sinon délègue au vrai reader. Rend l'E2E du board reproductible sans dépôt réel. |
| `src/Service/Github/FakeRepositoryCatalog.php` | Catalogue déterministe de données factices (arbre + contenus), source unique partagée par le décorateur dev et le `StubRepositoryReader` de test. |
| `templates/project/_board.html.twig` | Rend les 4 colonnes (label + compteur), le bandeau « À vérifier », l'état vide et l'état d'erreur. |
| `templates/project/_card.html.twig` | Carte : badge track, id, slug ; `data-` (storyId, liste docs) pour le drawer. |
| `templates/project/_drawer.html.twig` | Panneau Flowbite : liste des docs (peuplée par Stimulus) + `<turbo-frame loading="lazy">` du contenu. |
| `templates/project/_doc.html.twig` | Fragment rendu par la route drawer : `<turbo-frame>` contenant le markdown d'un doc. |
| `assets/controllers/story_drawer_controller.js` | Stimulus : ouvre/ferme le drawer, injecte la liste des docs de la carte, arme le `src` du turbo-frame. |
| `tests/Unit/Service/Board/StoryIdTest.php` | Parsing id → number/track/slug + humanisation ; ids limites. |
| `tests/Unit/Enum/Type/TrackTest.php` | `fromLetter` (f/r/t) + labels. |
| `tests/Unit/Service/Board/ProjectBoardBuilderTest.php` | Groupement colonnes, tri NNN décroissant, bandeau, vide, échec (reader mocké). |
| `tests/Functional/Controller/ProjectBoardTest.php` | `show` : colonnes/compteurs/bandeau/vide/erreur ; route drawer (doc rendu, sanitize, filename invalide rejeté). Convention `Functional/Controller` du projet (pas de dossier `Application`). |
| `tests/e2e/project-board.spec.ts` | Playwright : ouvrir board → colonnes visibles → clic carte → drawer + liste → clic doc → markdown rendu. |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Service/Repository/RepositoryReaderInterface.php` | Ajouter `readFile(RepositoryUrl $url, string $plainToken, string $path): string` (mêmes `@throws` que `readStoryTree`). |
| `src/Service/Github/GitHubRepositoryReader.php` | Implémenter `readFile` : `GET /repos/{owner}/{repo}/contents/{path}` en `Accept: application/vnd.github.raw`, retour string ; 404 → exception friendly, 401/403 → `AccessDenied`. |
| `src/Controller/ProjectController.php` | `show` : consommer `ProjectBoardBuilder` → passer `BoardResult` au template. Ajouter route `app_project_story_doc` (`GET /{id}/story/{storyId}/doc/{filename}`, regex stricte anti-traversal) → `StoryDocumentFetcher` + rendu `_doc.html.twig`. |
| `templates/project/show.html.twig` | Remplacer le placeholder kanban (l.66-75) par `include('project/_board.html.twig')` + `include('project/_drawer.html.twig')`. |
| `composer.json` | `composer require twig/markdown-extra` (tire `league/commonmark`) — dépendance actuellement absente. |
| `config/services.yaml` | Redéfinir `twig.markdown.default` (`LeagueMarkdown`) sur un `MarkdownConverter` bâti sur un `Environment` sûr (core + GFM + `ExternalLinkExtension`, `html_input: strip`, `allow_unsafe_links: false`). Cibler `twig.markdown.default` — l'alias `MarkdownInterface` est ignoré par le runtime. |
| `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php` | Ajouter les cas `readFile` (`MockHttpClient` : raw, 404, 401/403) au test unitaire existant du reader. |
| `tests/Double/StubRepositoryReader.php` | Déléguer à `FakeRepositoryCatalog` (source partagée) + implémenter `readFile`. |
| `.env` / `.env.example` | Flag `APP_FAKE_REPOSITORY_READER=0` (défaut sûr : dev/prod lisent les vrais dépôts). |
| `.github/workflows/ci.yml` | `APP_FAKE_REPOSITORY_READER: 1` sur le job E2E (board reproductible sans dépôt réel). |

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **API REST/GraphQL** : non. La route drawer renvoie un **fragment HTML** (Turbo Frame), pas une ressource API.
- **i18n** : libellés FR en dur via `PipelineStage::label()` / `Track::label()` et templates (colonnes, bandeau, états vide/erreur). Pas de contenu multilingue ; `|trans` non requis (projet mono-langue, cohérent avec l'existant `show.html.twig`).
- **Permissions** : inchangé — tout est derrière le firewall `login` (`access_control` existant sur `/projects`). Ni voter ni rôle nouveau (mono-utilisateur).
- **Emails / notifications** : non.
- **Migration de données** : **aucune** (rien de persisté).
- **Comportement par défaut** : la page projet, aujourd'hui « Kanban à venir », affiche désormais le tableau réel.
- **Sécurité** : le contenu markdown vient d'un repo tiers → rendu **sanitizé** (`html_input: strip`, `allow_unsafe_links: false`). La route drawer valide `storyId` (regex `\d{3}-[frt]-[a-z0-9-]+`) et `filename` (`[a-z0-9._-]+\.md`, pas de `/` ni `..`) pour interdire toute traversée de chemin. Le token reste chiffré, déchiffré au plus près de l'appel (patron `ProjectVerifier`), jamais loggé.

## Ordre d'exécution

1. [ ] `src/Enum/Type/Track.php` + `src/Service/Board/StoryId.php` (VO purs) + tests unitaires.
2. [ ] `RepositoryReaderInterface::readFile` + implémentation `GitHubRepositoryReader::readFile` + test `MockHttpClient`.
3. [ ] VOs `StoryCard`, `Board`, `BoardResult`.
4. [ ] `ProjectBoardBuilder` (orchestration + catch) + test unitaire (reader mocké : colonnes, tri, bandeau, vide, échec).
5. [ ] `composer require twig/markdown-extra` + config `services.yaml` (converter GFM sûr via `twig.markdown.default`).
6. [ ] `StoryDocumentFetcher` (lecture d'un doc pour le drawer).
7. [ ] `ProjectController` : `show` consomme le builder ; route `app_project_story_doc` (validation stricte).
8. [ ] Templates : `_board`, `_card`, `_drawer`, `_doc` + branchement dans `show.html.twig` ; contrôleur Stimulus `story_drawer`.
9. [ ] Tests Application (`WebTestCase`, reader stubé) + E2E Playwright.
10. [ ] QA : `vendor/bin/php-cs-fixer` + `vendor/bin/phpstan` (level 9) + `npm run build` + `make phpunit` + `make playwright`.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `src/Service/Board/StoryId.php` | Unit | parsing number/track/slug, humanisation, ids des 3 tracks. |
| `src/Enum/Type/Track.php` | Unit | `fromLetter` f/r/t + labels. |
| `src/Service/Board/ProjectBoardBuilder.php` | Unit | groupement par colonne, tri NNN décroissant, bandeau « À vérifier », board vide, échec reader → `BoardResult::failure` (reader **mocké** — pas de réseau). |
| Config `markdown_to_html` (converter sûr) | Functional | via la route drawer : un doc contenant `<script>`/HTML brut est **neutralisé** (strip) et les liens externes ouverts en `target="_blank"`/`noopener` (contenu tiers). |
| `src/Service/Github/GitHubRepositoryReader.php` (`readFile`) | Unit (`MockHttpClient`) | contenu raw renvoyé, 404 → exception, 401/403 → `AccessDenied`. Cas ajoutés au test unitaire existant du reader. Jamais d'appel réseau réel. |
| `src/Controller/ProjectController.php` | Functional (`WebTestCase`) | `show` : 4 colonnes + compteurs, bandeau présent/absent, carte `r`/`t` jamais en Cadrage, état vide, état erreur (reader stubé en échec) ; route drawer : doc rendu, markdown tiers sanitizé, `filename` invalide → 404, connecté requis. Sélecteurs `data-test`. |
| `templates/project/*`, drawer | E2E (Playwright) | ouvrir un projet → colonnes visibles → clic carte → drawer ouvert + liste docs → clic doc → markdown rendu ; sélecteurs `data-test`. |

**Hors scope tests pour cette story** :

- Pas de test du `StoryStageMapper` (déjà couvert par `004`).
- Pas de test de bout en bout réseau GitHub réel — tout est mocké/stubé.
- Pas de test de rafraîchissement/erreur riche (relève de `sync-manuelle`).

## Risques et mitigations

- **Latence du scan live** : `show` appelle GitHub à chaque ouverture (borné par `timeout: 5` / `max_duration: 10` du `github.client`). Acceptable pour « quelques dizaines de stories ». Mitigation possible plus tard (cache court), **hors scope** ici ; noté pour `sync-manuelle`.
- **Traversée de chemin sur la route drawer** : un `filename`/`storyId` malicieux pourrait viser un autre fichier. Mitigation : regex stricte sur les deux segments (pas de `/`, pas de `..`) + le reader ne construit que `docs/story/{storyId}/{filename}`.
- **Injection HTML via markdown tiers** : le contenu vient d'un repo non maîtrisé. Mitigation : converter en mode `html_input: strip` + `allow_unsafe_links: false` ; le `|raw` du template n'est appliqué qu'à la sortie **déjà assainie**. Test de sécurité dédié.
- **Doc absent / illisible dans le drawer** : `readFile` peut renvoyer 404 (fichier supprimé entre le scan et le clic) ou échouer. Mitigation : `StoryDocumentFetcher` catch → le turbo-frame affiche un message d'erreur minimal, sans casser la page (cohérent règle 10).
- **Board partiellement à jour** : la liste des docs du drawer vient du scan initial (`data-`), le contenu est lu live ; un décalage rare est possible (fichier disparu). Acceptable en lecture seule ; le cas 404 est géré.

## Questions ouvertes

_Toutes tranchées avant implémentation._

- **Rendu markdown** : extension Twig maison vs runtime `twig/markdown-extra`. → **tranché : runtime `markdown_to_html`**, converter GFM configuré sûr en `services.yaml` via redéfinition de `twig.markdown.default` (l'alias `MarkdownInterface` est ignoré par le runtime — cf. changelog). Dépendance `twig/markdown-extra` à installer (absente).
- **Format de `BoardResult::failure`** : string vs enum. → **tranché : message `string` court** (le diagnostic riche relève de `sync-manuelle`).
- **Drawer / Turbo** : drawer partagé (src réarmé) vs frame par doc. → **tranché : drawer partagé**, `src` du `<turbo-frame>` réarmé au clic par Stimulus.
