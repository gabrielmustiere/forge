# Cloner en local le repo d'un projet depuis son kanban

> Depuis la vue kanban d'un projet, un bouton rapatrie (ou met à jour) le repo GitHub/GitLab déclaré dans `private/`, afin de disposer d'une copie locale de travail. C'est la première brique du pivot « l'app agit » : sans repo local, aucun skill de cadrage ne pourra s'exécuter par la suite.

## Contexte

Le pivot de la vision fait passer Forge Board de « miroir qui observe » à « atelier qui agit » : à terme, un besoin exprimé depuis le board déclenche un skill de cadrage (interview, pitch) via Symfony AI, qui produit une story dans le repo. Mais un skill forge s'exécute **sur des fichiers**, dans un repo — pas sur un flux d'API. Il faut donc, avant tout, une **copie locale du repo** sur laquelle agir.

Aujourd'hui, l'app ne fait que *lire* `docs/story/` à distance via l'API (story 003) pour projeter le kanban. Elle n'a jamais rapatrié un repo : aucun fichier du projet ne vit en local. Tant que cette copie n'existe pas, rien du parcours de production (exécuter un skill, produire une story) ne peut démarrer. Cette story livre **uniquement cette brique d'amorçage** : le clone. Elle est petite, démontrable, et débloque tout l'aval.

## Alignement vision

- **Problème adressé** : première brique matérielle du pivot — sans copie locale, aucun skill de cadrage ne peut s'exécuter sur le repo. Prérequis en amont de la North Star primaire (« besoin exprimé → story produite »).
- **Audience servie** : l'utilisateur local du POC (le dev, jouant les deux rôles) ; prépare l'entrée du PO non-technique (horizon 1 an).
- **Principes respectés** : « l'app agit mais reste bornée » (le clone est une action **locale**) ; « cadrage only » (aucune écriture dans `src/`, aucune génération de code) ; « projection lecture-seule préservée » (le kanban existant est inchangé) ; « prouver avant d'ouvrir » (brique POC, mono-utilisateur).
- **Anti-objectifs honorés** : pas de génération de code ; pas de multi-utilisateur ; **pas de push distant** dans cette story (le clone/pull ne fait que lire le repo distant).
- **Impact North Star** : indirect mais bloquant — sans copie locale, tout le parcours de production reste impossible.

## Utilisateurs concernés

- **Utilisateur local connecté** (unique utilisateur au POC, derrière le firewall `login`) — voit un bouton sur la vue kanban d'un projet et peut cloner, ou mettre à jour, le repo en local. C'est le seul rôle impacté.
- Aucun autre rôle : l'app reste mono-utilisateur au POC.

## User Stories

- En tant qu'**utilisateur connecté**, je veux **cloner en local** le repo d'un projet déclaré depuis sa vue kanban, afin de disposer d'une copie de travail sur laquelle lancer plus tard un cadrage.
- En tant qu'**utilisateur connecté**, je veux que le bouton **mette à jour (git pull)** le clone s'il existe déjà, afin de rafraîchir les fichiers sans tout re-cloner.
- En tant qu'**utilisateur connecté**, je veux **voir l'état de l'opération** (clonage en cours / cloné / échec avec raison), afin de savoir si ma copie locale est prête sans quitter l'écran.
- En tant qu'**utilisateur connecté**, je veux que l'interface **NE se bloque PAS** pendant un clone long, afin de continuer à naviguer pendant que l'opération tourne en tâche de fond.

## Règles métier

