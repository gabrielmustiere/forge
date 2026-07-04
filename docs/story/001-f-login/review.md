# Review — Connexion / déconnexion locale (login)

> Date : 2026-07-04
> Stack : symfony
> Périmètre : working tree + staged (~10 fichiers de code + 1 test nouveau, ~300 lignes diff pertinentes)
> Référence d'intention : `docs/story/001-f-login/plan.md` + `docs/story/001-f-login/pitch.md`

## Bloquants

- _(aucun)_

## Importants

- _(aucun)_

## Mineurs

- [x] **[TEST] Critère « remember-me décochée → pas de cookie » couvert** — `tests/e2e/login.spec.ts` — test négatif ajouté (« login without remember me does not set a persistent cookie »). Critère d'acceptation fermé.
- [ ] **[ROBUSTESSE] Le message neutre masque aussi le throttling** — `templates/security/login.html.twig:16` — « Identifiants invalides. » s'affiche pour toute erreur d'auth, y compris `TooManyLoginAttempts`. **Conservé volontairement** : c'est le comportement imposé par la règle de neutralité (règle métier 4 du pitch). Noté pour arbitrage produit ultérieur si un indice « réessayez plus tard » devient souhaitable.
- [x] **[ROBUSTESSE] Test fonctionnel supprimé** — `tests/Functional/SecurityControllerTest.php` retiré à la demande (jugé redondant avec l'E2E). Ses scénarios non couverts (accès anonyme → redirection, mauvais identifiants → message neutre) ont été **rapatriés dans l'E2E**. La remarque sur la recréation de schéma devient sans objet.
- [ ] **[DOC] Fichiers hors périmètre dans le diff** — `config/reference.php`, `phpinsights.php` — effets de bord légitimes (regen `rate_limiter.enabled: true` après install ; normalisation CS-Fixer). À inclure ou exclure consciemment au `/commit`.

## Divergence avec le plan (à réaligner via `/forge:sync`)

- Le `plan.md` (§Stratégie de test) prévoyait un test fonctionnel PHP `SecurityControllerTest` **+** l'E2E. La couverture est désormais **100 % E2E** (6 tests), sur décision utilisateur. À répercuter dans `plan.md` au `/forge:sync`.

## Points positifs

- **Zéro dette de sécurité** : CSRF présent (`csrf_token('authenticate')` + `enable_csrf`), message d'erreur neutre (ne révèle pas le champ fautif), aucun secret en dur, `access_control` global préservé.
- **Suppressions saines** de l'ancien template : lien mort « Mot de passe oublié ? » et affichage des identifiants de test en clair retirés.
- **Retrait de `data-controller="csrf-protection"` sans régression** — vérifié : `assets/controllers/csrf_protection_controller.js` opère via des listeners `submit` globaux et cible aussi `input[name="_csrf_token"]` ; l'E2E de login (contre le vrai serveur) confirme le CSRF de bout en bout.
- **Couverture E2E consolidée (6 tests)** : accès anonyme, happy-path, message neutre, remember-me coché/décoché, logout + re-protection. Sélecteurs `data-test` stables.
- **Aucune migration, aucune entité touchée** : la feature s'appuie exclusivement sur les mécanismes natifs `security-bundle` (remember_me signature, login_throttling), remember-me sans table.

## Verdict

- Bloquants restants : 0 / 0
- Importants restants : 0 / 0
- Mineurs restants : 2 / 4 (les 2 restants sont des décisions assumées, pas des correctifs)
- Statut : **READY TO COMMIT**

`/commit` pour commit et push. Penser à `/forge:sync` pour réaligner le `plan.md` (couverture désormais 100 % E2E).

## Hors review (à vérifier en environnement réel)

- **Throttling** (`login_throttling: max_attempts: 5`) : non testé automatiquement (fenêtre temporelle). Vérifier manuellement qu'au-delà de 5 tentatives échouées le blocage temporaire s'active.
- **Persistance réelle du remember-me** : l'E2E vérifie la présence du cookie, pas la survie à une fermeture/réouverture navigateur — à confirmer à l'usage.
- **E2E** : nécessite le serveur Symfony CLI sur `forge-board.wip` (proxy attaché) et la DB dev seedée (`make db-reset`).
