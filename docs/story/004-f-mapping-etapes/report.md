# Report — Déduire l'étape de chaque story depuis les fichiers présents

> Pitch : `docs/story/004-f-mapping-etapes/pitch.md`
> Plan : `docs/story/004-f-mapping-etapes/plan.md`
> Date d'implémentation : 2026-07-05
> Commits liés : working tree non commité au moment du report (4 fichiers neufs untracked)
> Référence review : `review.md`

## Résumé

Conformité au plan **totale (100 %)** : les 4 fichiers prévus sont livrés à l'identique de la conception (enum `PipelineStage` pur à 5 cas, service `StoryStageMapper` `final readonly` sans dépendance, table `PRECEDENCE` en `const` privée = point unique règle #8), aucun fichier existant touché (purement additif). Les **7 critères d'acceptation** du pitch sont satisfaits. Un seul élément hors plan, favorable : une validation terrain sur 30 stories réelles du repo enao (au-delà du rejeu unitaire sur la forme `001`/`002`/`003` prévu au plan), sans écart. Review **READY TO COMMIT** (0 bloquant, 0 important, 2 mineurs corrigés). QA verte : PHPStan niveau 9 + style + build ; 100 tests PHPUnit, 228 assertions. Dette résiduelle nulle sur cette brique ; le branchement UI reste attendu en `kanban-projet`.

## Ce qui a été implémenté

### Fichiers créés

| Fichier                                              | Rôle                                                                                                   | Prévu dans le plan |
|------------------------------------------------------|--------------------------------------------------------------------------------------------------------|--------------------|
| `src/Enum/Type/PipelineStage.php`                    | Enum backed string des 5 étapes (Cadrage/Planifie/Review/Livre/AVerifier) + `label()` (FR) + `isOnPipeline()`. | Oui                |
| `src/Service/Mapping/StoryStageMapper.php`           | Service pur : `stageFor(StoryFolder): PipelineStage` ; table `PRECEDENCE` fichier → étape en `const` privée. | Oui                |
| `tests/Unit/Enum/PipelineStageTest.php`              | `label()` et `isOnPipeline()` par cas (5 + 5 via data providers).                                      | Oui                |
| `tests/Unit/Service/Mapping/StoryStageMapperTest.php`| Tous les critères d'acceptation : chaque fichier → sa colonne, précédence, absence → À vérifier, transversaux ignorés, sous-dossier ignoré, rejeu déterministe `001`/`002`/`003`. | Oui                |

### Fichiers modifiés

Aucun. La feature est purement additive : l'autowiring découvre l'enum et le service sans toucher config, controller, template ni entité — conforme au plan (§Fichiers à modifier : « Aucun »).

## Écarts avec le plan

### Écarts volontaires

| Prévu                                       | Réalisé                                  | Raison                                                       |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| _(aucun)_                                   | —                                        | Conception suivie à l'identique, aucun arbitrage rejoué.     |

### Non implémenté

| Élément prévu                               | Raison                                   | Action requise                                               |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| Aucun                                       | Tous les éléments du plan livrés.        | —                                                            |

### Ajouts non prévus

| Élément ajouté                              | Raison                                                                              |
|---------------------------------------------|-------------------------------------------------------------------------------------|
| **Validation terrain sur le repo réel enao** (`github.com/enao-io/ems`, projet id 7, 30 stories) via une commande console jetable supprimée après usage. Résultat : 30 stories classées sans écart, cas limites confirmés (précédence `plan+report` sans review → Livré ; transversaux `benchmark.md`, `.autopilot.json`, `estimate.md`, `brief.md` ignorés ; brief seul → À vérifier ; `r`/`t` → Planifié). | Le plan ne prévoyait qu'un rejeu déterministe sur la **forme** `001`/`002`/`003` en unitaire ; verrouiller l'hypothèse critique #1 sur un vrai repo forge hétérogène a été fait en bonus. **Aucune trace dans le code** (commande supprimée), à propager à la doc d'intention comme validation renforcée. |
| **Durcissement `isOnPipeline()`** : implémenté en `match` exhaustif au lieu de `!== AVerifier`. | Retour de review (mineur ROBUSTESSE, `review.md`) — tout cas futur hors-pipeline devra être tranché explicitement. Corrigé avant commit, pas un écart de scope. |
| **Fusion d'un cas de data provider dupliqué** (`StoryStageMapperTest`). | Retour de review (mineur TEST, `review.md`) — deux cas `r`/`t` à entrée identique fusionnés. Corrigé avant commit, pas un écart de scope. |

## Tests

