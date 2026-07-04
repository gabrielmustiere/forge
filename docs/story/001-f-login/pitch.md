# Verrouiller Forge Board derrière une connexion locale mono-utilisateur

> L'application entière vit derrière un écran de connexion : l'unique utilisateur se connecte pour accéder à ses projets et à ses tableaux, et se déconnecte quand il le souhaite. Objectif : protéger l'app et, en amont, les tokens de lecture qui y seront stockés.

## Contexte

Forge Board scanne des repos forge et stocke, à terme, des **tokens d'accès en lecture** aux repos GitHub de l'utilisateur (cf. `declaration-projet`). Même pour un outil strictement personnel tournant en local, laisser ces informations accessibles sans aucune barrière est un risque : n'importe qui ayant la main sur la machine ou le navigateur ouvert accède aux projets et aux secrets stockés.

Aujourd'hui le socle de sécurité Symfony est déjà scaffoldé (entité `User`, `security.yaml` avec `form_login` + `logout`, `access_control` qui protège tout sauf `/login`, `SecurityController`, fixture d'un compte `admin@example.com`), mais **l'écran de connexion n'existe pas encore** (le contrôleur rend un template absent) et le parcours n'est ni finalisé ni éprouvé. Tant que cette barrière n'est pas fermée et fonctionnelle, aucune autre feature manipulant des tokens ne peut être livrée sereinement — `login` est la première ligne du MVP, sans dépendance, et le prérequis de tout le reste.

## Alignement vision

- **Problème adressé** : nouveau pan — la vision ne parle pas de sécurité en tant que problème central, mais l'anti-objectif « outil personnel » et la contrainte « sécurité des tokens d'accès » (backlog) imposent cette barrière comme socle.
- **Audience servie** : l'utilisateur principal (développeur / product owner solo), le seul de la V1.
- **Principes respectés** : cohérent avec « outil personnel, mono-utilisateur » — aucun compte multiple, aucune inscription, aucun partage.
- **Impact North Star** : indirect. La connexion ne fait pas gagner du temps pour se resituer, mais conditionne l'accès au tableau qui, lui, le fait. Une barrière trop pénible (reconnexion permanente) nuirait au North Star — d'où le « rester connecté ».

## Utilisateurs concernés

- **Utilisateur unique** (`ROLE_USER`, le seul rôle applicatif) — accède à l'app après connexion ; sans session valide, toute URL le renvoie vers l'écran de login. C'est le seul acteur du système.
- **Aucun autre rôle** — pas d'admin distinct, pas de visiteur anonyme autorisé, pas de multi-utilisateur (anti-objectif vision « backend partagé / multi-utilisateur »).

## User Stories

- En tant qu'**utilisateur**, je veux me connecter avec mon email et mon mot de passe afin d'accéder à mes projets et à mes tableaux.
- En tant qu'**utilisateur**, je veux cocher « rester connecté » afin de ne pas ressaisir mes identifiants à chaque ouverture d'une app que je consulte plusieurs fois par jour.
- En tant qu'**utilisateur**, je veux me déconnecter afin de refermer l'accès quand je quitte mon poste.
- En tant qu'**utilisateur**, je veux qu'une tentative avec de mauvais identifiants soit rejetée avec un message clair mais neutre afin de comprendre l'échec sans qu'on m'indique quel champ est faux.
- En tant qu'**utilisateur non connecté**, je veux NE PAS pouvoir atteindre la moindre page applicative (liste de projets, kanban, document) afin que mes tokens et mes projets restent protégés.

## Règles métier

1. **Barrière totale** : toutes les routes applicatives exigent `ROLE_USER`. Seuls l'écran de login et les assets statiques (+ outils de dev) sont publics.
2. **Compte unique, non auto-inscriptible** : il n'existe qu'un seul compte utilisateur. Aucune page d'inscription, aucun « mot de passe oublié » en libre-service.
3. **Provisioning par fixtures** : le compte est créé par les fixtures (`admin@example.com` / `password` en dev). C'est le mécanisme retenu pour la V1, y compris hors dev — voir la dette assumée en *Hors scope*.
4. **Message d'erreur neutre** : en cas d'échec, le message n'indique jamais si c'est l'email ou le mot de passe qui est erroné (« Identifiants invalides »).
5. **Anti-brute-force** : les tentatives de connexion échouées sont limitées (throttling) pour la même identité / IP.
6. **Rester connecté** : une case optionnelle prolonge la session au-delà de la fermeture du navigateur (durée à fixer au plan — défaut proposé : 1 semaine). Décochée, la session suit le cycle de vie par défaut du navigateur.
7. **Redirections** : après connexion réussie → accueil du board ; après déconnexion → écran de login ; toute tentative d'accès non authentifié → écran de login.
8. **Le login est une barrière d'accès seule** : il n'intervient pas dans le chiffrement des tokens (voir *Hors scope*).

## Critères d'acceptation

