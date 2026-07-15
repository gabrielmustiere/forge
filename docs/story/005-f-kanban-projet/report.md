# Report — Afficher le kanban d'un projet

> **But** : constater l'écart entre l'intention et le code livré — écarts, dette, suites.
> **Registre** : factuel
> **Story** : `docs/story/005-f-kanban-projet/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

Feature livrée avec un taux de conformité au plan d'environ 90 % : les 11 critères d'acceptation du pitch sont satisfaits (132 PHPUnit / 312 assertions + 11 E2E verts, review **PRÊT À COMMITER**, 0 bloquant / 0 important / 0 mineur restant). Trois écarts structurants par rapport au plan : (1) le wiring markdown a dû être refait via `twig.markdown.default` — l'alias `MarkdownInterface` prévu au plan était ignoré par le runtime Twig — et enrichi d'un `ExternalLinkExtension` ; (2) un socle de reader factice dev/test (`DevFakeRepositoryReader` + `FakeRepositoryCatalog` + flag `APP_FAKE_REPOSITORY_READER`) a été ajouté hors plan pour rendre l'E2E du board reproductible sans dépôt réel ; (3) l'orchestration passe par `RepositoryReaderRegistry` (résolution par provider) plutôt que par une injection directe du reader, et les tests ont été replacés sur la convention `Functional` existante. Dette résiduelle nulle côté review (tous les mineurs corrigés).

## Périmètre livré

### Fichiers créés

| Fichier | Rôle | Prévu dans le plan |
|---|---|---|
| `src/Enum/Type/Track.php` | Enum `feature`/`refacto`/`tech` ; `fromLetter()`, `label()`. | Oui |
| `src/Service/Board/StoryId.php` | VO : `parse()` → number/track/slug + `humanizedTitle()`. | Oui |
| `src/Service/Board/StoryCard.php` | VO immuable d'une carte (id, stage, docs présents ordonnés). | Oui |
| `src/Service/Board/Board.php` | VO du tableau : cartes par colonne + bandeau, compteurs, `isEmpty()`. | Oui |
| `src/Service/Board/BoardResult.php` | Résultat d'orchestration `success`/`failure` (garde-fou règle 10). | Oui |
| `src/Service/Board/ProjectBoardBuilder.php` | Orchestration scan live → mapping → `BoardResult`. | Oui (via registry — cf. §Écarts) |
| `src/Service/Board/StoryDocumentFetcher.php` | Lit un document d'une story pour le drawer, catch → exception friendly. | Oui |
| `src/Service/Board/StoryDocumentUnavailableException.php` | Exception friendly du fetcher (règle 10). | Non (ajout — cf. §Ajouts non prévus) |
| `src/Service/Github/DevFakeRepositoryReader.php` | Décorateur dev-only du reader (opt-in `APP_FAKE_REPOSITORY_READER`). | Non (ajout — cf. §Ajouts non prévus) |
| `src/Service/Github/FakeRepositoryCatalog.php` | Catalogue déterministe partagé dev/test (arbre + contenus factices). | Non (ajout — cf. §Ajouts non prévus) |
| `templates/project/_board.html.twig` | 4 colonnes (label + compteur), bandeau, état vide, état erreur. | Oui |
| `templates/project/_card.html.twig` | Carte : badge track, id, slug ; `data-` pour le drawer. | Oui |
| `templates/project/_drawer.html.twig` | Panneau Flowbite + liste docs + `<turbo-frame loading="lazy">`. | Oui |
| `templates/project/_doc.html.twig` | Fragment turbo-frame du markdown d'un doc. | Oui |
| `assets/controllers/story_drawer_controller.js` | Stimulus : ouverture/fermeture, liste des docs, armement du `src`. | Oui |
| `tests/Unit/Service/Board/StoryIdTest.php` | Parsing id → number/track/slug + humanisation. | Oui |
| `tests/Unit/Enum/Type/TrackTest.php` | `fromLetter` f/r/t + labels. | Oui |
| `tests/Unit/Service/Board/ProjectBoardBuilderTest.php` | Groupement colonnes, tri NNN décroissant, bandeau, vide, échec. | Oui |
| `tests/Functional/Controller/ProjectBoardTest.php` | `show` + route drawer (colonnes/compteurs/bandeau/vide/erreur/sanitize/traversal). | Oui (namespace `Functional`, pas `Application` — cf. §Écarts) |
| `tests/e2e/project-board.spec.ts` | Playwright : board → colonnes → drawer → liste → doc rendu. | Oui |

### Fichiers modifiés

| Fichier | Modification | Prévu dans le plan |
|---|---|---|
| `src/Service/Repository/RepositoryReaderInterface.php` | Ajout `readFile(url, plainToken, path): string`. | Oui |
| `src/Service/Github/GitHubRepositoryReader.php` | Implémentation `readFile` (contents API raw, 404/401/403). | Oui |
| `src/Controller/ProjectController.php` | `show` consomme le builder ; route `app_project_story_doc` (regex strictes). | Oui |
| `templates/project/show.html.twig` | Remplacement du placeholder par `_board` + `_drawer`. | Oui |
| `composer.json` / `composer.lock` | `require twig/markdown-extra` (tire `league/commonmark`). | Oui |
| `config/services.yaml` | Wiring converter GFM sûr via `twig.markdown.default` + `ExternalLinkExtension`. | Écart volontaire (cf. §Écarts) |
| `tests/Double/StubRepositoryReader.php` | Délègue à `FakeRepositoryCatalog` + implémente `readFile`. | Oui (adapté à l'ajout catalogue) |
| `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php` | Cas `readFile` (raw, 404, 401/403) ajoutés au test existant. | Écart volontaire (Unit vs Functional dédié — cf. §Écarts) |
| `.env` / `.env.example` | Flag `APP_FAKE_REPOSITORY_READER=0` (défaut sûr). | Non (ajout — cf. §Ajouts non prévus) |
| `.github/workflows/ci.yml` | `APP_FAKE_REPOSITORY_READER: 1` sur le job E2E. | Non (ajout — cf. §Ajouts non prévus) |

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison |
|---|---|---|
| §Approche/§Framework : `ProjectBoardBuilder` et `StoryDocumentFetcher` injectent directement `RepositoryReaderInterface::readStoryTree()` (patron `ProjectVerifier`). | Injection de `RepositoryReaderRegistry` avec `readerFor($project->getProvider())` et garde-fou `failure`/exception si provider non supporté. | Résolution du reader par provider plus robuste (multi-provider à venir, GitLab V2) et cohérente avec le registre existant ; ajoute un chemin d'échec propre si aucun reader ne supporte le provider. |
| §Framework/§Fichiers : converter GFM sûr aliasé sur `Twig\Extra\Markdown\MarkdownInterface`, classe `GithubFlavoredMarkdownConverter`. | Redéfinition du service `twig.markdown.default` (`LeagueMarkdown`) sur un `MarkdownConverter` bâti sur un `Environment` explicite (core + GFM + external-link). | L'alias sur `MarkdownInterface` était **ignoré** par le `MarkdownRuntime` de `twig/extra-bundle` (bug silencieux attrapé en review) : le filtre passe par `twig.markdown.default`. Wiring corrigé + prouvé par test de sanitization. |
| §Framework : converter sûr = `html_input: strip` + `allow_unsafe_links: false`. | Idem + ajout de `ExternalLinkExtension` (`open_in_new_window`, `rel="noopener noreferrer"`). | Retour de review (mineur ROBUSTESSE/SECU) : les liens markdown tiers s'ouvrent en nouvel onglet de façon sûre, corrigé au niveau du converter plutôt qu'en hack template. |
| §Fichiers/§Tests : `tests/Application/Controller/ProjectBoardTest.php`. | `tests/Functional/Controller/ProjectBoardTest.php` (`WebTestCase`). | Le projet n'a pas de répertoire `tests/Application/` ; la convention existante pour les tests contrôleur `WebTestCase` est `tests/Functional/Controller/`. Alignement sur l'existant. |
| §Fichiers/§Tests : test `readFile` dédié `tests/Functional/Service/Github/GitHubRepositoryReaderReadFileTest.php`. | Cas `readFile` (raw, 404, 401/403) ajoutés au `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php` existant, en `MockHttpClient`. | Pas de réseau réel (MockHttpClient) → reste unitaire ; regroupé avec les cas `readStoryTree` du même reader plutôt qu'un fichier Functional distinct. Couverture équivalente. |
| §Règle 6 / §documentsFor : tous les `.md` (précédence forge puis transversaux). | `documentsFor()` exclut les `.md` au nom non servable par la route (majuscule, sous-chemin, ex. `README.md`). | Correction du **bloquant** review : générer l'URL d'un doc au nom non conforme au `requirements` de route (`strict_requirements`) plantait le rendu (viole règles 9/10). Les docs non servables sont exclus du drawer, test de non-régression ajouté. |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---|---|---|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté | Raison |
|---|---|
| `src/Service/Github/DevFakeRepositoryReader.php` (`#[When('dev')]` + `#[AsDecorator]`) | Rendre l'E2E Playwright du board reproductible sans dépendre d'un dépôt GitHub réel ; opt-in par flag, délègue au vrai reader quand désactivé (dogfooding préservé). |
| `src/Service/Github/FakeRepositoryCatalog.php` | Source unique de données factices déterministes partagée entre le décorateur dev et le `StubRepositoryReader` de test (zéro duplication/drift entre dev et test). |
| Flag `APP_FAKE_REPOSITORY_READER` (`.env`, `.env.example` défaut `0`, `ci.yml` = `1` sur le job E2E) | Plomberie du reader factice ; défaut sûr (0) pour ne pas altérer le comportement dev/prod. |
| `src/Service/Board/StoryDocumentUnavailableException.php` | Exception « friendly » dédiée pour absorber les erreurs bas niveau du reader dans le drawer (règle 10). Le plan évoquait « catch → exception friendly » sans nommer de classe : détail d'implémentation. |

