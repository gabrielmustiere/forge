# Lire à distance un repo GitHub et vérifier qu'il est éligible forge

> Le connecteur lit en lecture seule l'arborescence de `docs/story/` d'un repo GitHub déclaré, en déduit si le repo est éligible forge, et signale le résultat (éligible / non-forge / token invalide / injoignable) sur le projet — sans jamais bloquer la déclaration ni modifier le repo.

## Contexte

La story `002-f-gestion-projets` a livré tout le domaine D2 : l'utilisateur déclare un repo (provider + URL + token chiffré), le retrouve dans sa liste, l'édite ou le retire. Mais cette déclaration est **strictement locale** : aucun appel réseau, aucune idée de si le token fonctionne, ni si le repo utilise réellement forge. Un projet fraîchement déclaré est donc un pari — on ne saura qu'il est cassé (token révoqué, URL erronée) ou vide (pas un repo forge) qu'une fois le kanban tenté, bien plus loin dans la chaîne.

Toute la chaîne de valeur (mapping → kanban → sync) est en aval de cette brique : **rien ne peut être projeté tant que l'app ne sait pas lire `docs/story/` d'un repo distant**. C'est la première fois que l'app sort d'elle-même pour toucher un repo réel — et c'est aussi le lieu naturel où trancher la question « ce repo est-il seulement exploitable ? » (hypothèse critique vision #3 : un accès en lecture suffit-il à récupérer `docs/story/` sans friction ?).

Cette feature couvre **C3.1** (lire l'arborescence de `docs/story/` d'un repo GitHub) **et C2.3** (vérifier la présence de `docs/story/` et signaler un repo non-forge), regroupées en un lot : le même appel réseau qui liste l'arborescence sert à statuer sur l'éligibilité. Livré seul, C3.1 serait une brique invisible ; adossé à C2.3, le lot produit un résultat utilisateur observable (un statut sur chaque projet).

## Alignement vision

- **Problème adressé** : premier maillon qui rend un repo distant *lisible* par l'app — condition nécessaire de la North Star (« où en est le projet X ? »), en validant l'hypothèse critique #3 (lecture distante sans friction).
- **Audience servie** : l'utilisateur principal (développeur / product owner solo) qui déclare des repos GitHub et veut savoir lesquels sont réellement exploitables.
- **Principes respectés** : « Lecture seule — la vérité vit dans les fichiers » (l'accès distant ne fait que *lire* l'arborescence, jamais écrire) ; « Sync fidèle avant tout » (un statut affiché reflète le résultat réel d'un appel, jamais une supposition) ; « Zéro friction d'ouverture » (la vérification ne bloque pas la déclaration).
- **Anti-objectif honoré** : « pas d'intégration profonde GitHub » — on lit *uniquement* l'arborescence de `docs/story/`, aucune issue, PR ou CI.
- **Impact North Star** : indirect mais bloquant — sans lecture distante fiable, ni mapping ni kanban ne peuvent exister.

## Utilisateurs concernés

- **Utilisateur local connecté** (l'unique utilisateur, mono-utilisateur — cf. anti-objectif vision « backend partagé ») — il voit désormais, pour chaque projet, un **statut d'éligibilité** et peut re-déclencher sa vérification. Toute la feature vit derrière le firewall de `login`.

## User Stories

- En tant qu'**utilisateur connecté**, je veux que l'app **vérifie l'accès à un repo GitHub** que j'ai déclaré (le token est-il valide ? le repo est-il joignable ?), afin de savoir immédiatement si mon projet est exploitable plutôt que de le découvrir plus tard.
- En tant qu'**utilisateur connecté**, je veux savoir si un repo **utilise réellement forge** (présence d'au moins une story sous `docs/story/`), afin de ne pas suivre un repo qui n'a rien à projeter en kanban.
- En tant qu'**utilisateur connecté**, je veux **re-déclencher la vérification** d'un projet via un bouton dédié, afin de confirmer qu'un accès réparé (nouveau token, URL corrigée) fonctionne, sans re-créer le projet.
- En tant qu'**utilisateur connecté**, je veux **voir le statut de chaque projet** (badge) et la **date de sa dernière vérification**, afin de repérer d'un coup d'œil ce qui est cassé, vide ou pas encore vérifié.
- En tant qu'**utilisateur connecté**, je veux que mon **token ne fuite jamais** (écran, HTML, logs) pendant la vérification, afin que ce secret reste protégé même lors d'un appel réseau.

## Règles métier

1. La vérification lit **uniquement l'arborescence** de `docs/story/` du repo distant, en **lecture seule** — jamais le contenu des fichiers à ce stade, jamais aucune écriture (aligné anti-objectif vision « intégration profonde »).
2. Un repo est déclaré **éligible forge** s'il expose au moins un dossier respectant la convention `docs/story/NNN-<f|r|t>-<slug>/`. À défaut → statut **non-forge**.
3. Les statuts distingués sont : `éligible`, `non-forge`, `token invalide` (réponse 401/403), `injoignable` (404 / erreur réseau / URL invalide), `provider non scannable` (projet GitLab, tant que `connecteur-gitlab-lecture` V2 n'existe pas), et `non vérifié` (état initial avant toute vérification).
4. Un projet **GitLab n'est jamais appelé** : il prend directement le statut `provider non scannable` (le connecteur GitLab arrive en V2).
5. Le **token est déchiffré en mémoire** au seul moment de l'appel réseau (via le `TokenCipher` existant), n'est **jamais renvoyé au navigateur** (HTML/JS) et **n'apparaît jamais dans les logs** — prolonge la règle sécurité de D2.
6. La vérification **ne bloque ni ne supprime jamais** un projet : elle *signale* le résultat. La déclaration reste instantanée et locale (principe « zéro friction »).
7. Le **statut et son horodatage sont persistés** sur le projet ; l'affichage de la liste et de la fiche projet les lit **en base**, sans rappeler GitHub à chaque rendu (protège du rate-limit et découple l'UI du réseau).
8. La vérification est déclenchée **automatiquement** à la déclaration et à l'édition d'un projet GitHub, et **manuellement** via un bouton « vérifier l'accès ». Aucun déclenchement **périodique** (relève de `sync-periodique`, V2).
9. Un échec de vérification (token invalide, injoignable) **n'est pas une erreur applicative** : c'est un statut légitime, affiché calmement, jamais une page d'erreur ni une exception remontée à l'utilisateur.

## Critères d'acceptation

- [ ] Déclarer ou éditer un projet GitHub déclenche une vérification dont le résultat s'affiche en badge sur le projet (liste + fiche).
- [ ] Un repo GitHub joignable, avec token valide et contenant `docs/story/NNN-<f|r|t>-<slug>/`, obtient le statut **éligible**.
- [ ] Un repo GitHub joignable mais **sans** `docs/story/` conforme obtient le statut **non-forge**, et le projet est **conservé** (pas supprimé).
- [ ] Un token invalide donne le statut **token invalide** ; un repo/URL inexistant ou une erreur réseau donne **injoignable**.
- [ ] Un bouton « vérifier l'accès » sur un projet re-déclenche la vérification et met à jour statut + horodatage sans re-créer le projet.
- [ ] Un projet GitLab affiche **provider non scannable** sans qu'aucun appel réseau ne soit tenté.
- [ ] Le token n'apparaît ni dans le HTML servi, ni dans les logs applicatifs, à aucune étape de la vérification (vérifiable).
- [ ] La liste des projets s'affiche **sans appel réseau** : les badges sont lus depuis la base (dernier statut connu + date).

## Hors scope

- **Lecture du contenu des fichiers** : le connecteur liste les fichiers présents, il ne rapatrie pas leur contenu. L'ouverture d'un document depuis une carte (C4.3) et sa lecture sont livrées plus tard.
- **Mapping fichiers → colonne (C3.2)** : déduire l'étape d'une story depuis les fichiers présents est le cœur métier de `mapping-etapes`, story distincte. Ici on liste, on ne mappe pas.
- **Connecteur GitLab (C3.1 GitLab)** : `connecteur-gitlab-lecture` (V2). Un projet GitLab est signalé « non scannable », pas appelé.
- **Sync périodique / automatique planifiée** : `sync-periodique` (V2). Ici, vérification à la déclaration/édition et à la demande uniquement.
- **Bouton de synchronisation du kanban et signalement fin des écarts de sync (C3.3/C3.4)** : relève de `sync-manuelle`. Cette story ne construit pas de tableau, elle statue sur l'accès.
- **Blocage / refus de déclaration** : on ne rouvre pas le choix D2 « déclaration sans friction » — un repo non éligible reste déclaré et signalé.

## Impacts transverses

- **Multi-tenant** : non (outil mono-utilisateur).
- **Multi-thème** : non.
- **i18n / traduction** : libellés UI en français (badges de statut, bouton « vérifier l'accès », messages) — pas de contenu multilingue.
- **API** : non (aucune ressource API exposée ; l'app reste server-rendered). L'app *consomme* l'API GitHub en lecture, elle n'en expose aucune.
- **Permissions** : inchangé — toute la feature vit derrière le firewall de `login`, sans nouveau rôle ni voter.
- **Emails / notifications** : non.
- **Migration de données** : oui — ajout d'un statut de vérification et d'une date de dernière vérification sur la table `project` (colonnes nullable, backfill implicite en `non vérifié`).
- **Comportement par défaut** : un projet existant non encore vérifié s'affiche en statut `non vérifié` jusqu'à la première vérification (auto à la prochaine édition, ou manuelle via le bouton).

## Notes pour le plan technique

> Pistes brutes — **ne pas concevoir ici**, à trancher en `/forge:feature-plan`.

- **Choix d'accès distant** : API GitHub REST vs GraphQL vs serveur MCP, pour lister l'arborescence de `docs/story/` (cf. `docs/stack.md` « décisions à trancher » + hypothèse vision #3). Candidat `/forge:adr`.
- **Abstraction du connecteur** : introduire une interface (type `RepositoryReader`) isolant l'accès distant, pour préparer GitLab (V2) et un éventuel fallback (mitigation du risque externe vision « API GitHub/GitLab »). L'implémentation GitHub est la seule fournie ici.
- **Réutilisation D2** : s'appuyer sur `RepositoryUrl` (owner/repo déjà parsés) et `TokenCipher::decrypt()` (token en clair en mémoire au moment de l'appel) — ne rien re-parser ni re-déchiffrer ailleurs.
- **Statut sur `Project`** : enum backed string (cf. convention `src/Enum/Type/`) pour le statut de vérification + champ `verifiedAt` (nullable) → migration `make:migration`.
- **Rate-limit GitHub** : prévoir la gestion des quotas (en-têtes `X-RateLimit-*`), un timeout raisonnable, et la traduction d'un dépassement en statut lisible plutôt qu'une exception.
- **Exceptions typées** : le connecteur remonte des cas typés (non trouvé, non autorisé, réseau), la couche appelante (manager/controller) les traduit en statut persisté. S'appuyer sur les skills `symfony:http-client-*`.
- **Déclenchement** : brancher la vérification dans le `ProjectManager` (create/update) existant + une action controller dédiée pour le bouton « vérifier l'accès ». Envisager Live Component pour un retour sans rechargement (à trancher au plan).
- **Détection de l'arborescence** : GitHub permet de lister un sous-dossier (`docs/story/`) sans rapatrier tout le repo (contents API / git tree API) — à cadrer au plan pour rester économe.

## Questions ouvertes

- **Granularité de l'appel** : lister `docs/story/` via l'API contents (un appel par niveau) vs l'API git tree (arbre récursif en un appel, filtré ensuite). Options : (a) contents, plus simple mais bavard ; (b) git tree, un appel mais nécessite le SHA de la branche par défaut. → à trancher en plan (impacte le rate-limit).
- **Vérification synchrone vs asynchrone à la déclaration** : (a) appel réseau bloquant pendant l'enregistrement (statut connu immédiatement, mais formulaire suspendu au réseau) ; (b) enregistrement instantané puis vérification déclenchée juste après (statut `non vérifié` fugace). → à trancher en plan selon l'UX voulue (le principe « zéro friction » penche vers (b)).
- **Branche lue** : lit-on la branche par défaut du repo, ou une branche configurable par projet ? → défaut proposé : branche par défaut du repo ; configurabilité repoussée si le besoin émerge.
- **Retour visuel du bouton « vérifier »** : rechargement simple vs Live Component (statut mis à jour sans reload). → cosmétique, à trancher en plan selon l'ambition UX.
