# Plan technique — Déduire l'étape de chaque story depuis les fichiers présents

> Pitch : `docs/story/004-f-mapping-etapes/pitch.md`
> Stack : symfony

## Approche retenue

Le moteur est une **fonction pure** matérialisée par un service `StoryStageMapper` (`final readonly`, autowiré, sans état ni effet de bord) qui prend un `StoryFolder` — le value object déjà produit par `003-f-connecteur-github-lecture`, porteur de l'identifiant `NNN-<f|r|t>-<slug>` et de la liste triée des noms de fichiers — et retourne un `PipelineStage` (enum backed string, vocabulaire pur). La correspondance fichier → colonne vit en **une `const` privée ordonnée** dans le mapper (point unique, règle métier #8) : on itère du fichier le plus avancé au moins avancé, le premier dont le nom top-level exact est présent l'emporte (règle #4), et l'absence totale de fichier de pipeline tombe sur `AVerifier` (règle #6). Le mapping est **track-agnostique** : une story `r`/`t` n'atteint jamais « Cadrage » parce qu'elle ne produit pas de `pitch.md` par convention forge, sans qu'on ait besoin d'introduire un concept `Track` ici.

Aucune persistance : il n'existe pas d'entité `Story` (les stories sont transitoires, lues à distance), et le pitch renvoie la persistance de l'état scanné à `sync-manuelle`. La colonne est donc **recalculée à la volée** à partir du `StoryFolder`. Cette feature ne produit ni écran, ni entité, ni migration, ni câblage : c'est le moteur seul, couvert intégralement en tests unitaires. Son branchement dans une UI relève de `kanban-projet`, son déclenchement de `sync-manuelle`.

**Alternatives écartées** :

- **Entité `Story` + colonne persistée** : obligerait à persister tout l'arbre scanné (couplage fort à la sync), gros élargissement de scope que le pitch attribue explicitement à `sync-manuelle`. Prématuré.
- **Table de mapping en méthode statique sur l'enum** (`PipelineStage::fromFiles()`) : coupler l'enum aux noms de fichiers forge le sortirait de son rôle de vocabulaire pur — le découplage enum/service existant (`VerificationStatus` ignore les readers) est préféré.
- **Mapping track-aware avec enum `Track`** : garantirait la règle #5 en dur (une `r`-story avec seulement `pitch.md` → À vérifier), mais introduit un concept `Track` non nécessaire ici pour un cas qui viole déjà la convention forge. Écarté au profit de la simplicité ; `Track` naîtra quand `kanban-projet` en aura besoin (badge).
- **Lecture du contenu des fichiers** pour trancher les cas ambigus : contredit la règle #1 (présence seule) et le principe « État déduit, jamais saisi ». Le nom de fichier fait foi.

## Entités et modèle de données

Aucun impact modèle. Pas d'entité créée ni modifiée, pas de migration — la colonne est recalculée à la volée depuis un value object transitoire (cf. §Approche retenue).

## Mécanismes framework mobilisés

- **Enum backed string** (`src/Enum/Type/`) : `PipelineStage` suit le patron de `VerificationStatus` (cases + `label()`), vocabulaire pur découplé de toute logique de fichiers ou de DA.
- **Service autowiré `final readonly`** : `StoryStageMapper` suit le patron de `ProjectVerifier` (calcul pur, sans effet de bord, appliqué par le caller). Autowiring standard de `services.yaml` — aucune déclaration explicite, aucun tag.
- **Value object en entrée** : réutilisation directe de `App\Service\Github\StoryFolder` (identifiant + `files()`), sans re-parser ni retoucher le connecteur.

## Fichiers à créer

| Fichier                                              | Rôle                                                                                          |
|------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| `src/Enum/Type/PipelineStage.php`                    | Enum backed string des 5 étapes (Cadrage/Planifie/Review/Livre/AVerifier) + `label()` + `isOnPipeline()`. |
| `src/Service/Mapping/StoryStageMapper.php`           | Service pur : `stageFor(StoryFolder): PipelineStage` ; table de précédence fichier → colonne en `const` privée. |
| `tests/Unit/Enum/PipelineStageTest.php`              | `label()` et `isOnPipeline()` par cas.                                                        |
| `tests/Unit/Service/Mapping/StoryStageMapperTest.php`| Tous les critères d'acceptation : chaque fichier → sa colonne, précédence, absence → À vérifier, transversaux ignorés, rejeu déterministe forme `001`/`002`/`003`. |

## Fichiers à modifier

Aucun. La feature est purement additive : aucun fichier existant n'est touché (ni entité, ni controller, ni template, ni config — l'autowiring découvre le service et l'enum automatiquement).

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **API REST/GraphQL** : non.
- **i18n** : libellés de colonnes en français, portés par `PipelineStage::label()` (Cadrage / Planifié / Review / Livré / À vérifier). Pas de contenu multilingue, pas de fichier de traduction.
- **Permissions** : inchangé — aucun point d'entrée exposé dans cette story ; le firewall `login` protège l'app quand `kanban-projet` consommera le moteur.
- **Emails / notifications** : non.
- **Migration de données** : non — recalcul à la volée, aucune colonne persistée (cf. §Approche retenue).
- **Comportement par défaut** : transparent — aucun écran produit ; l'effet du moteur devient visible avec `kanban-projet`.

## Ordre d'implémentation

1. [ ] `src/Enum/Type/PipelineStage.php` : cases en ordre de pipeline (`Cadrage`, `Planifie`, `Review`, `Livre`, `AVerifier`), `label()`, `isOnPipeline()` (`false` pour `AVerifier`).
2. [ ] `tests/Unit/Enum/PipelineStageTest.php` : `label()` par cas + `isOnPipeline()`.
3. [ ] `src/Service/Mapping/StoryStageMapper.php` : `const` privée de précédence (`report.md`→Livre, `review.md`→Review, `plan.md`→Planifie, `pitch.md`→Cadrage), `stageFor(StoryFolder)` avec match top-level exact et repli `AVerifier`.
4. [ ] `tests/Unit/Service/Mapping/StoryStageMapperTest.php` : couverture complète des critères d'acceptation (cf. §Stratégie de test).
5. [ ] QA finale : `make quality` (PHP-CS-Fixer + PHPStan niveau 9 + build) puis `make phpunit`.

## Stratégie de test

| Code                                  | Type | Ce qu'on vérifie                                                                                                                                                                     |
|---------------------------------------|------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `PipelineStage`                       | Unit | `label()` renvoie le libellé FR attendu par cas ; `isOnPipeline()` vrai pour les 4 colonnes, faux pour `AVerifier`.                                                                  |
| `StoryStageMapper`                    | Unit | `pitch.md` seul → Cadrage ; `plan.md` → Planifié ; `review.md` → Review ; `report.md` → Livré ; précédence (`report.md` sans `plan.md` → Livré) ; `plan.md`+`estimate.md` → Planifié (transversal ignoré) ; aucun fichier de pipeline (`brief.md` seul / inconnu / vide) → À vérifier ; nom en sous-dossier (`x/pitch.md`) ne compte pas ; rejeu déterministe sur la forme `001`/`002`/`003` (pitch+plan+review+report → Livré). |

**Hors scope tests pour cette story** :

- Pas de test fonctionnel ni E2E : aucune UI, aucun point d'entrée HTTP, aucun câblage — le moteur est une fonction pure entièrement couverte en unitaire.
- Pas de test de lecture distante : l'entrée (`StoryFolder`) est déjà couverte par les tests de `003-f-connecteur-github-lecture`.
- Pas de test de persistance : aucune (recalcul à la volée).

## Risques et points d'attention

- **Évolution de la convention forge** (risque externe vision) : un renommage de livrable (`report.md` → autre) casserait le mapping. Mitigation : table de précédence en un point unique (`const` du mapper), modifiable sans toucher à la logique — exactement la règle #8.
- **Fichiers en sous-dossier** : `StoryFolder::files()` peut contenir des chemins relatifs imbriqués (ex : `feature-map/overview.md`). Mitigation : le match se fait sur le **nom top-level exact** (`in_array('pitch.md', $files, true)`), un `sous-dossier/pitch.md` ne déclenche donc jamais une colonne — comportement testé.
- **Règle #5 en cas pathologique** : une `r`/`t`-story ne contenant que `pitch.md` (violation de convention) serait classée « Cadrage » par le mapping track-agnostique. Accepté : ce cas viole déjà la convention forge amont et ne se produit pas dans un repo forge sain ; le durcir exigerait un concept `Track` prématuré ici (cf. alternative écartée).
- **Déterminisme** : aucune dépendance à l'horloge, à l'ordre de lecture ou à une source externe — le résultat ne dépend que de l'ensemble des noms de fichiers. Verrouillé par le test de rejeu sur `001`/`002`/`003`, et confirmé en post-implémentation par une validation terrain sur 30 stories réelles d'un repo forge externe (`enao`), sans écart (cf. `report.md`).

## Questions ouvertes

Aucune côté technique — les trois arbitrages (persistance, garantie de la règle #5, emplacement de la table) ont été tranchés au cadrage du plan (recalcul à la volée / track-agnostique / `const` dans le service). Les questions ouvertes résiduelles sont fonctionnelles et documentées dans le pitch (nuance « brief seul → À vérifier », libellés définitifs), à réévaluer une fois `kanban-projet` visible.

---

## Changelog

| Date       | Type                      | Description |
|------------|---------------------------|-------------|
| 2026-07-05 | Sync post-implémentation  | §Risques (Déterminisme) : ajout de la validation terrain sur 30 stories réelles du repo `enao`, qui renforce le rejeu unitaire `001`/`002`/`003` prévu (bonus hors code, cf. `report.md` §Ajouts non prévus). |
