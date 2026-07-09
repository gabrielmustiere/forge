import { test, expect, type Page } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/login');
  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.click('[data-test="login-submit"]');
  await expect(page).not.toHaveURL(/\/login/);
}

// Smoke : le bouton de clone est présent sur la fiche projet et son clic bascule l'état de
// clone hors de « non cloné ». En CI aucun worker Messenger ne consomme le transport async :
// le job est enqueué mais pas exécuté (aucun `git` réel), l'état reste donc « Clonage… ».
test('clone button triggers the clone from a project page', async ({ page }) => {
  await login(page);

  const repoUrl = `https://github.com/acme-e2e/clone-${Date.now()}`;
  const name = repoUrl.replace('https://github.com/', '');

  await page.goto('/projects/new');
  await page.click('[data-test="provider-github"]');
  await page.fill('[data-test="project-url"]', repoUrl);
  await page.fill('[data-test="project-token"]', 'ghp_e2e_token');
  await page.click('[data-test="project-submit"]');
  await expect(page).toHaveURL(/\/projects$/);

  // Ouvrir la fiche du projet fraîchement déclaré.
  await page.locator('[data-test="project-row"]', { hasText: name }).locator('[data-test="project-open"]').click();
  await expect(page).toHaveURL(/\/projects\/\d+$/);

  // Le projet n'est pas encore cloné : bouton « Cloner » visible, badge « Non cloné ».
  const button = page.locator('[data-test="project-clone"]');
  await expect(button).toBeVisible();
  await expect(button).toContainText('Cloner');
  await expect(page.locator('[data-test="project-clone-status"]')).toHaveAttribute('data-status', 'not_cloned');

  // Déclencher le clone : l'état quitte « non cloné » (Clonage… en CI, ou Cloné si un worker tourne).
  await button.click();
  await expect(page.locator('[data-test="project-clone-status"]')).toHaveAttribute('data-status', /cloning|cloned/);
});
