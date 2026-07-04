import { test, expect } from '@playwright/test';

// Accès anonyme à une route applicative → redirection vers l'écran de login.
test('anonymous access is redirected to login', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);
});

// Happy-path : parcours de connexion réel jusqu'au tableau de bord.
test('login flow', async ({ page }) => {
  await page.goto('/login');

  await expect(page.locator('h1')).toContainText('Se connecter');

  await page.fill('input[name="_username"]', 'admin@example.com');
  await page.fill('input[name="_password"]', 'password');
  await page.click('button[type="submit"]');

  await expect(page).not.toHaveURL(/\/login/);
  await expect(page.locator('body')).toContainText('Tableau de bord');
});

// Mauvais identifiants → message neutre (ne révèle pas le champ fautif), reste sur le login.
test('invalid credentials show a neutral error', async ({ page }) => {
  await page.goto('/login');

  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'mauvais-mot-de-passe');
  await page.click('[data-test="login-submit"]');

  await expect(page).toHaveURL(/\/login/);
  await expect(page.locator('[data-test="login-error"]')).toContainText('Identifiants invalides');
});

// « Rester connecté » coché → cookie de persistance posé par Symfony.
test('remember me sets a persistent cookie', async ({ page, context }) => {
  await page.goto('/login');

  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.check('[data-test="login-remember"]');
  await page.click('[data-test="login-submit"]');

  await expect(page).not.toHaveURL(/\/login/);

  const cookies = await context.cookies();
  expect(cookies.some((c) => c.name === 'REMEMBERME')).toBeTruthy();
});

// Sans cocher « rester connecté » → aucun cookie de persistance.
test('login without remember me does not set a persistent cookie', async ({ page, context }) => {
  await page.goto('/login');

  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.click('[data-test="login-submit"]');

  await expect(page).not.toHaveURL(/\/login/);

  const cookies = await context.cookies();
  expect(cookies.some((c) => c.name === 'REMEMBERME')).toBeFalsy();
});

// Déconnexion via l'UI → retour à l'écran de login, puis l'app est de nouveau protégée.
test('logout returns to login and re-protects the app', async ({ page }) => {
  await page.goto('/login');
  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.click('[data-test="login-submit"]');
  await expect(page).not.toHaveURL(/\/login/);

  // dispatchEvent : la barre de debug Symfony (dev) recouvre le pied de sidebar ;
  // on dispatche le clic directement sur l'ancre pour déclencher la navigation.
  await page.locator('[data-test="logout"]').dispatchEvent('click');
  await expect(page).toHaveURL(/\/login/);

  // Une requête vers une route applicative renvoie de nouveau vers le login.
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);
});
