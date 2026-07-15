# Déduire l'étape de chaque story depuis les fichiers présents

> **But** : figer l'intention métier de la feature — ce qu'on livre et pour qui, jamais comment.
> **Registre** : fonctionnel
> **Story** : `docs/story/004-f-mapping-etapes/`
> **Amont** : aucun

> Un moteur de mapping qui lit la liste des fichiers d'une story (`pitch.md`, `plan.md`, `review.md`, `report.md`…) et en déduit sa colonne sur un pipeline unifié — sans jamais lire le contenu, sans jamais deviner. C'est la brique qui transforme une arborescence `docs/story/` en positions sur un kanban.

## Contexte

La story `003-f-connecteur-github-lecture` a livré la lecture distante : l'app sait désormais lister l'arborescence de `docs/story/` d'un repo GitHub et sait qu'un repo est éligible forge (au moins une story conforme). Mais elle s'arrête là — elle voit des dossiers de stories et les noms de fichiers qu'ils contiennent, sans en tirer aucun sens. Aucune idée d'où en est chaque story : est-elle juste cadrée, planifiée, en review, livrée ?

C'est précisément le trou que cette feature comble. Sans moteur de mapping, il n'y a **rien à projeter en kanban** : le tableau (`kanban-projet`) a besoin d'une position par carte, et la synchronisation (`sync-manuelle`) a besoin de savoir ce qu'elle recalcule. Le mapping est le cœur métier du produit et son principal risque : c'est lui qui incarne l'*unfair advantage* (la connaissance intime de la convention forge) et qui doit tenir la promesse de Fidélité — zéro écart entre la colonne affichée et l'état réel des fichiers. Sans lui, l'app reste un lecteur de dossiers muet.

## Alignement vision

- **Problème adressé** : rend l'avancement d'une story *lisible* d'un coup d'œil, cœur du problème central de la vision (« où en est le projet X ? »). C'est le maillon qui convertit des fichiers en état.
- **Audience servie** : l'utilisateur principal (développeur / product owner solo), qui a besoin de se resituer en quelques secondes (North Star).
- **Principes respectés** : « État déduit, jamais saisi » (la colonne se calcule des seuls fichiers présents) ; « Sync fidèle avant tout » (une story indécidable est signalée, jamais rangée arbitrairement) ; « Lecture seule » (le moteur observe, ne modifie rien).
- **Hypothèses testées** : #1 (le mapping fichiers → colonne est-il déterministe et sans ambiguïté ?) — cette feature est sa mise à l'épreuve ; #4 (un pipeline unifié reste-t-il lisible malgré des tracks hétérogènes ?).
- **Impact North Star** : direct et bloquant — sans mapping, ni kanban ni sync ne peuvent exister ; c'est la condition de la lecture rapide.

## Utilisateurs concernés