| Code                                              | Type prévu   | Type réalisé                                            | Statut                    |
|---------------------------------------------------|--------------|---------------------------------------------------------|---------------------------|
| `PipelineStage`                                   | Unit         | Unit, `label()` (5 cas) + `isOnPipeline()` (5 cas) via data providers | Fait                      |
| `StoryStageMapper`                                | Unit         | Unit, couverture exhaustive des 7 critères + précédence, transversaux, sous-dossier, déterminisme | Fait — couverture étendue |
| Rejeu déterministe stories réelles                | Unit (forme `001`/`002`/`003`) | Unit `provideRealStories` + validation terrain 30 stories enao (bonus hors code) | Fait — couverture étendue |
| Test fonctionnel / E2E                            | Hors scope assumé | Pas écrit                                          | Conforme — aucune UI ni point d'entrée HTTP dans cette story |
| Test de persistance                               | Hors scope assumé | Pas écrit                                          | Conforme — recalcul à la volée, aucune persistance |

## Dette technique identifiée

Issus de la review (mineurs non traités) :

_(aucun — les 2 mineurs de la review ont été corrigés avant commit, cf. `review.md` §Verdict)._

Au-delà de la review (suites attendues, non bloquantes) :

1. **Consommation UI du moteur** — le mapper n'est branché à aucun écran ; le rendu kanban (colonnes, cartes, badges de track, ordre) relève de `kanban-projet` (C4.1–C4.3). Suite attendue, hors scope de cette story par conception.
2. **Déclenchement du scan / rafraîchissement** — relève de `sync-manuelle` (C3.3, C3.4). Le moteur recalcule à la volée, sans déclencheur propre.
3. **Concept `Track` (badge `f`/`r`/`t`)** — non introduit ici (mapper track-agnostique). Le cas pathologique d'une `r`/`t`-story ne contenant que `pitch.md` serait classé « Cadrage » ; accepté car il viole déjà la convention forge amont. `Track` naîtra quand `kanban-projet` en aura besoin.
4. **Nuance « brief seul → À vérifier » vs colonne « Découverte »** — question fonctionnelle ouverte au pitch, à réévaluer une fois le kanban visible.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Une story feature contenant `pitch.md` (et rien de plus avancé) est classée en **Cadrage** ; avec `plan.md` en **Planifié** ; avec `review.md` en **Review** ; avec `report.md` en **Livré**.
- [x] Une story refacto (`r`) ou tech (`t`) contenant `plan.md` est classée en **Planifié** et n'apparaît jamais en **Cadrage** (mapper track-agnostique : `plan.md` sans `pitch.md` → Planifié).
- [x] Une story possédant `report.md` mais pas `plan.md` est classée en **Livré** (le plus avancé l'emporte, sans signalement).
- [x] Une story dont le dossier ne contient aucun des quatre fichiers de pipeline (uniquement `brief.md`, fichiers inconnus, ou vide) est classée en **« À vérifier »**, hors des quatre colonnes.
- [x] La présence de `estimate.md` / d'un ADR / de `brief.md` ne modifie pas la colonne déduite des fichiers de pipeline.
- [x] Rejouer le mapping sur les stories réelles du repo (`001`, `002`, `003`) les classe toutes en **Livré**, zéro écart — validation de l'hypothèse #1, **renforcée** par un rejeu terrain sur 30 stories réelles du repo enao sans écart.
- [x] La table fichier → colonne (`PRECEDENCE`) est modifiable en un seul endroit sans toucher au reste du moteur.

## Leçons apprises

- **Une fonction pure track-agnostique suffit** : le concept `Track` a été délibérément écarté du moteur ; garantir la règle #5 en dur aurait exigé une abstraction prématurée. Pour un mapping stack-agnostique, laisser le vocabulaire (enum) ignorer les noms de fichiers et concentrer la convention dans une seule `const` de service donne un point d'évolution unique — à rejouer pour toute future règle forge.
- **La validation terrain sur un vrai repo forge externe a plus de valeur que le rejeu de forme** : le plan anticipait un rejeu sur `001`/`002`/`003` (tous identiques : pitch+plan+review+report). Le rejeu sur 30 stories hétérogènes du repo enao a confirmé les cas limites réels (transversaux inattendus comme `.autopilot.json`, `benchmark.md`) qu'un jeu synthétique n'aurait pas exercés. À prévoir dès le plan pour les briques de mapping critiques.
- **Point d'attention pour `kanban-projet`** : le moteur expose `PipelineStage::isOnPipeline()` pour distinguer « rangé sur le pipeline » de « signalé à part » (À vérifier). Le consommateur UI devra traiter la voie « À vérifier » séparément des 4 colonnes, pas comme une 5e colonne.
