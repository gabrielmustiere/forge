# Plan technique — Aligner les colonnes du board sur le cycle de vie réel d'une story forge

> Pitch : `docs/story/007-f-refonte-colonnes-cycle-de-vie/pitch.md`
> Stack : symfony

## Approche retenue

Le board déduit déjà l'étape d'une story via une chaîne pure et sans état : `StoryFolder.files()` → `StoryStageMapper` (table de précédence) → `PipelineStage` → `Board.COLUMNS` (ordre d'affichage) → rendu Twig. La feature ne change **pas** cette architecture : elle **réétalonne le vocabulaire de l'enum** et **étend la table de précédence** d'un cran vers l'amont (`brief.md`). Concrètement : on ajoute une 5ᵉ colonne « Idée » alimentée par `brief.md`, on renomme les 4 cases existantes en libellés de cycle de vie (Besoin/Cadré/Implémenté/Livré), et on répercute mécaniquement sur les points qui lisent l'enum (Board, template, couleurs, données fake, tests).

Aucune persistance n'est en jeu : l'état d'une story n'est **jamais stocké**, il est recalculé à chaque scan. Le changement est donc purement en mémoire + présentation — **zéro migration Doctrine**. Le point unique de vérité du mapping (`StoryStageMapper::PRECEDENCE`) reste le seul endroit à toucher pour la logique métier, conformément à la vision (mapping centralisé, facile à faire évoluer).

**Alternatives écartées** :

- **Ajouter `estimate.md` comme déclencheur / 6ᵉ colonne** : écarté au pitch (optionnel) — une story chiffrée reste positionnée par `plan.md` en « Cadré ».
- **Introduire un état « en cours de codage » distinct** : aucun fichier forge ne marque « je code maintenant » ; créer un état artificiel violerait le principe « état déduit, jamais saisi ».
- **Conserver des `value` d'enum nommées par document (`brief/pitch/plan/review/report`)** : rejeté — le board parle « cycle de vie », pas « livrables » ; les `value` `idee/besoin/cadre/implemente/livre` sont plus lisibles dans le HTML (`data-stage`) et il n'y a aucune contrainte de compat (pas de persistance).
- **Colonne « Idée » commune avec « À vérifier »** : rejeté — une idée dégrossie par interview est une vraie étape du cycle, pas une anomalie ; les fusionner reproduirait la friction du pitch.

## Entités et modèle de données

Aucun impact modèle. La feature ne touche aucune entité Doctrine ni aucune table : l'étape d'une story est un `PipelineStage` calculé à la volée, jamais persisté.

## Mécanismes framework mobilisés

- **Backed enum `string` (`PipelineStage`)** : porte le vocabulaire ordonné + `label()` + `isOnPipeline()`. Mécanisme déjà en place, on l'étend d'un case et on renomme — pas de nouveau pattern introduit.
- **Fonction pure `StoryStageMapper`** : table `PRECEDENCE` (const de classe) parcourue du plus avancé au moins avancé ; aucun effet de bord, testable en isolation. On ajoute une entrée.
- **Value object immuable `Board`** : `COLUMNS` (const) définit l'ordre des colonnes ; `cardsFor`/`countFor` indexent sur `PipelineStage->value`. On passe la liste de 4 à 5.
- **Maps Twig locales (`stageAccent`/`stageBar`)** dans `_board.html.twig` : associent `stage.value` → classes de couleur Tailwind. On remappe sur les nouvelles `value` et on ajoute « idee ».
- **Tokens CSS `--color-st-*`** (design system Nova) : déjà nommés par document. On réserve `st-brief` (gris) à « Idée » et on donne à « À vérifier » un ton d'attention distinct.

## Fichiers à créer

Aucun fichier de production à créer — la feature réétalonne l'existant. Les tests sont adaptés en place (cf. §Fichiers à modifier). Une éventuelle création de fixture E2E se fait dans le catalogue fake existant, pas dans un nouveau fichier.

## Fichiers à modifier

| Fichier | Modification |
|---|---|
| `src/Enum/Type/PipelineStage.php` | Ajouter `case Idee = 'idee'` ; renommer `Cadrage→Besoin='besoin'`, `Planifie→Cadre='cadre'`, `Review→Implemente='implemente'` ; **conserver** `Livre='livre'` et `AVerifier='a_verifier'`. Mettre à jour `label()` (Idée/Besoin/Cadré/Implémenté/Livré), `isOnPipeline()` (5 cases true) et le docblock (« quatre »→« cinq », nouvelle échelle). |
| `src/Service/Mapping/StoryStageMapper.php` | Étendre `PRECEDENCE` : `report→Livre`, `review→Implemente`, `plan→Cadre`, `pitch→Besoin`, **`brief.md→Idee`** (nouvelle entrée, plus bas de l'échelle). Adapter le docblock (exemple de précédence). |
| `src/Service/Board/Board.php` | `COLUMNS` : 5 entrées ordonnées `Idee, Besoin, Cadre, Implemente, Livre`. Docblocks « quatre colonnes »→« cinq colonnes ». |
| `templates/project/_board.html.twig` | Reconstruire `stageAccent`/`stageBar` sur les 5 nouvelles `value` (+ `idee`) ; recolorer le bandeau « À vérifier » (dot `bg-st-brief`→ton d'attention). |
| `assets/styles/app.css` | Attribuer à « À vérifier » un token distinct de `st-brief` (rose/danger discret — option retenue, cf. Questions ouvertes) ; `st-brief` (gris) reste pour « Idée ». |
| `src/Service/Github/FakeRepositoryCatalog.php` | Ajouter à `boardTree()` une story `brief.md` seule (ex. `new StoryFolder('012-f-idee', ['brief.md'])`) + une entrée `FAKE_METADATA` correspondante, pour peupler « Idée » en dev/E2E. |
| `tests/Unit/Enum/PipelineStageTest.php` | Data providers `label()`/`isOnPipeline()` : 5 cases pipeline + `AVerifier` avec les nouveaux libellés. |
| `tests/Unit/Service/Mapping/StoryStageMapperTest.php` | Réécrire les cas : `brief seul → Idee` (nouveau comportement, plus « À vérifier »), `pitch→Besoin`, `plan→Cadre`, `review→Implemente`, `report→Livre`, `plan+estimate→Cadre`, précédence, top-level exact. |
| `tests/Unit/Service/Board/ProjectBoardBuilderTest.php` | Adapter les `countFor`/`cardsFor` aux nouveaux cases ; renommer `testRefactoStoryNeverLandsInCadrage` → refacto `plan.md` entre en **Cadré**, jamais en Idée/Besoin. |
| `tests/Functional/Controller/ProjectBoardTest.php` | `assertCount(4→5)` colonnes ; `data-stage` `cadrage/planifie/review`→`besoin/cadre/implemente` ; compteurs recalés ; `testRefactoCardIsNeverInCadrageColumn` → « never in Besoin » et présent en « implemente ». Ajouter l'assertion « story `brief.md` → colonne `idee` ». |
| `tests/e2e/project-board.spec.ts` | `toHaveCount(4→5)` ; labels/compteurs ; conserver `data-stage="livre"` ; ajouter une assertion sur la colonne « Idée » peuplée. Le test filtre/tri reste valide (sélecteurs inchangés). |
| `tests/Unit/Service/Board/StoryCardTest.php` | Fixtures `PipelineStage::Cadrage` → `Besoin` : le VO `StoryCard` figeait l'ancien case, le renommage cassait sa construction. **Ajouté au relevé post-implémentation** — non recensé initialement, débusqué par le grep de contrôle (cf. §Risques). |

## Impacts transverses

- **Multi-tenant** : non — outil mono-utilisateur.
- **Multi-thème** : non.
- **API REST/GraphQL** : non.
- **i18n** : non — libellés FR en dur dans `PipelineStage::label()`, comme l'existant.
- **Permissions** : inchangé.
- **Emails / notifications** : non.
- **Migration de données** : **aucune** — pas de schéma, pas de backfill. Les `value` d'enum ne sont ni stockées ni sérialisées durablement (seulement dans le HTML rendu à la volée).
- **Comportement par défaut** : le nouveau découpage s'applique immédiatement à tous (un seul utilisateur), sans feature flag. Effet observable : les stories `brief.md`-seul migrent de « À vérifier » vers « Idée ».

## Ordre d'implémentation

1. [ ] `PipelineStage` — ajouter `Idee`, renommer cases/values/labels, MAJ `isOnPipeline()` + docblock.
2. [ ] `StoryStageMapper::PRECEDENCE` — ajouter `brief.md → Idee`, réaligner sur les nouveaux cases.
3. [ ] `Board::COLUMNS` — passer à 5 étapes + docblocks.
4. [ ] `_board.html.twig` — remapper `stageAccent`/`stageBar` (+ `idee`) et recolorer « À vérifier ».
5. [ ] `assets/styles/app.css` — token d'attention pour « À vérifier » ; `symfony console tailwind:build`.
6. [ ] `FakeRepositoryCatalog` — ajouter la story `brief.md` seule + metadata fake.
7. [ ] Tests unit — `PipelineStageTest`, `StoryStageMapperTest`, `ProjectBoardBuilderTest`.
8. [ ] Tests functional — `ProjectBoardTest` (5 colonnes, `data-stage`, compteurs, refacto en Cadré, brief en Idée).
9. [ ] Tests E2E — `project-board.spec.ts` (5 colonnes, Idée peuplée, filtre/tri OK).
10. [ ] QA finale — `make quality` (CS-Fixer + PHPStan level 9 + build) puis `make phpunit` + `make playwright`.

## Stratégie de test

| Code | Type | Ce qu'on vérifie |
|---|---|---|
| `StoryStageMapper` | Unit | `brief.md` seul → `Idee` (nouveau) ; `pitch→Besoin`, `plan→Cadre`, `review→Implemente`, `report→Livre` ; « plus avancé gagne » ; `estimate.md` ignoré ; match top-level exact ; dossier sans fichier reconnu → `AVerifier`. |
| `PipelineStage` | Unit | `label()` des 6 cases ; `isOnPipeline()` true pour les 5 étapes, false pour `AVerifier`. |
| `ProjectBoardBuilder` | Unit | Répartition sur les 5 colonnes depuis un `StoryTree` ; refacto `plan.md` → `Cadre` ; tri intra-colonne par numéro décroissant conservé. |
| `ProjectBoardController` (rendu) | Functional | 5 colonnes ordonnées, `data-stage` corrects, compteurs par colonne, bandeau « À vérifier », story `brief.md` visible en colonne `idee`. |
| Board (parcours) | E2E | 5 colonnes affichées, colonne « Idée » peuplée, filtre par tag + tri activité toujours fonctionnels après le repartitionnement. |

**Hors scope tests pour cette story** :

- Pas de nouveau test pour le filtre/tri (`board_filter_controller.js`) — inchangé, ses sélecteurs (`data-stage`, `column-count`) restent valides ; la spec E2E existante le recouvre.
- Pas de test de couleur/CSS — vérification visuelle manuelle au navigateur (rendu Nova).

## Risques et points d'attention

- **Oubli d'un point de lecture de l'enum** → colonne muette ou clé Twig absente. Mitigation : la recherche exhaustive est faite (Board, template, FakeRepositoryCatalog, 4 fichiers de test) ; un `grep` final sur `Cadrage|Planifie|::Review|'cadrage'|'planifie'` doit ne plus rien renvoyer hors historique.
- **Collision de couleur `st-brief`** entre « Idée » et « À vérifier » → deux voies grises indistinctes. Mitigation : re-toner « À vérifier » (option retenue) ; contrôle visuel au navigateur.
- **E2E « Idée » non vérifiable** faute de story `brief.md` dans le catalogue fake → régression silencieuse possible. Mitigation : l'ajout de la story `012-f-idee` (étape 6) est un prérequis de l'assertion E2E (étape 9).
- **Renommage des `value`** casse les sélecteurs `data-stage` des tests functional/E2E. Mitigation : `Livre='livre'` conservé ; les autres sélecteurs sont mis à jour dans le même diff (étapes 8–9).
- **Vocabulaire « Cadré » vs « cadrage » forge** : « Cadré » (plan.md) réemploie un mot que forge associe au pitch. Risque de confusion documentaire uniquement, aucun impact code ; acté au pitch.

## Questions ouvertes

- ~~**Couleur de « À vérifier »**~~ → **tranché à l'implem** : token dédié `--color-st-flag: #f43f5e` (rose), distinct de `st-brief` (gris) réservé à « Idée ». Option par défaut du plan retenue ; validée au navigateur sur données réelles.
- ~~**Identifiant de la story fake « Idée »**~~ → **réalisé** : story `brief.md` seule ajoutée à `FakeRepositoryCatalog::boardTree()` (+ metadata fake), sans collision d'id.

---

## Changelog

| Date | Type | Description |
|------|------|-------------|
| 2026-07-05 | Sync post-implémentation | §Fichiers à modifier : ajout de `StoryCardTest.php` (fixture figeant l'ancien case, oubli du relevé initial débusqué par le grep). §Questions ouvertes : couleur « À vérifier » tranchée (`--color-st-flag` rose #f43f5e) et story fake « Idée » réalisée — les deux questions closes par la livraison. Cf. `report.md`. |