- **Utilisateur local connecté** (l'unique utilisateur — outil mono-utilisateur, cf. anti-objectif vision « backend partagé ») — bénéficiaire indirect : cette feature ne produit pas encore d'écran (le rendu est `kanban-projet`), mais elle calcule la position que chaque carte occupera. Toute la feature vit derrière la connexion.
- **Aucun autre rôle** — pas de nouveau rôle ni niveau d'autorisation supplémentaire ; périmètre inchangé côté droits d'accès.

## User Stories

- En tant qu'**utilisateur connecté**, je veux que l'app **déduise l'étape de chaque story** depuis les fichiers présents dans son dossier, afin de savoir où en est chaque chantier sans ouvrir les dossiers un à un.
- En tant qu'**utilisateur connecté**, je veux que cette déduction fonctionne **pour les trois tracks** (feature, refacto, tech) sur un pipeline commun, afin de lire tous mes chantiers sur une échelle unique.
- En tant qu'**utilisateur connecté**, je veux qu'une story dont les fichiers **ne permettent pas de trancher** l'étape soit **signalée à part** plutôt que rangée au hasard, afin de ne jamais être induit en erreur par une position inventée.
- En tant qu'**utilisateur connecté**, je veux que le mapping reste **fidèle à la réalité des fichiers** (jamais une colonne qui ne correspond pas à un fichier réellement présent), afin de faire confiance au tableau au lieu de retourner vérifier à la main.

## Règles métier

1. La colonne d'une story se déduit **exclusivement de la présence de noms de fichiers** dans son dossier — **jamais du contenu** des fichiers, jamais d'une saisie manuelle (principe vision « État déduit, jamais saisi »).
2. Le **pipeline unifié** comporte quatre colonnes ordonnées : **Cadrage** → **Planifié** → **Review** → **Livré**, communes aux trois tracks.
3. Correspondance fichier déclencheur → colonne : `pitch.md` → **Cadrage** ; `plan.md` → **Planifié** ; `review.md` → **Review** ; `report.md` → **Livré**.
4. **Le fichier le plus avancé présent l'emporte**, sans condition : précédence `report.md` > `review.md` > `plan.md` > `pitch.md`. Une story qui possède `report.md` est en « Livré » même si `plan.md` est absent (séquence incomplète tolérée, non signalée).
5. **Colonne d'entrée par track** : une story feature (`f`) peut démarrer en « Cadrage » (via `pitch.md`) ; une story refacto (`r`) ou tech (`t`) n'a pas de `pitch.md` et entre directement en « Planifié ». Une carte `r`/`t` n'apparaît **jamais** en « Cadrage ». Le track (déduit de la lettre `f`/`r`/`t` de l'identifiant) est porté par un badge, non par une colonne distincte.
6. Une story qui ne présente **aucun** des quatre fichiers de pipeline (dossier ne contenant que `brief.md`, que des fichiers non reconnus, ou vide) n'est **pas rangée en colonne** : elle est classée dans une voie dédiée **« À vérifier »**, séparée du pipeline (principe « Fidélité : signalé, jamais deviné »).
7. Les fichiers **transversaux** (`estimate.md`, ADR, `brief.md`, et tout fichier non reconnu) n'influencent **pas** la colonne : ils sont ignorés par le calcul. Une story avec `plan.md` + `estimate.md` reste en « Planifié ».
8. La **table de correspondance fichier → colonne vit en un point unique**, facile à modifier quand la convention forge évolue (renommage de livrable, nouveau document) — règle métier transverse du backlog et mitigation du risque externe vision « évolution du plugin forge ».
9. Le calcul est **déterministe** : les mêmes fichiers produisent toujours la même colonne, sans dépendance à l'horodatage, à l'ordre de lecture ou à une source externe.

## Critères d'acceptation

