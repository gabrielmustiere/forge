# Report — Enrichir chaque story de métadonnées lisibles par le Board

> Pitch : `docs/story/006-f-metadonnees-story/pitch.md`
> Plan : `docs/story/006-f-metadonnees-story/plan.md`
> Date d'implémentation : 2026-07-05
> Commits liés : working tree non commité au moment du report
> Référence review : `review.md`

## Résumé

Implémentation ~98 % conforme au plan : les deux versants (app Symfony + skills plugin) sont livrés tels que conçus (contrat `metadata.json` v1, VO `StoryMetadata`/`StoryChangelogEntry`/`StoryDelivery` + `StoryMetadataParser`, lecture groupée GraphQL sur `readStoryMetadata`, hydratation `StoryCard`/`ProjectBoardBuilder`, UI cartes/drawer/filtre-tri, backfill 001→005, référence partagée + édition des SKILL.md producteurs). 14/14 critères d'acceptation cochés. Review **READY TO COMMIT** : 0 bloquant, 1 important et 3 mineurs tous corrigés. Trois écarts structurants, tous des enrichissements en cours d'exécution : détection du rate-limit GraphQL, recalage des compteurs de colonne sur le filtre, simplification imposée par la complexité cognitive PHPStan. Périmètre : ~1825 insertions / 71 suppressions sur 53 fichiers.

## Ce qui a été implémenté

### Fichiers créés

| Fichier                                                     | Rôle                                                                      | Prévu dans le plan |
|-------------------------------------------------------------|--------------------------------------------------------------------------|--------------------|
| `src/Service/Board/StoryMetadata.php`                       | VO immuable des métadonnées d'une story.                                  | Oui                |
| `src/Service/Board/StoryChangelogEntry.php`                 | VO d'une entrée de changelog (date, type, description).                   | Oui                |
| `src/Service/Board/StoryDelivery.php`                       | VO livraison (release nullable, commit nullable).                        | Oui                |
| `src/Service/Board/StoryMetadataParser.php`                 | Décode le JSON brut → `?StoryMetadata`, validation tolérante.            | Oui                |
| `assets/controllers/board_filter_controller.js`            | Stimulus : filtre par tag + tri `updated`, client-side.                  | Oui                |
| `tests/Unit/Service/Board/StoryMetadataParserTest.php`      | Nominal / absent / malformé / version inconnue / delivery partielle.     | Oui                |
| `tests/Unit/Service/Board/StoryMetadataTest.php`            | Accès aux champs du VO.                                                   | Oui                |
| `tests/Unit/Service/Board/StoryCardTest.php`                | Fallback de titre `StoryCard::title()` (slug humanisé sans metadata).    | Non (ajout — cf. §Ajouts non prévus) |
| `plugins/forge/references/story-metadata.md`               | Schéma v1 + procédure d'écriture, invoquée par les SKILL.md.             | Oui                |
| `docs/story/001-f-login/metadata.json` … `005-…/metadata.json` | Backfill des 5 stories existantes.                                    | Oui                |
| `docs/story/006-f-metadonnees-story/metadata.json`         | Métadonnées de la story elle-même.                                       | Oui                |

### Fichiers modifiés

| Fichier                                                    | Modification                                                                       | Prévu dans le plan |
|------------------------------------------------------------|------------------------------------------------------------------------------------|--------------------|
| `src/Service/Repository/RepositoryReaderInterface.php`    | Ajout de `readStoryMetadata(RepositoryUrl, string, array): array`.                 | Oui                |
| `src/Service/Github/GitHubRepositoryReader.php`           | Implémentation GraphQL unique + `guardGraphqlRateLimit()` + simplification.        | Écart volontaire (cf. §) |
| `src/Service/Github/DevFakeRepositoryReader.php`          | Fake de la nouvelle méthode.                                                       | Oui                |
| `src/Service/Github/FakeRepositoryCatalog.php`            | Sert un `metadata.json` factice par story fixture.                                 | Oui                |
| `tests/Double/StubRepositoryReader.php`                   | Implémente la nouvelle méthode (données de test).                                  | Oui                |
| `src/Service/Board/StoryCard.php`                         | `?StoryMetadata` + `title()` = `metadata?.title ?? humanizedTitle`.               | Oui                |
| `src/Service/Board/ProjectBoardBuilder.php`               | Appel `readStoryMetadata` (1 appel groupé) + hydratation via le parser.           | Oui                |
| `templates/project/_card.html.twig`                       | Titre réel, âge, tags, badge livraison, data-attrs filtre/tri.                    | Oui                |
| `templates/project/_board.html.twig`                      | Barre d'outils filtre tag + toggle tri `updated`.                                 | Oui                |
| `templates/project/_drawer.html.twig`                     | Changelog consolidé dans le drawer.                                               | Oui                |
| `assets/controllers/story_drawer_controller.js`          | Passage/affichage du changelog consolidé.                                          | Oui                |
| `tests/Unit/Service/Board/ProjectBoardBuilderTest.php`    | Cartes hydratées + cas dégradé (metadata absent).                                  | Oui                |
| `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php`| `readStoryMetadata` (MockHttpClient) + rate-limit + erreur partielle tolérée.      | Oui                |
| `tests/Functional/Controller/ProjectBoardTest.php`        | Carte rend le titre réel + tags depuis metadata fixture.                           | Oui                |
| `tests/e2e/project-board.spec.ts`                         | Filtre par tag + tri par activité + recalage des compteurs.                        | Oui                |
| `plugins/forge/skills/{feature-interview,feature-pitch,feature-plan,refactor-plan,tech-plan,feature-implem,refactor-implem,tech-implem,report,sync,adr,estimate,review}/SKILL.md` | Invocation de `references/story-metadata.md`. | Oui |
| `plugins/forge/skills/commit/SKILL.md`                    | Écrit `delivery.commit`.                                                           | Oui                |
| `plugins/forge/skills/release/SKILL.md`                   | Écrit `delivery.release`.                                                          | Oui                |
| `plugins/forge/skills/sync/SKILL.md`                      | Cesse les tables de changelog ; append au `metadata.json`.                        | Oui                |
| `plugins/forge/agents/report-and-sync.md`                | Alignement sur la convention metadata.                                             | Oui                |
| `plugins/forge/.claude-plugin/plugin.json`, `.claude-plugin/marketplace.json` | Bump de version (alignés).                                    | Oui                |
| `plugins/forge/SKILLS.md`, `CHANGELOG.md`                | Documentation de la convention metadata + entrée de changelog.                     | Oui                |

