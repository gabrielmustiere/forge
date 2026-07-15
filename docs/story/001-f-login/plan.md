# Plan technique — Connexion / déconnexion locale (login)

> **But** : figer le comment technique de la feature — architecture, périmètre de code, ordre d'exécution.
> **Registre** : technique
> **Story** : `docs/story/001-f-login/`
> **Amont** : `pitch.md`

_Note : story et ligne de backlog alignées sous le slug `login` (anciennement `acces-local`)._

## Approche retenue

Aucun code métier, aucune entité, aucune migration : la story finalise la **barrière de sécurité native Symfony déjà scaffoldée**. Le socle existe (entité `User`, firewall `main` avec `form_login` + `logout` + `access_control ^/ → ROLE_USER`, `SecurityController`, fixture d'un compte). Il manque le **template du formulaire de login** (le contrôleur rend `security/login.html.twig`, absent), le câblage `remember_me` + `login_throttling`, la cible de redirection du logout, et les tests.

On s'appuie donc exclusivement sur les mécanismes officiels du `security-bundle` : authentification par `form_login` (CSRF déjà activé), `remember_me` **par signature** (`secret`-based, sans stockage en base → pas de migration), `login_throttling` (limiteur natif par IP + identifiant, qui requiert le composant `symfony/rate-limiter`, actuellement absent). Côté UI, on crée le template de login et on aligne le layout d'auth `security.html.twig` sur la DA Nova déjà utilisée par `base.html.twig`.

### Mécanismes mobilisés

- **`form_login` (security-bundle)** : authentification email/mot de passe, `enable_csrf: true` déjà posé → le template **doit** émettre `csrf_token('authenticate')`.
- **`remember_me` par signature** : cookie de session longue durée signé avec `%kernel.secret%`, sans persistance en base. Case `_remember_me` dans le formulaire.
- **`login_throttling`** : limiteur de tentatives natif (par IP + identifiant), anti-brute-force. Dépend de `symfony/rate-limiter`.
- **`access_control` firewall** : protection globale `^/ → ROLE_USER` déjà en place ; `^/login → PUBLIC_ACCESS` déjà en place. Aucun voter custom.
- **`AuthenticationUtils`** : déjà utilisé par `SecurityController::login()` pour exposer `error` + `last_username` au template.
- **Fixtures Doctrine (`DataFixtures\AppFixtures`)** : provisionnent l'unique compte.

### Alternatives écartées

- **`remember_me` avec `token_provider` en base (persistent)** : imposerait une table + migration pour un mono-utilisateur local — la variante par signature suffit et reste sans schéma.
- **Authenticator custom** : inutile, `form_login` natif couvre 100 % du besoin (identifiant email + mot de passe).
- **Commande console de provisioning du compte** : écartée au pitch (fixtures-only en V1) — laissée en dette V2.
- **Layout de login minimal générique (gris/blanc actuel)** : rejeté pour cohérence visuelle — l'app shell est déjà en Nova, l'écran de login doit l'être aussi.

## Modèle de données

Aucun impact modèle. L'entité `App\Entity\User` et la table `user` (migration `Version20260420133408`) existent déjà et suffisent. `remember_me` par signature n'ajoute aucune colonne ni table.

## Périmètre

### Fichiers à créer

| Fichier                                              | Rôle                                                              |
|------------------------------------------------------|-------------------------------------------------------------------|
| `templates/security/login.html.twig`                 | Formulaire de login Nova : `h1` « Se connecter », champs `_username`/`_password`, case `_remember_me`, `csrf_token('authenticate')`, bloc erreur neutre, `last_username`, `data-test` complets. |

> _Note sync (2026-07-04)_ : le test fonctionnel PHP `tests/Functional/SecurityControllerTest.php`, initialement prévu ici, n'a pas été créé — couverture rapatriée à 100 % en E2E (cf. §Stratégie de test et `report.md`).

### Fichiers à modifier

| Fichier                                              | Modification                                                      |
|------------------------------------------------------|-------------------------------------------------------------------|
| `config/packages/security.yaml`                      | Firewall `main` : ajouter `remember_me` (signature, `lifetime: 604800`), `login_throttling` (défaut) ; `logout: { path: app_logout, target: app_login }`. `form_login`/`access_control` inchangés. |
| `templates/security.html.twig`                       | Restyler le layout d'auth sur les tokens Nova (`bg-canvas`, `bg-surface`, `text-ink`, accent `iris`, Hanken Grotesk) pour cohérence avec `base.html.twig`. |
| `templates/base.html.twig`                           | Ajouter `data-test="logout"` sur l'ancre de déconnexion (sélecteur requis par l'E2E logout ; le lien vit dans le layout applicatif). |
| `tests/e2e/login.spec.ts`                             | Couverture E2E complète (6 tests, seul niveau de test de la story) : accès anonyme redirigé, happy-path, mauvais identifiants (message neutre, reste sur `/login`), logout (retour login + route protégée), cookie remember-me présent quand la case est cochée **et absent quand elle ne l'est pas**. |
| `composer.json` / `composer.lock`                    | Ajout de `symfony/rate-limiter` (via `composer require`, requis par `login_throttling`). |
| `config/reference.php` / `phpinsights.php`           | Effets de bord de la QA : regénération `rate_limiter.enabled: true` après install du rate-limiter ; normalisation CS-Fixer. |

