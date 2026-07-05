# Report — Aligner les colonnes du board sur le cycle de vie réel d'une story forge

> Pitch : `docs/story/007-f-refonte-colonnes-cycle-de-vie/pitch.md`
> Plan : `docs/story/007-f-refonte-colonnes-cycle-de-vie/plan.md`
> Date d'implémentation : 2026-07-05
> Commits liés : `317a029` (feat(board): refondre les colonnes et le filtre du kanban)
> Référence review : `review.md`

## Résumé

Conformité au plan estimée à ~95 % : les 5 colonnes de cycle de vie (Idée → Besoin → Cadré → Implémenté → Livré) et l'entrée `brief.md → Idee` sont livrées exactement comme conçu, point de vérité unique (`StoryStageMapper::PRECEDENCE`) respecté. Un seul écart structurant : un fichier de test non recensé au plan (`StoryCardTest.php`) a dû être touché, débusqué par le grep de contrôle prévu au plan. Les 8/8 critères d'acceptation sont cochés. Review sans bloquant : l'unique important est une consigne de commit (scope), le mineur A11Y est corrigé, le mineur DOC retiré après vérification. Périmètre 007 : ~15 fichiers ; le commit `317a029` agrège en plus deux features de tours antérieurs (popover de filtre, rétrécissement des colonnes vides) hors périmètre du plan.

## Ce qui a été implémenté

### Fichiers créés

| Fichier | Rôle | Prévu dans le plan |
|---------|------|--------------------|
| _(aucun)_ | La feature réétalonne l'existant, aucun fichier de production ni de test créé | Oui (plan : « Aucun fichier de production à créer ») |

### Fichiers modifiés