- [ ] Une story feature contenant `pitch.md` (et rien de plus avancé) est classée en **Cadrage** ; avec `plan.md` en **Planifié** ; avec `review.md` en **Review** ; avec `report.md` en **Livré**.
- [ ] Une story refacto (`r`) ou tech (`t`) contenant `plan.md` est classée en **Planifié** et n'apparaît jamais en **Cadrage**.
- [ ] Une story possédant `report.md` mais pas `plan.md` est classée en **Livré** (le plus avancé l'emporte, sans signalement).
- [ ] Une story dont le dossier ne contient aucun des quatre fichiers de pipeline (uniquement `brief.md`, fichiers inconnus, ou vide) est classée en **« À vérifier »**, hors des quatre colonnes.
- [ ] La présence de `estimate.md` / d'un ADR / de `brief.md` ne modifie pas la colonne déduite des fichiers de pipeline.
- [ ] Rejouer le mapping sur les stories réelles du repo (`001`, `002`, `003`) les classe toutes en **Livré**, conformément à leurs fichiers — zéro écart (validation de l'hypothèse critique #1) ; validation renforcée en post-implémentation sur 30 stories réelles du repo `enao`, sans écart.
- [ ] La table fichier → colonne est modifiable en un seul endroit sans toucher au reste du moteur.

## Hors scope

- **Rendu du kanban** (colonnes, cartes, badges de track, couleurs, ordre d'affichage) : relève de `kanban-projet` (C4.1–C4.3). Ici on calcule une position, on ne dessine rien.
- **Identité de carte** (titre lu dans un fichier, identifiant affiché) : le mapping ne lit pas le contenu ; l'extraction du titre relève de `kanban-projet` (C4.2).
- **Déclenchement du scan / rafraîchissement** (bouton, périodicité, signalement d'erreurs de sync) : relève de `sync-manuelle` (C3.3, C3.4).
- **Mémorisation de l'état calculé** (conserver la colonne d'une consultation à l'autre vs la recalculer à chaque affichage) : décision technique, tranchée en `/feature-plan`.
- **Lecture du contenu des fichiers** : le moteur travaille sur les noms de fichiers seuls ; aucune analyse de contenu.
- **Colonne « En cours d'implémentation »** : l'implémentation ne produit aucun fichier ; une story en cours de code reste en « Planifié » jusqu'à l'apparition de `review.md`. Pas de signal git (écarté : sort de « l'état vit dans les fichiers » et frôle l'anti-objectif « intégration profonde git »).
- **Éligibilité forge / lecture distante** : déjà livrées par `003-f-connecteur-github-lecture`.

## Impacts transverses

- **Traduction / langues** : libellés de colonnes en français (Cadrage / Planifié / Review / Livré / À vérifier). Pas de contenu multilingue.
- **Droits d'accès** : inchangé — la barrière de connexion existante suffit, ni rôle ni niveau d'autorisation nouveau.
- **Cloisonnement des données** : non (outil mono-utilisateur).
- **Apparence / déclinaisons** : non.
- **Exposition à des tiers** : non (rien n'est mis à disposition hors de l'interface).
- **Emails / notifications** : non.
- **Données existantes** : à confirmer au plan selon la décision de mémorisation (si la colonne est conservée sur la story → reprise des stories déjà enregistrées ; si elle est recalculée à chaque affichage → aucune). Non tranché ici.
- **Comportement par défaut** : transparent — la feature ne produit pas d'écran à elle seule ; son effet devient visible avec `kanban-projet`.

## Questions ouvertes

- **`brief.md` seul → « À vérifier »** : retenu (cohérent avec la voie dédiée), mais une story avec uniquement `brief.md` est légitimement précoce, pas anormale. Option future : une colonne/entrée « Découverte » distincte de « À vérifier » si le mélange « précoce » vs « anomalie » gêne à l'usage. → à réévaluer une fois le kanban visible.
- **Mémorisation de la colonne** : (a) conservée sur la story et rafraîchie à chaque sync (implique de reprendre les stories déjà enregistrées) ; (b) recalculée à chaque affichage (rien à reprendre, mais la colonne dépend de la fraîcheur du dernier scan). → tranché en `/feature-plan`, en lien avec `sync-manuelle`.
- **Libellés définitifs des colonnes** : Cadrage / Planifié / Review / Livré retenus par défaut ; ré-ajustables à la mise en place du kanban selon la lisibilité réelle.

---

## Annexe — Pistes pour le plan

> Pistes brutes — **ne pas concevoir ici**, à trancher en `/forge:feature-plan`.

- **Table de mapping centralisée** : un point unique (enum, config, ou service dédié) portant la correspondance fichier → colonne, ordonnée par précédence. Envisager un enum backed string pour les colonnes (convention `src/Enum/Type/`, cf. `VerificationStatus`).
- **Entrée du moteur** : la structure déjà remontée par `003` (`StoryTree` / `StoryFolder` avec identifiant `NNN-<f|r|t>-<slug>` et liste des noms de fichiers). Ne rien re-parser côté réseau.
- **Track** : déduit de la lettre de l'identifiant (`f`/`r`/`t`) — logique déjà proche de ce que `StoryFolder` connaît.
- **Pureté** : un service de mapping sans effet de bord (fichiers → colonne), testable unitairement sur des jeux de noms de fichiers, dans l'esprit du `ProjectVerifier` (calcul pur, appliqué par le caller).
- **Persistance vs recalcul** : trancher si la colonne est un attribut stocké sur la story (couplé à la sync) ou recalculée au rendu — impacte migration et `sync-manuelle`.
- **Validation déterminisme** : prévoir un test rejouant le mapping sur les stories réelles (`001`/`002`/`003`) et éventuellement d'autres jeux forge, pour verrouiller l'hypothèse #1 avant de figer.
