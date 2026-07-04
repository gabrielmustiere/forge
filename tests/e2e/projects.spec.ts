import { test, expect, type Page } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/login');
  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.click('[data-test="login-submit"]');
  await expect(page).not.toHaveURL(/\/login/);
}

// Parcours complet : déclaration d'un projet puis suppression confirmée via l'UI.
test('declare then delete a project', async ({ page }) => {
  await login(page);

  const repoUrl = `https://github.com/acme-e2e/widget-${Date.now()}`;
  const name = repoUrl.replace('https://github.com/', '');

  await page.goto('/projects/new');
  await page.click('[data-test="provider-github"]');
  await page.fill('[data-test="project-url"]', repoUrl);
  await page.fill('[data-test="project-token"]', 'ghp_e2e_token');
  await page.click('[data-test="project-submit"]');

  await expect(page).toHaveURL(/\/projects$/);
  const row = page.locator('[data-test="project-row"]', { hasText: name });
  await expect(row).toBeVisible();
  // La déclaration déclenche la vérification : un badge de statut est présent sur la ligne.
  await expect(row.locator('[data-test="project-status"]')).toBeVisible();

  await row.locator('[data-test="project-delete"]').click();
  await expect(page.locator('[data-test="project-delete-modal"]')).toBeVisible();
  await page.click('[data-test="project-delete-confirm"]');

  await expect(page.locator('[data-test="project-row"]', { hasText: name })).toHaveCount(0);
});

// Le nom est pré-rempli (owner/repo) à partir de l'URL saisie.
test('name is prefilled from the URL', async ({ page }) => {
  await login(page);

  await page.goto('/projects/new');
  await page.fill('[data-test="project-url"]', 'https://github.com/acme/prefill-demo');

  await expect(page.locator('[data-test="project-name"]')).toHaveValue('acme/prefill-demo');
});

// Une URL incohérente avec le provider est refusée avec un message clair.
test('provider/host mismatch is rejected', async ({ page }) => {
  await login(page);

  await page.goto('/projects/new');
  await page.click('[data-test="provider-github"]');
  await page.fill('[data-test="project-url"]', 'https://gitlab.com/acme/widget');
  await page.fill('[data-test="project-token"]', 'ghp_e2e_token');
  await page.click('[data-test="project-submit"]');

  await expect(page.locator('[data-test="project-form"]')).toContainText('ne correspond pas au provider');
});