- [ ] Non connecté, une requête vers n'importe quelle URL applicative (`/`, un projet, un document) redirige vers l'écran de login.
- [ ] L'écran de login s'affiche (email, mot de passe, case « rester connecté »).
- [ ] Des identifiants valides connectent l'utilisateur et le redirigent vers l'accueil du board.
- [ ] Des identifiants invalides restent sur l'écran de login avec un message neutre (« Identifiants invalides »), sans préciser le champ fautif.
- [ ] La case « rester connecté » cochée maintient la session après fermeture/réouverture du navigateur ; décochée, non.
- [ ] Après déconnexion, l'utilisateur est renvoyé à l'écran de login et les routes applicatives redeviennent inaccessibles.
- [ ] Au-delà d'un seuil de tentatives échouées, les connexions suivantes sont temporairement bloquées.
- [ ] Les fixtures créent le compte `admin@example.com` / `password` (`ROLE_USER`) et permettent de se connecter après `make db-reset` / rechargement des fixtures.

## Hors scope

- **Chiffrement des tokens au repos** : la protection cryptographique des tokens (clé dérivée d'`APP_SECRET`, non-réaffichage en clair) est cadrée dans `declaration-projet`, pas ici. `login` ne fait que garder l'accès.
- **Commande de gestion de compte** : pas de commande console `app:user:create` / reset password en V1. **Dette assumée** : sans elle, changer le mot de passe en usage réel passe par un rechargement des fixtures, ce qui réinitialise la base (donc les projets déclarés et leurs tokens). Acceptable pour un compte quasi-fixe au MVP ; évolution V2 naturelle → commande console.
- **Inscription / « mot de passe oublié » en self-service** : hors sujet pour un outil mono-utilisateur local.
- **Rôles et permissions fines / voters** : un seul rôle, aucun besoin de granularité.
- **Multi-utilisateur, comptes partagés, SSO, 2FA** : anti-objectifs vision ou surdimensionnés pour un usage personnel local.

## Impacts transverses

- **Multi-tenant** : non (mono-utilisateur par nature).
- **Multi-thème** : non.
- **i18n / traduction** : libellés de l'écran de login et messages d'erreur en français (pas de mécanisme i18n à introduire pour autant).
- **API** : non (aucune ressource API exposée par cette story).
- **Permissions** : firewall `main` déjà configuré (`form_login` + `logout` + `access_control ^/ → ROLE_USER`) ; à compléter avec `remember_me` et `login_throttling`. Aucun nouveau voter.
- **Emails / notifications** : non (pas de « mot de passe oublié »).
- **Migration de données** : la table `user` existe déjà (migration `Version20260420133408`). Aucune nouvelle migration attendue, sauf si `remember_me` impose un stockage (l'implémentation par signature n'en requiert pas).
- **Comportement par défaut** : l'utilisateur voit d'abord l'écran de login à chaque première visite d'une session non mémorisée.

## Notes pour le plan technique

- Créer le template manquant `security/login.html.twig` (le `SecurityController` le rend déjà) : formulaire email + password + case « rester connecté » + affichage de l'erreur d'auth.
- Activer `remember_me` (par signature, `secret: '%kernel.secret%'`) et `login_throttling` dans le firewall `main` de `security.yaml`.
- Fixer la cible de redirection du `logout` (`target: app_login`) — actuellement non renseignée.
- Vérifier / ajuster `default_target_path` du `form_login` vers l'accueil réel du board.
- Fixtures : le compte existe déjà dans `AppFixtures`. Confirmer qu'il reste l'unique source du compte et qu'il couvre bien le critère d'acceptation (rechargement → connexion possible).
- Écran de login = premier écran visible → il devra suivre le design system. Point à trancher côté design/plan : socle actuel « Paper » vs DA de référence « Nova · Midnight » (commit récent). Ne pas trancher ici.
- Tests : parcours de login/logout, redirection et protection des routes couverts en E2E Playwright (sélecteurs `data-test`). _Réalignement post-livraison : la protection des routes est vérifiée en E2E, sans test fonctionnel PHP dédié (cf. `plan.md` §Stratégie de test)._

## Questions ouvertes

- **Durée du « rester connecté »** : combien de temps le cookie remember-me maintient-il la session ? Options : (a) 1 semaine (défaut proposé), (b) 1 mois, (c) autre. → à trancher au plan.
- **DA de l'écran de login** : « Paper » (socle actuel) ou « Nova · Midnight » (DA de référence récente) ? → décision design, à trancher au plan.

---

## Changelog

| Date       | Type                     | Description                                       |
|------------|--------------------------|---------------------------------------------------|
| 2026-07-04 | Sync post-implémentation | §Notes pour le plan technique : la protection des routes est vérifiée en E2E (pas de test fonctionnel PHP dédié), aligné sur le code livré. Critères d'acceptation et règles métier inchangés (tous couverts). Réf. `report.md`. |
