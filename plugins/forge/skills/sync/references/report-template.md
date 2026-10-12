# Report — <Titre de la story>

> **But** : garder la trace de ce qui a dérivé entre l'intention et le livré, et de ce qui reste à faire.
> **Registre** : factuel
> **Story** : `docs/story/<NNN>-<f|r|t>-<slug>/`
> **Amont** : `pitch.md` · `plan.md` · `review.md` <!-- guide: retirer `pitch.md` pour `-r-`/`-t-` (pas de pitch), `review.md` si la review n'a pas conservé de fichier -->

<!--
guide: Journal de clôture. Écrit par `/forge:sync`, seul propriétaire du fichier, dans la même passe qui réaligne le pitch et le plan — avant `/forge:commit`.
But : garder ce qu'AUCUN autre fichier ne porte après la passe — les écarts et où ils ont été réalignés, ce qui n'est pas livré, la dette, les leçons.
Ce que le report NE contient PAS, parce que ça vit ailleurs, à jour :
  - le périmètre livré → `plan.md` §Périmètre réaligné, et le commit ;
  - les critères cochés → `pitch.md` §Critères d'acceptation / `plan.md` §Critères de sortie, cochés en place ;
  - la stratégie de test → `plan.md` §Stratégie de test réalignée ;
  - le verdict de review et les compteurs de tests → `metadata.json` (entrées `Implémentation`, `Review`).
Recopier l'un d'eux crée une seconde source qui divergera. Pas de report du tout si rien n'est à garder (aucun écart, non-livré, dette ni leçon).
Format commun à tous les documents de story : charte `${CLAUDE_SKILL_DIR}/../../references/document-format.md`.
Registre FACTUEL (charte §3) : le report constate, il ne défend pas. Ce qui a dérivé, ce qui manque, avec la raison. Pas d'opinion, pas de projet.
Ne pas dupliquer la date ni les SHA de commit dans l'en-tête : ils vivent dans `metadata.json` (charte §2).
L'en-tête ci-dessus RESTE dans le fichier commité. Retirer ce bloc et tous les `> _Skill : ..._` à la rédaction.
-->

## Synthèse

> _Skill : la conclusion, en premier — on doit pouvoir ne lire qu'elle. Des compteurs, pas un pourcentage de conformité : « 71 % » ne dit rien qu'un compte d'écarts ne dise mieux. Ligne propre au track : `-r-` → comportement préservé (preuve consignée en amont) ; `-t-` → métriques baseline → mesurée (cible), telles que consignées par l'implem, « non mesuré » sinon._

- **Écarts** : <N> — <N> réalignés dans `plan.md`/`pitch.md`, <N> laissés tels quels.
- **Critères** : <N> / <total> atteints, cochés dans `pitch.md` <!-- guide: `plan.md` §Critères de sortie pour `-r-`/`-t-` -->.
- **Non livré** : <N | aucun>.
- **Dette** : <N> items, dont <N> critiques.
<!-- guide: -r- → - **Comportement préservé** : <preuve consignée, ex. « 42 tests de caractérisation verts avant et après (implem) »>. -->
<!-- guide: -t- → - **Métriques** : <métrique> <baseline> → <mesurée> (cible <cible>). -->

<1–2 phrases : ce qui a dérivé et pourquoi, ce qui reste.>

## Écarts avec le plan

> _Skill : trois sous-tables, toujours les trois (une ligne « Aucun » plutôt qu'un sous-bloc supprimé). La colonne « Réaligné dans » dit où l'écart a été reporté par cette même passe (`plan.md` §Approche retenue, `pitch.md` §Règles métier…) ou `—` s'il a été laissé volontairement. La raison vient des traces amont (checkpoints d'implem, `metadata.json`, findings de review) ; quand elle vient d'un finding, citer son tag et son résumé (charte §7). Sans trace : « non tracée » — jamais une raison reconstituée._

### Écarts volontaires

| Prévu | Réalisé | Raison | Réaligné dans |
|---|---|---|---|
| <ce que disait l'intention> | <ce qui est livré> | <raison + référence review/décision le cas échéant> | <`plan.md` §… / `pitch.md` §… / —> |

### Non implémenté

> _Skill : prévu, pas livré — y compris les critères non atteints. « Action requise » : story future, dette (renvoyer à son numéro), ou « aucune » si l'élément est couvert autrement (le dire)._

| Élément prévu | Raison | Action requise |
|---|---|---|
| <description> | <abandonné / reporté / oublié + pourquoi> | <dette n°…, story future, aucune> |

### Ajouts non prévus

| Élément ajouté | Raison | Réaligné dans |
|---|---|---|
| <description> | <raison — souvent un découpage découvert à l'implémentation ou un correctif de review> | <`plan.md` §Périmètre / —> |

## Dette technique identifiée

> _Skill : ce qui reste à faire après cette story, ordonné par criticité. Pour chaque item, dire d'où il vient : mineur de review non traité, réserve arbitrée, point « Hors review », test prévu non écrit, TODO introduit par le diff, contrainte de temps. Les findings repris de la review gardent leur format et leur tag **à l'identique** (charte §7). **Critique** en gras pour ce qui casse au prochain déploiement ou au premier usage réel. Jamais d'item de pipeline (« réaligner la doc », « écrire l'ADR ») : sync vient de réaligner, et l'ADR est une proposition en clôture, pas une dette. Si aucune : `_(aucune)_`._

1. **[TAG] <résumé>** — `<chemin>:<ligne>` — <action attendue>. <!-- guide: finding de review repris tel quel -->
2. **<Élément structurel>** — <action attendue>. **Critique** <quand>.

## Leçons apprises

> _Skill : 0 à 5 puces. Ce qu'un prochain plan sur cette zone aurait dû anticiper : piège technique, seuil d'outillage qui dessine le code, pattern qui a payé. Une leçon attachée à une zone de fichiers est candidate à `/forge:rules` — sync le propose en clôture. Pas de bilan d'humeur, pas de « tout s'est bien passé ». Si aucune : supprimer la section plutôt que de la remplir._

- **<Leçon>** : <ce qu'il faut retenir, contexte d'application future>.
