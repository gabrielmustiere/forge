# Plan technique — Enrichir chaque story de métadonnées lisibles par le Board

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/006-f-metadonnees-story/`
> **Amont** : `pitch.md`

## Approche retenue

Deux versants, un seul contrat. **Côté fichier** : chaque story porte un `metadata.json` (versionné) à la racine de son dossier, produit et maintenu par les skills du plugin forge via une **référence partagée** invoquée par chaque SKILL.md producteur. **Côté app** : le Board lit ce contenu, sans jamais l'écrire.

Le point dur est la lecture « instantanée sans N appels ». On l'adresse par une **nouvelle méthode d'interface** `readStoryMetadata(url, token, storyIds): array<storyId, ?string>` sur `RepositoryReaderInterface`, dont l'implémentation GitHub fait **une seule requête GraphQL** (`POST /graphql`, un alias `object(expression: "HEAD:docs/story/<id>/metadata.json")` par story) — nombre d'appels constant, indépendant du nombre de stories. Le reader reste pur transport (renvoie le JSON brut ou `null`) ; le **décodage + validation tolérante** vit dans un `StoryMetadataParser` côté app (retourne `null` si absent/malformé → dégradation). `ProjectBoardBuilder` enchaîne : `readStoryTree` (existant, 1 appel) → `readStoryMetadata` (nouveau, 1 appel) → hydratation des `StoryCard`. Le filtre par tag et le tri par `updated` sont **client-side** (Stimulus sur cartes déjà rendues), donc zéro round-trip et instantanés. Aucune entité Doctrine, aucune migration : la donnée vit dans les fichiers, fidèle au principe « lecture seule / état déduit ».

### Mécanismes mobilisés

- **`RepositoryReaderInterface` + `AutoconfigureTag`** : on étend l'interface existante (une méthode `readStoryMetadata`), toutes les implémentations taguées la fournissent — cohérent avec `readStoryTree`/`readFile`.
- **`symfony/http-client` (scoped client `github.client`)** : réutilisé tel quel pour le POST GraphQL (`/graphql` sur le même host `api.github.com`) — pas de nouveau client, token en `auth_bearer`.
- **`#[AsDecorator]` (dev) + double de test** : `DevFakeRepositoryReader` et `tests/Double/StubRepositoryReader` implémentent la nouvelle méthode (fake via `FakeRepositoryCatalog`) — réseau neutralisé en dev/test, patron inchangé.
- **Stimulus (client-side)** : nouveau `board_filter_controller` pour filtre tag + tri `updated`, opérant sur des cartes déjà rendues (data-attributes) — instantané, aucun round-trip, cohérent avec l'archi server-rendered actuelle (pas de Live Component).
- **Référence partagée de plugin** (patron `_detection.md`/`template.md`) : `plugins/forge/references/story-metadata.md` invoquée par chaque SKILL.md producteur — mécanisme de mutualisation déjà en place dans le plugin.

### Alternatives écartées

- **Boucle de `readFile` par story** : N appels REST séquentiels → chargement qui dérive avec le nombre de stories, refusé au pitch (« pas N appels »).
- **HttpClient multiplexé (fan-out `readFile` parallèle)** : wall-clock quasi constant mais N requêtes comptant contre le rate-limit ; viole la lettre de la contrainte. Conservé comme **repli documenté** : l'interface `readStoryMetadata` étant abstraite, basculer GraphQL → multiplexé ne touche que l'implémentation GitHub.
- **Cache court + lazy** : ne résout pas le cold-load et introduit un risque de staleness, en tension avec le principe de fidélité.
- **Frontmatter YAML dans `pitch.md`** : non track-agnostique (`pitch.md` absent des tracks `r`/`t`), impose un parsing markdown, et casserait l'isolation gratuite du mapping. `metadata.json` gagne.

## Modèle de données

**Aucun impact modèle Doctrine, aucune migration.** Les métadonnées vivent dans les fichiers du dépôt scanné, jamais en base. On introduit des **value objects immuables** côté app (namespace `App\Service\Board`) :

`src/Service/Board/StoryMetadata.php` — VO racine, produit par le parser :