## Écarts avec le plan

### Écarts volontaires

| Prévu                                                                                  | Réalisé                                                                                             | Raison                                                                                                                                                                             |
|----------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Plan §Risques : « mêmes exceptions métier (`RepositoryUnreachable`/`AccessDenied`) que le REST », sans détailler la détection GraphQL. | `guardGraphqlRateLimit()` détecte le HTTP 200 + `errors[].type = RATE_LIMITED` et lève `RepositoryUnreachableException`. | GitHub GraphQL signale le quota par un **200 + erreur typée** (pas un 403 comme le REST) — asymétrie non anticipée au plan. Aligné sur le versant REST ; erreurs partielles (`NOT_FOUND`) restent tolérées (règle 9). Suite review mineure [ROBUSTESSE]. |
| Plan §Fichiers : `GitHubRepositoryReader` implémente `readStoryMetadata` (mapping des alias GraphQL). | Reader **bi-protocole REST + GraphQL** avec branchement simplifié (rate-limit via `array_column`/`in_array`, aplatissement des ternaires de mapping). | Contrainte PHPStan level 9 : la classe passe sous la limite de complexité cognitive (< 40) une fois les deux protocoles cohabitant. Aucune API publique modifiée. |

### Non implémenté

| Élément prévu                               | Raison                                   | Action requise                                               |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| Aucun                                       | —                                        | —                                                            |

### Ajouts non prévus

| Élément ajouté                                                                                      | Raison                                                                                                                                                       |
|----------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `board_filter_controller.js#updateCounts()` — recale les compteurs `column-count`/`banner-count` sur les cartes visibles après filtrage (retour au total serveur quand le filtre est retiré). | Sans recalage, les compteurs mentaient après filtrage (issue review [UX] importante). Règle 11 préservée : l'état déduit des colonnes n'est pas modifié, seul l'affichage change. Couvert par l'E2E (`livreCount` : 2 → 1 → 2). |
| `tests/Unit/Service/Board/StoryCardTest.php` — test dédié séparé pour `StoryCard::title()`.        | Le plan logeait ce cas dans `StoryMetadataTest` ; séparé pour clarté des responsabilités de test. Cosmétique.                                               |
| Retrait des stubs de table de changelog dans les `pitch.md`/`plan.md` de la story 006 elle-même (remplacés par un commentaire renvoyant à `metadata.json`). | Cohérence avec la règle métier 7 introduite par cette story : la timeline vit uniquement dans `metadata.json`. La story applique sa propre convention (issue review mineure [DOC]). |

## Tests

| Code                                              | Type prévu             | Type réalisé                                                     | Statut                    |
|---------------------------------------------------|------------------------|-----------------------------------------------------------------|---------------------------|
| `StoryMetadataParser`                             | Unit (5 cas)           | Unit (`StoryMetadataParserTest`, cas exhaustifs)                | Fait — couverture étendue |
| `StoryCard::title()`                              | Unit (dans MetadataTest) | Unit dédié (`StoryCardTest`) + `StoryMetadataTest`            | Fait — couverture étendue |
| `GitHubRepositoryReader::readStoryMetadata`       | Unit (MockHttpClient)  | Unit : 1 requête, mapping, absent→null, **rate-limit→unreachable, erreur partielle tolérée** | Fait — couverture étendue |
| `ProjectBoardBuilder`                             | Unit                   | Unit : hydratation + une seule lecture groupée + cas dégradé   | Fait                      |
| `ProjectController::show` (board)                 | Functional             | Functional : titre réel + tags depuis fixture metadata          | Fait                      |
| Filtre tag + tri activité                         | E2E (Playwright)       | E2E : filtre masque hors tag, tri réordonne, **compteurs recalés** | Fait — couverture étendue |
| Versant skills (SKILL.md)                         | Hors scope (dogfooding)| Non testé en PHPUnit — à valider par dogfooding                 | Conforme (hors scope assumé) |

