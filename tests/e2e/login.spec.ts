import { test, expect } from '@playwright/test';

test('login flow', async ({ page }) => {
  await page.goto('/login');

  await expect(page.locator('h1')).toContainText('Se connecter');

  await page.fill('input[name="_username"]', 'admin@example.com');
  await page.fill('input[name="_password"]', 'password');
  await page.click('button[type="submit"]');

  await expect(page).not.toHaveURL(/\/login/);
  await expect(page.locator('body')).toContainText('Tableau de bord');
});