| Champ        | Type PHP                          | Notes                                                        |
|--------------|-----------------------------------|--------------------------------------------------------------|
| `version`    | `int`                             | Version de schéma (1 au lancement).                          |
| `title`      | `string`                          | H1 réel de la story.                                         |
| `created`    | `\DateTimeImmutable`              | Date de création (figée).                                    |
| `updated`    | `\DateTimeImmutable`              | Date de dernière activité.                                   |
| `tags`       | `list<string>`                    | Étiquettes kebab-case, dédupliquées.                         |
| `changelog`  | `list<StoryChangelogEntry>`       | Timeline consolidée, ordre chronologique.                    |
| `delivery`   | `?StoryDelivery`                  | `null` tant que non livrée.                                  |

`src/Service/Board/StoryChangelogEntry.php` — `{ date: \DateTimeImmutable, type: string, description: string }`.
`src/Service/Board/StoryDelivery.php` — `{ release: ?string, commit: ?string }` (release nullable = tag en différé, règle métier 8).

**Contrat `metadata.json` v1** (schéma embarqué chez tous les utilisateurs forge — figé) :

```json
{
  "version": 1,
  "title": "Afficher le kanban d'un projet",
  "created": "2026-07-01",
  "updated": "2026-07-05",
  "tags": ["board", "kanban"],
  "changelog": [
    { "date": "2026-07-01", "type": "Création", "description": "Pitch initial." }
  ],
  "delivery": { "release": "v4.3.0", "commit": "b7964b4" }
}
```

Formats : dates `YYYY-MM-DD` (aligné sur les changelogs existants) ; `delivery` absent ou `{release:null,commit:null}` toléré.

## Périmètre

### Fichiers à créer

| Fichier                                                     | Rôle                                                                       |
|------------------------------------------------------------|----------------------------------------------------------------------------|
| `src/Service/Board/StoryMetadata.php`                      | VO immuable des métadonnées d'une story.                                    |
| `src/Service/Board/StoryChangelogEntry.php`                | VO d'une entrée de changelog (date, type, description).                     |
| `src/Service/Board/StoryDelivery.php`                      | VO livraison (release nullable, commit nullable).                           |
| `src/Service/Board/StoryMetadataParser.php`                | Décode le JSON brut → `?StoryMetadata`, validation tolérante (null si KO).  |
| `assets/controllers/board_filter_controller.js`           | Stimulus : filtre par tag + tri par `updated` + recalage des compteurs de colonne sur les cartes visibles, client-side. |
| `tests/Unit/Service/Board/StoryMetadataParserTest.php`     | Nominal, absent→null, malformé→null, version inconnue, delivery partielle.  |
| `tests/Unit/Service/Board/StoryMetadataTest.php`           | Accès aux champs + fallback de titre côté `StoryCard`.                      |
| `plugins/forge/references/story-metadata.md`               | Schéma v1 + procédure d'écriture/mise à jour, invoquée par les SKILL.md.    |
| `docs/story/001-f-login/metadata.json` … `005-…/metadata.json` | Backfill des 5 stories existantes (généré depuis H1 + changelogs actuels). |

### Fichiers à modifier