| Fichier | Modification | Prévu dans le plan |
|---------|--------------|--------------------|
| `src/Enum/Type/PipelineStage.php` | Ajout `case Idee='idee'` ; renommage `Cadrage→Besoin`, `Planifie→Cadre`, `Review→Implemente` ; `Livre`/`AVerifier` conservés ; `label()`, `isOnPipeline()` (5 true) et docblock (« quatre »→« cinq ») MAJ | Oui |
| `src/Service/Mapping/StoryStageMapper.php` | Table `PRECEDENCE` étendue avec `brief.md → Idee` en bas d'échelle, réalignée sur les nouveaux cases | Oui |
| `src/Service/Board/Board.php` | `COLUMNS` passé à 5 étapes ordonnées ; docblocks « quatre »→« cinq » | Oui |
| `templates/project/_board.html.twig` | `stageAccent`/`stageBar` remappés sur les 5 nouvelles `value` (+ `idee`) ; bandeau « À vérifier » recoloré sur `st-flag` | Oui (part 007 ; le fichier porte aussi du popover/rétrécissement hors scope) |
| `assets/styles/app.css` | Nouveau token `--color-st-flag: #f43f5e` (rose) pour « À vérifier » ; `st-brief` (gris) réservé à « Idée » ; commentaires de tokens annotés par colonne | Oui |
| `src/Service/Github/FakeRepositoryCatalog.php` | Ajout d'une story `brief.md` seule + metadata fake pour peupler « Idée » en dev/E2E | Oui |
| `tests/Unit/Enum/PipelineStageTest.php` | Data providers `label()`/`isOnPipeline()` sur les 6 cases | Oui |
| `tests/Unit/Service/Mapping/StoryStageMapperTest.php` | Cas réécrits : `brief→Idee`, `pitch→Besoin`, `plan→Cadre`, `review→Implemente`, `report→Livre`, `plan+estimate→Cadre`, précédence, top-level | Oui |
| `tests/Unit/Service/Board/ProjectBoardBuilderTest.php` | `countFor`/`cardsFor` adaptés ; refacto `plan.md` → **Cadré**, jamais Idée/Besoin | Oui |
| `tests/Functional/Controller/ProjectBoardTest.php` | 5 colonnes, `data-stage` recalés, compteurs, refacto en Cadré, `brief.md → idee` | Oui |
| `tests/e2e/project-board.spec.ts` | 5 colonnes, colonne « Idée » peuplée, filtre/tri OK | Oui |
| `tests/Unit/Service/Board/StoryCardTest.php` | Fixtures `PipelineStage::Cadrage` → `Besoin` (figeaient l'ancien case) | **Non (ajout — cf. §Ajouts non prévus)** |
| `assets/controllers/board_filter_controller.js` | Popover de filtre recherchable + état ARIA | Hors périmètre 007 (tour antérieur — cf. §Écarts volontaires) |

## Écarts avec le plan

### Écarts volontaires

| Prévu | Réalisé | Raison |
|-------|---------|--------|
| Question ouverte plan : couleur de « À vérifier » « tranchée par défaut » (rose/danger discret), sans nommer le token | Token dédié nommé `--color-st-flag: #f43f5e` (rose), `st-brief` gris réservé à « Idée » | Résolution de la question ouverte à l'implem, option par défaut du plan retenue ; validée au navigateur sur données réelles (story `029-f-documents-zone` passée de « À vérifier » à « Idée ») |
| Question ouverte pitch : teinte de la colonne « Idée » dans Nova, « à trancher au plan » | `st-brief` (gris `#a1a1aa`) conservé pour « Idée » | Décision déjà prise au plan (st-brief gris) et appliquée telle quelle |
| Périmètre 007 seul dans le plan | Commit `317a029` agrège 007 + popover de filtre + rétrécissement des colonnes vides (tours antérieurs) | Travail non commité de tours précédents, entrelacé au niveau ligne dans `_board.html.twig`/`app.css` ; l'important review [SCOPE] recommandait de découper — non appliqué, commit unique. L'intention 007 elle-même reste conforme au plan |

### Non implémenté

| Élément prévu | Raison | Action requise |
|---------------|--------|----------------|
| Aucun | Tous les fichiers et critères du plan ont été livrés | — |

### Ajouts non prévus

| Élément ajouté | Raison |
|----------------|--------|
| `tests/Unit/Service/Board/StoryCardTest.php` modifié | Non recensé dans la §Fichiers à modifier du plan : il figeait `PipelineStage::Cadrage` comme valeur de fixture ; le renommage `Cadrage→Besoin` cassait la construction du `StoryCard`. Débusqué par le grep de contrôle prévu au plan (§Risques) — le recensement exhaustif des points de lecture de l'enum avait manqué ce fichier |

## Tests

| Code | Type prévu | Type réalisé | Statut |
|------|-----------|--------------|--------|
| `StoryStageMapper` | Unit | Unit réécrit (`brief→Idee`, précédence, estimate ignoré, top-level) | Fait |
| `PipelineStage` | Unit | Unit, `label()`/`isOnPipeline()` sur 6 cases | Fait |
| `ProjectBoardBuilder` | Unit | Unit adapté (5 colonnes, refacto en Cadré) | Fait |
| `ProjectBoardController` (rendu) | Functional | Functional (5 colonnes, `data-stage`, compteurs, `brief.md → idee`) | Fait |
| Board (parcours) | E2E | E2E (5 colonnes, « Idée » peuplée, filtre/tri) | Fait |
| `StoryCard` | Non prévu | Unit adapté (fixtures `Cadrage → Besoin`) | Fait — ajout imposé par le renommage |
| Couleur / CSS | Hors scope (vérif visuelle) | Contrôle visuel navigateur (rendu Nova) | Conforme (hors scope assumé) |

Suite : 162 tests unit/functional + build OK (message de commit).

## Dette technique identifiée

Issus de la review :

1. **[SCOPE] Commit multi-chantiers non découpé** — le commit `317a029` mêle la story 007, le popover de filtre et le rétrécissement des colonnes vides. La review demandait de découper en commits séparés ; la livraison a produit un commit unique. Dette d'historique (aucun défaut de code) : le scope 007 n'est pas isolable a posteriori sans réécriture. **Action** : à l'avenir, committer avant d'empiler des tours non planifiés.

Au-delà de la review :

2. **Recensement des points de lecture d'enum incomplet au plan** — `StoryCardTest.php` a échappé au relevé exhaustif (§Risques du plan). Le grep de contrôle a joué son rôle de filet, mais le plan aurait dû l'inclure. Aucune action résiduelle (corrigé), point de méthode pour les prochains renommages d'enum.
3. **Rétrécissement dynamique des colonnes vides** (`w-80 → w-44`) — comportement JS non couvert par un test E2E. Hors scope 007, mais dette de couverture sur cette feature entrelacée.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Le board affiche cinq colonnes de pipeline libellées **Idée, Besoin, Cadré, Implémenté, Livré**, dans cet ordre.
- [x] Une story dont le dossier ne contient que `brief.md` s'affiche en colonne **Idée** (vérifié functional + E2E ; validé sur données réelles avec `029-f-documents-zone`).
- [x] Une story avec `pitch.md` seul s'affiche en **Besoin**.
- [x] Une story avec `plan.md` comme fichier le plus avancé s'affiche en **Cadré**, `estimate.md` inclus (cas `plan+estimate→Cadre` testé).
- [x] Une story avec `review.md` comme fichier le plus avancé s'affiche en **Implémenté**.
- [x] Une story avec `report.md` s'affiche en **Livré**.
- [x] Un dossier sans aucun des cinq fichiers reconnus reste en « À vérifier ».
- [x] Les compteurs par colonne et le comptage total reflètent le nouveau découpage.

## Leçons apprises

- **Recenser les points de lecture d'une enum ne suffit pas via la seule recherche des `case` de production** : les fixtures de test (`StoryCardTest`) référencent les cases par leur nom et cassent au renommage. Sur un renommage d'enum, prévoir explicitement un grep sur `EnumName::CaseName` dans `tests/` dès le plan, pas seulement en filet de contrôle final.
- **Une question ouverte « tranchée par défaut » au plan gagne à nommer l'artefact concret** : le plan tranchait la couleur mais pas le nom du token ; nommer `--color-st-flag` dès le plan aurait évité un micro-arbitrage à l'implem.
- **Committer un tour avant d'en empiler un autre** : entrelacer trois chantiers au niveau ligne dans les mêmes fichiers (`_board.html.twig`, `app.css`) rend le découpage en commits propres impossible a posteriori — l'important review [SCOPE] est né de là.
- **Un catalogue fake unique (`FakeRepositoryCatalog::boardTree`) partagé functional + E2E** rend l'ajout d'un cas (`012-f-idee`) couvrant les deux niveaux sans duplication — pattern à reconduire pour toute nouvelle colonne/état.
