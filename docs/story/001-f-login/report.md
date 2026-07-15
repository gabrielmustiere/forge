# Report — Verrouiller Forge Board derrière une connexion locale mono-utilisateur

> **But** : constater l'écart entre l'intention et le code livré — écarts, dette, suites.
> **Registre** : factuel
> **Story** : `docs/story/001-f-login/`
> **Amont** : `pitch.md` · `plan.md` · `review.md`

## Synthèse

Feature livrée à ~95 % de conformité au plan : la barrière de sécurité native Symfony est finalisée (template de login Nova, `remember_me` par signature, `login_throttling`, redirection de logout, `symfony/rate-limiter` ajouté), sans entité ni migration comme prévu. **Unique écart structurant** : le test fonctionnel PHP `SecurityControllerTest` prévu au plan n'a pas été créé — la couverture est désormais 100 % E2E (6 tests Playwright), sur décision utilisateur, scénarios rapatriés. 8/8 critères d'acceptation couverts, dont 2 avec vérification manuelle en attente (throttling, survie réelle du remember-me). Review : 0 bloquant, 0 important, 2 mineurs restants (décisions assumées). Statut review : PRÊT À COMMITER.

## Périmètre livré

### Fichiers créés

| Fichier                                              | Rôle                                                              | Prévu dans le plan |
|------------------------------------------------------|-------------------------------------------------------------------|--------------------|
| `templates/security/login.html.twig`                 | Formulaire de login Nova : `h1` « Se connecter », champs `_username`/`_password`, case `_remember_me`, `csrf_token('authenticate')`, bloc erreur neutre, `last_username`, `data-test` complets. | Oui |

### Fichiers modifiés

| Fichier                                              | Modification                                                      | Prévu dans le plan |
|------------------------------------------------------|-------------------------------------------------------------------|--------------------|
| `config/packages/security.yaml`                      | Firewall `main` : `remember_me` (signature `%kernel.secret%`, `lifetime: 604800`), `login_throttling` (`max_attempts: 5`), `logout.target: app_login`. | Oui |
| `templates/security.html.twig`                       | Restyle du layout d'auth sur les tokens Nova (canvas/surface/ink/iris). | Oui |
| `composer.json` / `composer.lock`                    | Ajout de `symfony/rate-limiter` (requis par `login_throttling`). | Oui |
| `tests/e2e/login.spec.ts`                            | Étendu à 6 tests : accès anonyme, happy-path, message neutre, remember-me coché **et décoché**, logout + re-protection. | Oui (couverture étendue) |
| `templates/base.html.twig`                           | Ajout de `data-test="logout"` sur l'ancre de déconnexion (sélecteur requis par l'E2E logout). | Non (ajout — cf. §Ajouts non prévus) |
| `config/reference.php`                               | Regénération : `rate_limiter.enabled: true` après install de `symfony/rate-limiter`. | Non (effet de bord — cf. §Ajouts non prévus) |
| `phpinsights.php`                                    | Normalisation CS-Fixer. | Non (effet de bord — cf. §Ajouts non prévus) |

## Écarts avec le plan

### Écarts volontaires

| Prévu                                       | Réalisé                                  | Raison                                                       |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| `tests/Functional/SecurityControllerTest.php` (WebTestCase : redirection anonyme, login OK/KO, logout) en complément de l'E2E (§Fichiers à créer, §Ordre étape 6, §Stratégie de test). | Aucun test fonctionnel PHP. Couverture 100 % E2E (6 tests Playwright), scénarios anonyme + message neutre + logout rapatriés dans `login.spec.ts`. | Décision utilisateur : test fonctionnel jugé redondant avec l'E2E (review.md, mineur ROBUSTESSE). La remarque du plan sur la recréation de schéma devient sans objet. |

### Non implémenté

| Élément prévu                               | Raison                                   | Action requise                                               |
|---------------------------------------------|------------------------------------------|--------------------------------------------------------------|
| Aucun | — | — |

### Ajouts non prévus

| Élément ajouté                              | Raison                                                                              |
|---------------------------------------------|-------------------------------------------------------------------------------------|
| `data-test="logout"` sur `templates/base.html.twig` | Sélecteur stable nécessaire au test E2E de logout ; l'ancre de déconnexion vit dans le layout applicatif, hors du périmètre de fichiers listé au plan. |
| 6e test E2E `login without remember me does not set a persistent cookie` | Couverture du critère négatif « case décochée → pas de cookie » ; le plan ne listait que le cas coché. |
| `config/reference.php` (`rate_limiter.enabled: true`) | Effet de bord légitime de `composer require symfony/rate-limiter` (regénération du fichier de référence). |
| `phpinsights.php` | Normalisation CS-Fixer, effet de bord de la QA finale. |

