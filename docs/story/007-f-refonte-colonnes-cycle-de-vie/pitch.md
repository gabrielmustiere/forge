# Aligner les colonnes du board sur le cycle de vie réel d'une story forge

> Renommer les colonnes du kanban et introduire une colonne « Idée » pour que chaque étape du board corresponde à un document forge concret — de la simple idée (`brief.md`) jusqu'à la livraison (`report.md`).

## Contexte

Le board projette aujourd'hui chaque story sur un pipeline de quatre colonnes — **Cadrage → Planifié → Review → Livré** — déduites du fichier le plus avancé présent dans le dossier de la story (`pitch.md` → Cadrage, `plan.md` → Planifié, `review.md` → Review, `report.md` → Livré). Tout dossier sans aucun de ces fichiers tombe dans la voie séparée « À vérifier ».

Deux frictions :

1. **Les stories tout juste dégrossies sont invisibles du pipeline.** Une story issue de `/feature-interview` ne contient qu'un `brief.md` : elle n'a aucun fichier reconnu, donc elle atterrit en « À vérifier » — au milieu des vrais cas douteux (dossiers mal formés). Une idée cadrée par interview n'est pas un cas à vérifier : c'est la toute première étape du cycle, et elle mérite sa place sur le pipeline.
2. **Le vocabulaire des colonnes ne parle pas le langage du cycle.** « Cadrage / Planifié / Review » décrit des noms de skills plus qu'un état de progression lisible d'un coup d'œil. L'utilisateur veut une échelle qui se lit comme un cycle de vie : de l'idée à la livraison.

Sans rien faire, le board continue de masquer les idées en phase interview et impose une lecture qui suppose de connaître les noms de livrables forge.

## Alignement vision

- **Problème adressé** : renforce le cœur de la vision — « déduire l'état d'une story depuis les fichiers réels ». On étend le moteur de mapping fichiers → colonne à un document jusque-là ignoré (`brief.md`) et on clarifie l'échelle.
- **Audience servie** : l'utilisateur principal (développeur/PO solo qui pilote ses stories), directement.
- **Principes respectés** : #2 « état déduit, jamais saisi » (la colonne reste calculée depuis les fichiers) et #3 « sync fidèle » (une idée en interview cesse d'être faussement rangée en « À vérifier »). Sert l'hypothèse critique #1 (mapping fichiers → étape déterministe) en la complétant.
- **Impact North Star** : améliore le « temps pour se resituer » — une colonne de plus en amont rend l'antichambre des stories immédiatement lisible, sans ouvrir les dossiers.

## Utilisateurs concernés

- **Utilisateur du board** (développeur/PO solo, lecture seule) — voit désormais cinq colonnes au vocabulaire de cycle de vie, et retrouve ses idées en phase interview sur le pipeline plutôt qu'en « À vérifier ».
- **Aucun autre rôle** : l'app reste mono-utilisateur, lecture seule.

## User Stories

- En tant qu'**utilisateur du board**, je veux que les colonnes s'appellent **Idée, Besoin, Cadré, Implémenté, Livré** afin de lire la progression d'une story comme un cycle de vie, sans avoir à traduire mentalement des noms de livrables.
- En tant qu'**utilisateur du board**, je veux qu'une story qui n'a qu'un `brief.md` apparaisse en **Idée** afin de voir mes idées dégrossies par interview dès la première colonne du pipeline.
- En tant qu'**utilisateur du board**, je veux que « À vérifier » ne contienne **plus** que les dossiers réellement non reconnus afin de distinguer une vraie anomalie d'une story en tout début de cycle.

## Règles métier

