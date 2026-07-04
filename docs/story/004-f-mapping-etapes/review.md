# Review — Déduire l'étape de chaque story depuis les fichiers présents

> Date : 2026-07-05
> Stack : symfony
> Périmètre : working tree — 4 fichiers neufs (`PipelineStage`, `StoryStageMapper` + 2 tests unitaires), ~230 lignes
> Référence d'intention : `docs/story/004-f-mapping-etapes/plan.md` + `pitch.md`

## Bloquants

_(aucun)_

## Importants

_(aucun)_

## Mineurs

- [x] **[ROBUSTESSE] `isOnPipeline()` implémenté en négatif** — `src/Enum/Type/PipelineStage.php:46` — corrigé : remplacé par un `match` exhaustif (comme `label()`), tout cas futur hors-pipeline devra être tranché explicitement au lieu d'être silencieusement classé « sur le pipeline ».
- [x] **[TEST] Cas de data provider dupliqué** — `tests/Unit/Service/Mapping/StoryStageMapperTest.php` — corrigé : les deux cas `r`/`t` à entrée identique (`['plan.md', 'estimate.md']`) fusionnés en un unique `'refacto/tech avec plan seul → Planifié'` (`['plan.md']`) ; le cas transversal `plan + estimate` reste distinct.

## Points positifs

- **Pureté totale respectée** : `StoryStageMapper` est `final readonly` sans dépendance ni effet de bord — fonction pure au sens strict, exactement l'esprit `ProjectVerifier` visé par le plan. Testé en `new StoryStageMapper()` direct, sans conteneur.
- **Point unique de vérité (règle #8)** : la table `PRECEDENCE` en `const array` privée typée est le seul endroit à toucher pour une évolution de convention forge, ordre de précédence lisible d'un coup d'œil.
- **Match top-level exact** : `in_array($filename, $files, true)` ignore proprement les sous-dossiers (`feature-map/pitch.md`), piège identifié au plan et verrouillé par test.
- **Découplage enum/logique** : `PipelineStage` reste vocabulaire pur, aucune connaissance des noms de fichiers — cohérent avec `VerificationStatus`.
- **Couverture d'acceptation exhaustive** : chaque critère du pitch a son cas (précédence, transversaux ignorés, absence → À vérifier, sous-dossier, rejeu déterministe), + validation terrain sur 30 stories réelles du repo enao sans écart.

## Verdict

- Bloquants restants : 0 / 0
- Importants restants : 0 / 0
- Mineurs restants : 0 / 2 (les deux corrigés)
- Statut : **READY TO COMMIT**

Deux mineurs corrigés, QA + 100 tests verts. `/forge:commit` pour commit et push.
