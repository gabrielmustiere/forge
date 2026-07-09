import { test, expect, type Page } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/login');
  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.click('[data-test="login-submit"]');
  await expect(page).not.toHaveURL(/\/login/);
}

async function declareProject(page: Page): Promise<string> {
  const repoUrl = `https://github.com/acme-e2e/interview-${Date.now()}`;
  const name = repoUrl.replace('https://github.com/', '');

  await page.goto('/projects/new');
  await page.click('[data-test="provider-github"]');
  await page.fill('[data-test="project-url"]', repoUrl);
  await page.fill('[data-test="project-token"]', 'ghp_e2e_token');
  await page.click('[data-test="project-submit"]');
  await expect(page).toHaveURL(/\/projects$/);

  await page.locator('[data-test="project-row"]', { hasText: name }).locator('[data-test="project-open"]').click();
  await expect(page).toHaveURL(/\/projects\/\d+$/);

  return name;
}

// Smoke : la précondition de clone (règle 1) est appliquée jusque dans le navigateur, et la garde
// métier remonte un message lisible via le Live Component. Un projet fraîchement déclaré n'est pas
// cloné (aucun worker ne tourne en E2E, comme pour le clone) : le bouton « Exprimer un besoin » est
// donc désactivé, et tenter de démarrer une interview affiche une erreur plutôt que de planter.
test('interview button is gated by the clone precondition and the guard is enforced', async ({ page }) => {
  await login(page);
  await declareProject(page);

  // Le bouton existe mais est désactivé tant que le dépôt n'est pas cloné.
  const gatedButton = page.locator('button[data-test="project-interview"]');
  await expect(gatedButton).toBeVisible();
  await expect(gatedButton).toBeDisabled();

  // La page d'interview reste accessible en direct : le composant monte, le formulaire s'affiche.
  const projectUrl = page.url();
  await page.goto(`${projectUrl}/interview`);
  await expect(page.locator('[data-test="interview-start"]')).toBeVisible();

  // Exprimer un besoin sur un projet non cloné : la garde refuse et affiche une erreur lisible.
  await page.fill('[data-test="interview-message-input"]', 'Je veux exporter mes factures au format comptable');
  await page.click('[data-test="interview-submit"]');
  await expect(page.locator('[data-test="interview-error"]')).toBeVisible();
  await expect(page.locator('[data-test="interview-error"]')).toContainText(/clon/i);
});