| Fichier                                                    | Modification                                                                        |
|-----------------------------------------------------------|--------------------------------------------------------------------------------------|
| `src/Service/Repository/RepositoryReaderInterface.php`    | Ajouter `readStoryMetadata(RepositoryUrl, string, array $storyIds): array`.          |
| `src/Service/Github/GitHubRepositoryReader.php`           | Implémenter `readStoryMetadata` via une requête GraphQL unique (alias par story).    |
| `src/Service/Github/DevFakeRepositoryReader.php`          | Déléguer/faker la nouvelle méthode.                                                  |
| `src/Service/Github/FakeRepositoryCatalog.php`            | Servir un `metadata.json` factice par story fixture.                                 |
| `tests/Double/StubRepositoryReader.php`                   | Implémenter la nouvelle méthode (données de test).                                   |
| `src/Service/Board/StoryCard.php`                         | Ajouter `?StoryMetadata $metadata` + `title()` = `metadata?.title ?? humanizedTitle`.|
| `src/Service/Board/ProjectBoardBuilder.php`               | Appeler `readStoryMetadata` (1 appel groupé) + hydrater chaque carte via le parser.  |
| `templates/project/_card.html.twig`                       | Titre réel (fallback slug), âge (`updated`), tags, badge livraison ; data-attrs filtre/tri.|
| `templates/project/_board.html.twig`                      | Barre d'outils filtre tag + toggle tri `updated` (branche le contrôleur Stimulus).   |
| `templates/project/_drawer.html.twig`                     | Afficher le changelog consolidé de la story dans le drawer.                          |
| `assets/controllers/story_drawer_controller.js`           | Passer/afficher le changelog consolidé (nouveau param Stimulus).                     |
| `tests/Unit/Service/Board/ProjectBoardBuilderTest.php`    | Cartes hydratées avec metadata + cas dégradé (metadata absent).                      |
| `tests/Unit/Service/Github/GitHubRepositoryReaderTest.php`| Cas `readStoryMetadata` (MockHttpClient sur réponse GraphQL).                        |
| `tests/Functional/Controller/ProjectBoardTest.php`        | Carte affiche titre réel + tags depuis metadata fixture.                             |
| `tests/e2e/project-board.spec.ts`                         | Filtre par tag + tri par activité (sélecteurs `data-test`).                          |
| `plugins/forge/skills/{feature-interview,feature-pitch,feature-plan,refactor-plan,tech-plan,feature-implem,refactor-implem,tech-implem,report,sync,adr,estimate,review}/SKILL.md` | Invoquer `references/story-metadata.md` au bon point (create → `created`+`title`+tags+1re entrée ; toute passe → `updated`+entrée). |
| `plugins/forge/skills/commit/SKILL.md`                    | Écrire `delivery.commit` (SHA de clôture).                                           |
| `plugins/forge/skills/release/SKILL.md`                   | Écrire `delivery.release` (tag de version).                                          |
| `plugins/forge/skills/sync/SKILL.md`                      | Cesser de produire les tables de changelog en pied de `pitch.md`/`plan.md`.          |
| `plugins/forge/.claude-plugin/plugin.json`, `.claude-plugin/marketplace.json` | Bump de version (alignés).                                          |
| `plugins/forge/SKILLS.md`, `CHANGELOG.md`                 | Documenter la convention metadata + entrée de changelog.                             |

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **API REST/GraphQL** : côté app, **consommation** GraphQL GitHub en lecture (pas d'exposition). Aucun endpoint app nouveau (le board reste rendu par `ProjectController::show`).
- **i18n** : quelques libellés UI FR (filtre, tri, « livré en… ») ; les données metadata ne sont pas traduites.
- **Permissions** : inchangé (accès local unique).
- **Emails / notifications** : non.
- **Migration de données** : aucune migration BDD. **Backfill fichier** : 5 `metadata.json` générés pour `001`→`005` (partie de l'implem, pas de SQL).
- **Comportement par défaut** : une story/un repo sans `metadata.json` s'affiche comme aujourd'hui (slug, pas de tags/dates) — dégradation gracieuse, zéro régression.

## Ordre d'exécution

1. [ ] **Contrat d'abord** : rédiger `plugins/forge/references/story-metadata.md` (schéma v1, formats, procédure d'écriture par étape). C'est la source des deux versants.
2. [ ] **VO + parser (app)** : `StoryMetadata`, `StoryChangelogEntry`, `StoryDelivery`, `StoryMetadataParser` (+ tests unit : nominal / absent / malformé / version inconnue / delivery partielle).
3. [ ] **Interface + lecture groupée** : ajouter `readStoryMetadata` à l'interface ; implémenter la requête GraphQL dans `GitHubRepositoryReader` (+ test MockHttpClient) ; câbler `DevFakeRepositoryReader`, `FakeRepositoryCatalog`, `StubRepositoryReader`.
4. [ ] **Hydratation** : `StoryCard` (`?StoryMetadata` + `title()` fallback) ; `ProjectBoardBuilder` appelle la lecture groupée et hydrate (+ adapter `ProjectBoardBuilderTest`, dont le cas dégradé).
5. [ ] **UI cartes/drawer** : `_card.html.twig` (titre réel, âge, tags, badge livraison, data-attrs), `_drawer.html.twig` + `story_drawer_controller.js` (changelog consolidé).
6. [ ] **Filtre & tri** : `board_filter_controller.js` + barre d'outils dans `_board.html.twig` (client-side, instantané).
7. [ ] **Backfill** : générer `metadata.json` pour `001`→`005` depuis leur H1 et leurs changelogs actuels.
8. [ ] **Versant skills** : éditer les SKILL.md producteurs pour invoquer la référence ; `commit`/`release` écrivent `delivery` ; `sync` cesse les pieds de changelog.
9. [ ] **Doc plugin** : bump `plugin.json` + `marketplace.json` (alignés), `SKILLS.md`, `CHANGELOG.md`.
10. [ ] **QA finale** : `make quality` (CS-Fixer + PHPStan level 9 + build) + `make phpunit` + `make playwright` + dogfooding d'un skill pour vérifier l'écriture réelle du `metadata.json`.

## Stratégie de test

| Code                                             | Type            | Ce qu'on vérifie                                                            |
|--------------------------------------------------|-----------------|-----------------------------------------------------------------------------|
| `StoryMetadataParser`                            | Unit            | JSON valide → VO ; absent → null ; malformé/invalide → null ; version inconnue tolérée ; `delivery` partielle. |
| `StoryCard::title()`                             | Unit            | Retourne `metadata.title` si présent, sinon le slug humanisé.               |
| `GitHubRepositoryReader::readStoryMetadata`      | Unit (MockHttpClient) | 1 requête GraphQL ; mapping storyId → JSON ; story sans fichier → null ; jamais d'appel réel. |
| `ProjectBoardBuilder`                            | Unit            | Cartes hydratées ; **une seule** lecture groupée ; cas dégradé (metadata absent) sans erreur. |
| `ProjectController::show` (board)                | Functional      | Carte rend le titre réel + tags depuis la fixture metadata.                 |
| Filtre tag + tri activité                        | E2E (Playwright)| Filtrer masque les cartes hors tag ; le tri réordonne dans chaque colonne.  |

**Hors scope tests pour cette story** :

- **Versant skills (SKILL.md)** : non testable en PHPUnit (prompts/doc) — validé par **dogfooding** (lancer un skill, vérifier le `metadata.json` produit) et par le backfill relu.
- Pas de test sur le rendu markdown du changelog au-delà de sa présence (déjà couvert par le pipeline de rendu du drawer existant).

## Risques et mitigations

- **Surface GraphQL nouvelle** : le reader devient bi-protocole (REST + GraphQL). Mitigation : GraphQL isolé dans la seule méthode `readStoryMetadata`, mêmes exceptions métier (`RepositoryUnreachable`/`AccessDenied`) que le REST, repli multiplexé possible sans toucher l'interface. **Attention détection** : GitHub GraphQL signale le rate-limit par un **HTTP 200 + `errors[].type = RATE_LIMITED`** (pas un 403 comme le REST) — traité par `guardGraphqlRateLimit()`, les autres erreurs partielles (`NOT_FOUND`) restant tolérées (règle 9). Faire cohabiter les deux protocoles pousse la classe vers la limite de complexité cognitive PHPStan level 9 : prévoir l'extraction de guards/helpers (rate-limit via `array_column`/`in_array`, aplatissement des ternaires de mapping).
- **Limite de taille de requête GraphQL** : un alias par story ; à quelques centaines de stories la requête gonfle. Mitigation : cible « quelques dizaines » (vision) ; si dépassement, découper en lots — noté, non implémenté au MVP.
- **Discipline `updated` sur ~13 skills** : la fidélité repose sur le fait que chaque skill producteur invoque la référence. Mitigation : centraliser la procédure dans `story-metadata.md` (un seul point de vérité), et checklist de relecture ; risque résiduel de prompt non suivi → assumé (dégradation ≠ mensonge : une date figée vaut mieux qu'une fausse).
- **JSON tiers hostile** : `metadata.json` vient d'un repo tiers. Mitigation : parser strictement tolérant (jamais d'exception remontée au template), types validés, `null` sur le moindre doute (principe de fidélité).
- **Isolation mapping** : vérifier explicitement (test) que la présence de `metadata.json` ne change aucune colonne et n'apparaît pas dans la liste de documents du drawer (`.json` déjà hors du filtre `.md`, mais à verrouiller par test de non-régression).

## Questions ouvertes

- **Repli GraphQL → multiplexé** : si l'implémentation GraphQL s'avère lourde à l'implem, basculer sur le fan-out `readFile` parallèle (change uniquement `GitHubRepositoryReader`). → à trancher à l'implem selon coût réel.
- **Affichage de l'âge** : date brute `updated` vs libellé relatif (« il y a 3 j »). Un petit filtre Twig suffit ; pas de dépendance nouvelle. → à trancher à l'implem (cosmétique).
- **Backfill — provenance des tags des 5 stories** : inférés puis validés manuellement à la génération (pas de skill rejoué). → tranché : génération assistée relue.
- **`delivery.release` en différé** : `release` réédite le `metadata.json` de la story livrée pour compléter le tag après coup. → mécanique à préciser dans `story-metadata.md`.