## Tests

| Code | Type prévu | Type réalisé | Statut |
|---|---|---|---|
| `src/Service/Board/StoryId.php` | Unit | Unit (`StoryIdTest`) | Fait |
| `src/Enum/Type/Track.php` | Unit | Unit (`TrackTest`) | Fait |
| `src/Service/Board/ProjectBoardBuilder.php` | Unit (reader mocké) | Unit — colonnes, tri, bandeau, vide, échec + non-régression `documentsFor` | Fait — couverture étendue |
| Config `markdown_to_html` (converter sûr) | Application (via route drawer) | Functional — `<script>` strippé + liens externes `target="_blank"`/`noopener` | Fait — couverture étendue |
| `src/Service/Github/GitHubRepositoryReader.php` (`readFile`) | Functional (`MockHttpClient`) | Unit `MockHttpClient` (raw, 404, 401/403), regroupé au test existant | Fait |
| `src/Controller/ProjectController.php` | Application (`WebTestCase`) | Functional (`WebTestCase`) — colonnes/compteurs/bandeau/vide/erreur/sanitize/traversal | Fait |
| `templates/project/*`, drawer | E2E (Playwright) | E2E — board → colonnes → drawer → liste → doc rendu | Fait |

Total : 132 PHPUnit / 312 assertions + 11 E2E verts (cf. review).