QA finale verte : PHPStan level 9 OK, 158 tests PHPUnit OK, 13 E2E Playwright OK.

## Dette technique identifiée

Issus de la review : aucun mineur résiduel — les 3 mineurs et l'important sont **corrigés** (verdict READY TO COMMIT).

Au-delà de la review :

1. **Requête GraphQL réelle validée uniquement en `MockHttpClient`** — la structure `object(expression:) { ... on Blob { text } }` n'est pas éprouvée sur un vrai dépôt. **Vérifier par dogfooding** du board de référence (token PAT) que la réponse est bien mappée. Cf. review §Hors review.
2. **Écriture du `metadata.json` par les skills non testable en PHPUnit** — valider par dogfooding d'un skill producteur (`feature-pitch`/`sync`) que `created`/`updated`/`changelog` sont bien écrits/maintenus.
3. **Limite de taille de requête GraphQL** — un alias par story ; à quelques centaines de stories la requête gonfle. Découpage en lots noté au plan (§Risques), **non implémenté au MVP** (cible « quelques dizaines »).
4. **Discipline `updated` sur ~13 skills** — la fidélité repose sur l'invocation de la référence par chaque skill producteur. Risque résiduel de prompt non suivi, assumé (dégradation ≠ mensonge). Point d'attention pour toute future skill écrivant dans un dossier de story.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Un fichier de métadonnées est produit/maintenu dans le dossier d'une story par les skills concernés, avec `title`, `created`, `updated`, `tags`, `changelog`, `delivery`.
- [x] `feature-pitch` (et les skills de création de `r`/`t`) écrit `created` + `title` + tags validés + première entrée de changelog à la création.
- [x] Chaque skill listé en règle 5 rebooge `updated` et append une entrée de changelog quand il écrit dans le dossier.
- [x] `commit` écrit le SHA de clôture et `release` écrit le tag dans `delivery` ; une story livrée non taguée a un commit sans release, sans erreur.
- [x] Les tables de changelog en pied de `pitch.md`/`plan.md` ne sont plus produites ; la timeline vit uniquement dans le metadata.
- [x] Le Board lit le metadata de toutes les stories en un seul appel groupé (vérifié : `getRequestsCount() === 1`).
- [x] La carte affiche le vrai titre (`title`) ; à défaut de metadata, elle retombe sur le slug humanisé.
- [x] La carte affiche l'âge / dernière activité, les tags, et un badge de livraison si `delivery` présent.
- [x] Le drawer expose le changelog consolidé de la story.
- [x] Le board permet de filtrer par tag et de trier par date de mise à jour.
- [x] Une story sans metadata, ou avec un metadata invalide, s'affiche sans erreur (dégradation gracieuse).
- [x] `StoryStageMapper` ignore le fichier metadata : sa présence ne modifie aucune colonne déduite.
- [x] Les 5 stories existantes (`001`→`005`) reçoivent un metadata rétroactif (backfill).
- [x] La convention est documentée pour tous les utilisateurs forge (`references/story-metadata.md`).

## Leçons apprises

- **GraphQL GitHub signale le rate-limit par un HTTP 200 + `errors[].type = RATE_LIMITED`**, pas un 403 comme le REST. Quand un plan introduit un second protocole en réutilisant « les mêmes exceptions métier », détailler explicitement la **détection** de ces exceptions côté nouveau protocole — les codes d'erreur ne se transposent pas.
- **Faire cohabiter REST + GraphQL dans une même classe pousse la complexité cognitive vers la limite PHPStan level 9.** Anticiper au plan l'extraction de helpers (guards, mapping) plutôt que de la découvrir en fin d'implem — un reader bi-protocole n'est pas « la même méthode en plus ».
- **Un filtre client-side qui « n'affecte que l'affichage » (règle 11) doit aussi recaler les compteurs dérivés** (colonnes, bannière), sinon l'UI ment sans violer la règle sur le papier. À intégrer au plan UI dès qu'un filtre coexiste avec des compteurs.
- **`metadata.json` track-agnostique confirmé** : la story a pu retirer ses propres stubs de changelog de `pitch.md`/`plan.md` et migrer sa timeline dans `metadata.json` sans casser le mapping — le choix contre le frontmatter YAML paye dès la première application réflexive de la convention.
