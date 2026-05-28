# Conventions E2E Playwright

Généralisables quel que soit le framework si le projet utilise Playwright pour ses tests E2E.

- **Nommage** : `e2e/{feature}-{area}.spec.ts` (ex: `articles-admin.spec.ts`, `checkout-shop.spec.ts`).
- **Login via `storageState`** (projet `setup` dans `playwright.config.ts`), pas de `beforeEach` login.
- **Sessions non authentifiées** : `test.use({ storageState: { cookies: [], origins: [] } })` en haut du fichier.
- **Pas de `waitForTimeout`** — utiliser `toBeVisible({ timeout })`, `toHaveCount()`, `waitForURL()`.
- **CRUD séquentiel** dans `test.describe.serial` avec identifiants uniques (`Date.now().toString(36)`).
- **Sélecteurs `data-test-*`** plutôt que sélecteurs CSS fragiles.
- **Configuration** : ajouter le projet Playwright dans `playwright.config.ts` et le script npm dans `package.json`.

Les patterns spécifiques au framework (par exemple la suppression via modale Bootstrap en admin Sylius) sont documentés dans la référence stack `<stack>.md` (déjà chargée via la détection).

## Mapping code → niveau de test

| Code écrit                | Test requis                             |
|---------------------------|-----------------------------------------|
| Service / Command Handler | Unit (mocks des dépendances)            |
| Repository custom         | Functional avec BDD de test             |
| EventSubscriber / Listener| Unit                                    |
| Workflow callback         | Unit ou fonctionnel                     |
| Commande Console          | CommandTester                           |
| Template / UI / parcours  | E2E (Playwright ou équivalent)          |