## Impacts transverses

- **Multi-tenant** : non (mono-utilisateur).
- **Multi-thème** : non.
- **API REST/GraphQL** : non.
- **i18n** : libellés du login et message d'erreur en français, en dur dans le template (pas de catalogue introduit). Le message d'erreur d'auth passe par `error.messageKey|trans(error.messageData, 'security')` (catalogue `security` natif Symfony).
- **Permissions** : firewall `main` existant, aucun nouveau voter. Ajout de `remember_me` + `login_throttling` uniquement.
- **Emails / notifications** : non (pas de « mot de passe oublié »).
- **Migration de données** : aucune. Table `user` déjà migrée ; remember-me par signature = sans schéma.
- **Comportement par défaut** : à chaque session non mémorisée, l'utilisateur voit d'abord l'écran de login avant toute page applicative.

## Ordre d'exécution

1. [ ] `composer require symfony/rate-limiter` (dépendance du throttling).
2. [ ] `config/packages/security.yaml` : ajouter `remember_me` (lifetime 604800), `login_throttling`, `logout.target: app_login`.
3. [ ] Restyler `templates/security.html.twig` sur la DA Nova.
4. [ ] Créer `templates/security/login.html.twig` (formulaire complet + CSRF + case remember-me + erreur).
5. [ ] Vérifier le provisioning : `make db-reset` puis connexion `admin@example.com` / `password`.
6. [ ] Ajouter `data-test="logout"` sur l'ancre de déconnexion de `templates/base.html.twig`.
7. [ ] Étendre `tests/e2e/login.spec.ts` (accès anonyme, mauvais identifiants, logout, remember-me coché/décoché) — seul niveau de test de la story.
8. [ ] QA finale : `make quality` (CS-Fixer + PHPStan) puis `make playwright`.

## Stratégie de test

| Code                                          | Type            | Ce qu'on vérifie                                                                 |
|-----------------------------------------------|-----------------|----------------------------------------------------------------------------------|
| `SecurityController` + firewall `main` + `templates/security/login.html.twig` | E2E (Playwright), 6 tests | `/` anonyme → redirigé vers `/login` ; `h1` « Se connecter » ; login nominal → « Tableau de bord » + hors `/login` ; mauvais identifiants → reste sur login, message neutre sans fuite de champ ; case « rester connecté » cochée → cookie `REMEMBERME` posé, décochée → absent ; logout → retour login + `/` re-protégé. |
| `security.yaml` `login_throttling`            | Manuel / smoke  | Au-delà du seuil de tentatives échouées, blocage temporaire (difficile à automatiser sans horloge — validation manuelle documentée). |

**Hors scope tests pour cette story** :

- Pas de test unitaire dédié : aucun service métier créé, tout repose sur le framework.
- Pas de test fonctionnel PHP (`SecurityControllerTest`) : couverture 100 % E2E, jugé redondant avec l'E2E réel (contre le serveur Symfony CLI) qui exerce déjà accès anonyme, message neutre et logout.
- Le throttling n'est pas testé automatiquement (dépend d'une fenêtre temporelle) — vérification manuelle notée au runbook.

## Risques et mitigations

- **CSRF obligatoire** : `enable_csrf: true` est déjà actif sur `form_login`. Si le template omet `csrf_token('authenticate')`, la connexion échoue silencieusement (« Invalid CSRF token »). Mitigation : l'inclure explicitement, couvert par le test E2E nominal.
- **Contrat du test E2E existant** : `login.spec.ts` s'appuie sur les `name` `_username`/`_password` (défauts `form_login`) et un `h1` « Se connecter ». Mitigation : conserver ces `name` et ce titre dans le nouveau template ; ajouter les `data-test` en complément sans casser les sélecteurs existants.
- **Dette provisioning (fixtures-only)** : changer le mot de passe en usage réel passe par un rechargement des fixtures, qui réinitialise la base (projets + tokens perdus). Acté au pitch, évolution V2 = commande console. Pas de mitigation code dans cette story.
- **`symfony/rate-limiter` absent** : oublier le `composer require` fait échouer le boot avec `login_throttling` configuré. Mitigation : étape 1 de l'ordre d'implémentation.
- **Cohérence flash-messages** : `common/flash-messages.html.twig` utilise une palette Flowbite gris/violet (pas strictement Nova). Hors scope de cette story — ne pas le refactorer ici.

## Questions ouvertes

- **Durée du remember-me** : retenue à **1 semaine (604800 s)** par défaut (recommandation confirmée par défaut, l'utilisateur étant absent au moment du plan). → à confirmer/ajuster à l'implémentation si besoin.
- **DA du login** : retenue **alignement sur Nova** (restyle de `security.html.twig`). → à confirmer si l'utilisateur préfère un layout minimal.
- **`data-test` sur le formulaire** : ajouter les attributs `data-test` (convention CLAUDE.md) en plus des `name` — à faire tant que ça ne casse pas le `login.spec.ts` actuel basé sur `name`/`h1`.