## Tests

| Code                                                        | Type prévu       | Type réalisé                                  | Statut                       |
|-------------------------------------------------------------|------------------|-----------------------------------------------|------------------------------|
| `SecurityController` + firewall `main`                      | Functional (PHP) | Aucun test PHP — rapatrié en E2E              | Écart volontaire (cf. §Écarts) |
| `templates/security/login.html.twig` + parcours complet     | E2E (Playwright) | E2E, 6 tests (`login.spec.ts`)               | Fait — couverture étendue    |
| Critère « remember-me décochée → pas de cookie »            | Non prévu        | E2E (test négatif ajouté)                     | Fait — couverture étendue    |
| `security.yaml` `login_throttling`                          | Manuel / smoke   | Non couvert automatiquement                   | Conforme — hors scope assumé (vérification manuelle) |

## Dette technique identifiée

Issus de la review (mineurs non traités) :

1. **[ROBUSTESSE] Message neutre masquant le throttling** — `templates/security/login.html.twig:24` — « Identifiants invalides. » s'affiche aussi pour `TooManyLoginAttempts`. Conservé volontairement (règle métier 4 « message neutre »). À arbitrer côté produit si un indice « réessayez plus tard » devient souhaitable.
2. **[DOC] Fichiers d'effet de bord dans le diff** — `config/reference.php`, `phpinsights.php` — à inclure/exclure consciemment au `/forge:commit`.

Au-delà de la review :

3. **Throttling non testé automatiquement** — `login_throttling: max_attempts: 5` : vérifier manuellement qu'au-delà de 5 tentatives échouées le blocage temporaire s'active (fenêtre temporelle non automatisable). **Critique** à valider avant mise en usage réel.
4. **Persistance réelle du remember-me** — l'E2E vérifie la présence du cookie `REMEMBERME`, pas la survie à une fermeture/réouverture navigateur : à confirmer à l'usage.
5. **Provisioning fixtures-only** — changer le mot de passe en usage réel passe par un rechargement des fixtures (réinitialise projets + tokens). Acté au pitch, évolution V2 = commande console `app:user:create`.

## Critères d'acceptation

Reprise des critères du `pitch.md` :

- [x] Non connecté, une requête vers n'importe quelle URL applicative redirige vers l'écran de login (E2E `anonymous access is redirected to login` + `access_control ^/ → ROLE_USER`).
- [x] L'écran de login s'affiche (email, mot de passe, case « rester connecté ») (`templates/security/login.html.twig`).
- [x] Des identifiants valides connectent l'utilisateur et le redirigent vers l'accueil du board (E2E `login flow` → « Tableau de bord »).
- [x] Des identifiants invalides restent sur le login avec un message neutre, sans préciser le champ fautif (E2E `invalid credentials show a neutral error`).
- [x] Case « rester connecté » cochée maintient la session, décochée non (E2E `remember me sets a persistent cookie` + `login without remember me…`). _Réserve : présence du cookie vérifiée, survie réelle à la fermeture navigateur non automatisée (cf. dette #4)._
- [x] Après déconnexion, retour au login et routes de nouveau inaccessibles (E2E `logout returns to login and re-protects the app`).
- [x] Au-delà d'un seuil de tentatives échouées, blocage temporaire (`login_throttling: max_attempts: 5`). _Réserve : mécanisme configuré, vérification manuelle en attente (cf. dette #3)._
- [x] Les fixtures créent le compte `admin@example.com` / `password` (`ROLE_USER`) et permettent la connexion après rechargement (`DataFixtures\AppFixtures`, inchangé).

## Leçons apprises

- **Arbitrage test fonctionnel vs E2E à trancher au plan, pas après** : le plan doublonnait la couverture (PHP fonctionnel + E2E) là où l'E2E réel (contre serveur Symfony CLI) couvrait déjà anonyme/message neutre/logout. Décider du niveau de test unique en amont évite d'écrire puis retirer du code de test.
- **Les sélecteurs E2E débordent du périmètre de fichiers listé** : un test de logout impose un `data-test` sur le layout applicatif (`base.html.twig`), fichier hors de la table « Fichiers à modifier ». Anticiper les points d'ancrage `data-test` transverses quand on planifie un parcours multi-écrans.
- **Effets de bord d'un `composer require` à prévoir dans le diff** : installer `symfony/rate-limiter` regénère `config/reference.php` (`rate_limiter.enabled: true`). Mentionner ces regénérations attendues dans le plan évite qu'elles apparaissent comme du bruit à la review.
- **Un message d'erreur neutre a un angle mort throttling** : la règle de neutralité masque aussi « trop de tentatives ». Documenter ce trade-off UX dès le pitch pour ne pas le redécouvrir en review.