## Dette technique identifiée

Issus de la review : **aucun** — les 3 mineurs (drawer sans doc, `loading="lazy"`, liens externes sûrs) et le bloquant (`README.md` non servable) ont tous été corrigés dans la story.

Au-delà de la review :

1. **Latence du scan live** — `show` appelle GitHub à chaque ouverture ; un cache court de requête est une optimisation possible, **hors périmètre** ici, à porter par `sync-manuelle`.
2. **Armement du `<turbo-frame>` par Stimulus** — le `loading="lazy"` est présent mais le `src` est armé au clic par le contrôleur Stimulus (écart de mécanisme résiduel signalé en review) ; à revoir si un chargement 100 % déclaratif est souhaité plus tard.
3. **Signalement d'erreur riche** — le garde-fou actuel est un message brut ; le diagnostic actionnable relève de `sync-manuelle` (C3.4).
4. **Non-vérifié en environnement réel** — rendu du board sur un vrai repo forge avec docs transversaux (`README.md` etc.) après correction du bloquant ; E2E jouables uniquement avec assets servis (pas via `php -S` router-only).

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Ouvrir un projet éligible affiche 4 colonnes ordonnées avec chaque story dans la colonne calculée par `004`.
- [x] Chaque carte affiche badge de track, identifiant `NNN-slug` et slug humanisé.
- [x] Le titre réel `# H1` apparaît en tête du document rendu dans le drawer (pas sur la carte).
- [x] Les stories « À vérifier » apparaissent dans un bandeau distinct sous les colonnes, absent si aucune concernée.
- [x] Une carte `r`/`t` n'apparaît jamais en colonne Cadrage (cohérent `004`).
- [x] Au sein d'une colonne, cartes triées par `NNN` décroissant.
- [x] Chaque colonne et le bandeau affichent leur compteur de cartes.
- [x] Cliquer une carte ouvre un drawer listant d'abord les documents, puis rend le markdown choisi, tableau visible en fond.
- [x] Rouvrir le tableau reflète le nouvel état (scan live), sans rafraîchissement explicite.
- [x] Un projet éligible sans story affiche un état vide explicite, sans erreur.
- [x] Un scan qui échoue affiche un garde-fou et ne casse pas la page.

## Leçons apprises

- **Le wiring `markdown_to_html` ne passe pas par `MarkdownInterface`** : `twig/extra-bundle` consomme le service `twig.markdown.default` (`LeagueMarkdown`), pas un alias sur `Twig\Extra\Markdown\MarkdownInterface`. Un plan qui prévoit un converter sûr doit cibler `twig.markdown.default` directement — l'alias est silencieusement ignoré, sans erreur au boot.
- **`strict_requirements` couple la génération d'URL au contenu scanné** : dès qu'un `data-`/`path()` génère une URL depuis un nom de fichier tiers, tout fichier hors du `requirements` de route casse le rendu. Filtrer en amont (ici `documentsFor`) sur le même motif que la route est indispensable — à anticiper au plan pour toute route paramétrée par des données externes.
- **Un E2E qui lit un repo distant a besoin d'un double déterministe dédié** : le plan n'avait pas prévu de socle de fake pour l'E2E. Mutualiser la source (dev décorateur + stub test = un seul `FakeRepositoryCatalog`) évite le drift entre environnements — à intégrer d'emblée dans les plans de features à dépendance réseau.
- **Convention de test à vérifier avant de nommer les fichiers au plan** : le plan a proposé `tests/Application/…` alors que le projet range ses `WebTestCase` sous `tests/Functional/`. Aligner sur l'arborescence existante plutôt que d'introduire un namespace non conventionnel.