1. La colonne d'une story se déduit du **fichier le plus avancé** présent dans son dossier, selon l'échelle ordonnée (du plus avancé au moins avancé) : `report.md` → **Livré**, `review.md` → **Implémenté**, `plan.md` → **Cadré**, `pitch.md` → **Besoin**, `brief.md` → **Idée**.
2. Un dossier ne contenant **aucun** de ces cinq fichiers reste dans la voie séparée **« À vérifier »** (comportement inchangé).
3. `estimate.md` **n'est pas** un fichier déclencheur : il est optionnel et n'influe pas sur la colonne. Une story qui possède `plan.md` + `estimate.md` (sans `review.md`) reste donc en **Cadré**.
4. Le mapping est **indépendant du track** : features (`f-`), refactos (`r-`) et évolutions techniques (`t-`) suivent la même échelle. Conséquence assumée : les tracks `r-` et `t-`, qui n'ont ni interview ni pitch et démarrent à `plan.md`, entrent directement en **Cadré** — les colonnes **Idée** et **Besoin** ne contiennent en pratique que des features.
5. L'ordre d'affichage des colonnes suit l'échelle : **Idée → Besoin → Cadré → Implémenté → Livré**, puis la voie « À vérifier ».

## Critères d'acceptation

- [ ] Le board affiche cinq colonnes de pipeline libellées **Idée, Besoin, Cadré, Implémenté, Livré**, dans cet ordre.
- [ ] Une story dont le dossier ne contient que `brief.md` s'affiche en colonne **Idée** (et non plus en « À vérifier »).
- [ ] Une story avec `pitch.md` (sans `plan.md`/`review.md`/`report.md`) s'affiche en **Besoin**.
- [ ] Une story avec `plan.md` comme fichier le plus avancé s'affiche en **Cadré**, y compris si `estimate.md` est aussi présent.
- [ ] Une story avec `review.md` comme fichier le plus avancé s'affiche en **Implémenté**.
- [ ] Une story avec `report.md` s'affiche en **Livré**.
- [ ] Un dossier sans aucun des cinq fichiers reconnus reste en « À vérifier ».
- [ ] Les compteurs par colonne et le comptage total reflètent le nouveau découpage.

## Hors scope

- **Ajouter `estimate.md` comme déclencheur ou colonne dédiée** : jugé optionnel, écarté pour garder l'échelle nette.
- **Refonte du filtre par tag et du tri** : indépendant, déjà livré ; non touché ici.
- **Toute écriture dans `docs/story/`** : l'app reste en lecture seule (anti-objectif vision).
- **Distinguer sur le board « en cours de codage » vs « en relecture »** : accepté que la phase de codage actif (après `plan`, avant `review`) s'affiche en **Cadré** — aucun fichier forge ne marque « je code maintenant », on ne crée pas d'état artificiel.

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **i18n / traduction** : non — libellés en français en dur, comme l'existant.
- **API** : non.
- **Permissions** : inchangé.
- **Emails / notifications** : non.
- **Migration de données** : non — aucune donnée persistée pour l'état d'une story ; tout est déduit au scan. (Vérifier toutefois si des métadonnées ou des tests figent les anciennes valeurs d'étape.)
- **Comportement par défaut** : tous les utilisateurs (il n'y en a qu'un) voient directement le nouveau découpage ; pas de feature flag.

## Notes pour le plan technique

> Pistes brutes — à explorer en `/forge:feature-plan`, ne rien trancher ici.

- Cœur du changement : l'enum `App\Enum\Type\PipelineStage` (cases + `label()`) et la table `PRECEDENCE` de `App\Service\Mapping\StoryStageMapper` — ajouter `brief.md` en bas de l'échelle, renommer les cases/libellés.
- Vérifier les **tokens de couleur** par étape : le template `templates/project/_board.html.twig` mappe `stageAccent`/`stageBar` sur `stage.value` (`st-pitch`, `st-plan`, `st-review`, `st-report`). Cinq colonnes → prévoir un token/couleur pour la nouvelle colonne « Idée » et réaligner le mapping sur les nouvelles `value`.
- Impact possible sur `metadata.json` des stories et sur les fixtures/tests E2E qui vérifient les libellés ou compteurs de colonnes (`data-stage`, `column-count`) — les recenser.
- Story fondatrice à relire : `004-f-mapping-etapes` (a livré le mapping actuel) — cette story en est l'évolution.

## Questions ouvertes

- **Couleur/accent de la colonne « Idée »** : quelle teinte dans le design system Nova ? → à trancher au plan (choix visuel, non bloquant fonctionnellement).