1. Le bouton est disponible sur **tout projet déclaré** — aucune condition de vérification forge préalable (le succès/échec du clone vaut lui-même test d'accès).
2. Cible du clone : **`private/<identifiant-projet>/`**, un dossier par projet. L'identifiant doit éviter toute collision entre deux repos de même nom (identifiant précis tranché au plan).
3. Si le dossier cible contient déjà un clone du repo → **`git pull`** (mise à jour), jamais un re-clone destructif.
4. Le clone utilise le **token d'accès déjà stocké** du projet (le même que la lecture) pour les repos privés. Le token n'apparaît **jamais en clair** — ni dans les logs, ni persisté dans la config git du clone.
5. **GitHub et GitLab** sont tous deux supportés dès cette story (l'URL de clone se dérive du provider + de l'URL déclarée).
6. L'opération est **asynchrone** : le projet expose un état de clone (`non cloné` / `clonage…` / `cloné` / `échec` + raison + horodatage).
7. Le contenu cloné dans `private/` (hors `.gitkeep`) **ne doit jamais être committé** dans le repo du Board — c'est un artefact local.
8. Cette story **ne modifie ni ne pousse rien** sur le repo distant (clone/pull = lecture seule côté remote).

## Critères d'acceptation

- [ ] Un bouton « Cloner » (ou « Cloner / Mettre à jour ») est visible sur la vue kanban d'un projet.
- [ ] Cliquer sur un projet non encore cloné rapatrie le repo dans `private/<projet>/` et l'état passe à « cloné ».
- [ ] Cliquer sur un projet déjà cloné exécute un `git pull` et l'état reflète la mise à jour.
- [ ] Un repo **privé** se clone avec le token stocké (un repo public aussi).
- [ ] Un échec (token invalide, repo injoignable, réseau) affiche un état « échec » avec une **raison lisible**, sans planter l'app.
- [ ] Pendant un clone long, l'interface reste **utilisable** (opération asynchrone).
- [ ] **GitHub et GitLab** fonctionnent tous les deux.
- [ ] Le contenu cloné dans `private/` n'est pas committé au repo du Board (`.gitignore` en place).

## Hors scope

- **Exécution d'un skill de cadrage** sur le clone — story suivante ; ici on ne fait qu'amener les fichiers en local.
- **Commit / push de contenu** vers le repo distant — appartient à l'horizon « production » de la vision, pas à cette story.
- **Changement de la source du kanban** — la projection continue de lire `docs/story/` via l'API (story 003) ; le clone ne la remplace pas.
- **Multi-utilisateur / permissions PO** — hors POC.
- **Nettoyage / suppression du clone, gestion de l'espace disque** — non couvert (candidat à une story ultérieure).

## Impacts transverses

- **Multi-tenant** : non (mono-utilisateur au POC).
- **Multi-thème** : non.
- **i18n / traduction** : oui — libellés du bouton et des états (`clonage…`, `cloné`, `échec`) à traduire selon la config i18n du projet.
- **API** : non — action interne déclenchée depuis l'UI, aucun endpoint public exposé.
- **Permissions** : inchangé — l'action vit derrière le firewall `login` existant.
- **Emails / notifications** : non.
- **Migration de données** : **oui** — le projet doit persister un état de clone (statut, chemin local, horodatage, éventuelle raison d'échec) → nouveaux champs sur `Project` ou entité dédiée (tranché au plan), donc migration Doctrine.
- **Comportement par défaut** : un projet non cloné s'affiche comme aujourd'hui, avec en plus le bouton et l'état « non cloné ». Le kanban est inchangé.

## Notes pour le plan technique

- **Persistance de l'état** : champs sur `Project` (`clone_status` en enum, `cloned_at`, `local_path`, `last_clone_error`) ou entité `Clone` dédiée — à trancher.
- **Exécution asynchrone** : Symfony Messenger (déjà en place, transport Doctrine) — message `CloneRepository` + handler ; l'état transite par l'entité. Rafraîchissement UI via Live Component / Turbo sans reload.
- **Auth du clone** : composer l'URL HTTPS avec le token, ou credential helper éphémère (`GIT_ASKPASS`), **sans** écrire le token dans le `.git/config` du clone (fuite). À sécuriser au plan.
- **Provider → URL de clone** : dériver via `Provider::host()` + `url` du projet.
- **Enum `CloneStatus`** dans `src/Enum/Type/` (convention projet : backed string enums).
- **Config** : ajouter `private/*` (sauf `.gitkeep`) au `.gitignore` du Board — actuellement `private/` n'est pas ignoré.

## Questions ouvertes

- **Identifiant du dossier de clone** : `private/<slug-du-nom>` ? `private/<id>` ? `private/<owner>-<repo>` extrait de l'URL ? Options : (a) nom humanisé — collisions possibles, (b) id numérique du projet — sûr mais peu lisible, (c) `owner/repo` de l'URL. → tranché au plan.
- **Modèle de persistance de l'état** : champs sur `Project` (simple, relation 1-1) vs entité `Clone` dédiée (extensible pour l'exécution de skills à venir). → tranché au plan.
- **Détection « déjà cloné »** : présence du dossier + validité du remote, ou état persisté fait foi ? → tranché au plan / implem.
