import { test, expect, type Page } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/login');
  await page.fill('[data-test="login-email"]', 'admin@example.com');
  await page.fill('[data-test="login-password"]', 'password');
  await page.click('[data-test="login-submit"]');
  await expect(page).not.toHaveURL(/\/login/);
}

// Déclare un projet dont l'URL contient `board` : le reader neutralisé en env test
// renvoie alors un pipeline complet (quatre colonnes peuplées + une story « À vérifier »).
async function declareBoardProject(page: Page): Promise<void> {
  const repoUrl = `https://github.com/acme-e2e/board-${Date.now()}`;

  await page.goto('/projects/new');
  await page.click('[data-test="provider-github"]');
  await page.fill('[data-test="project-url"]', repoUrl);
  await page.fill('[data-test="project-token"]', 'ghp_e2e_token');
  await page.click('[data-test="project-submit"]');
  await expect(page).toHaveURL(/\/projects$/);

  const name = repoUrl.replace('https://github.com/', '');
  await page.locator('[data-test="project-row"]', { hasText: name }).locator('[data-test="project-open"]').click();
}

test('board shows the four ordered columns, counts and the banner', async ({ page }) => {
  await login(page);
  await declareBoardProject(page);

  await expect(page.locator('[data-test="board"]')).toBeVisible();
  await expect(page.locator('[data-test="board-column"]')).toHaveCount(4);

  // Compteurs par colonne (Cadrage 1, Planifié 1, Review 1, Livré 2).
  await expect(page.locator('[data-stage="livre"] [data-test="column-count"]')).toHaveText('2');

  // Bandeau « À vérifier » distinct, sous les colonnes.
  await expect(page.locator('[data-test="board-banner"]')).toBeVisible();
  await expect(page.locator('[data-test="banner-count"]')).toHaveText('1');
});

test('filters cards by tag and sorts by activity, client-side', async ({ page }) => {
  await login(page);
  await declareBoardProject(page);

  await expect(page.locator('[data-test="board"]')).toBeVisible();

  // Compteur de la colonne Livré avant filtre : deux cartes (007-t-livre + 003-f-livre-complet).
  const livreCount = page.locator('[data-stage="livre"] [data-test="column-count"]');
  await expect(livreCount).toHaveText('2');

  // Filtre par tag « dette » : un thème isolé à travers le pipeline (Review + Livré).
  await page.locator('[data-test="filter-tag"]', { hasText: 'dette' }).click();
  await expect(page.locator('[data-test="story-card"][data-story-id="005-r-review"]')).toBeVisible();
  await expect(page.locator('[data-test="story-card"][data-story-id="007-t-livre"]')).toBeVisible();
  // Une carte hors du tag est masquée (le filtrage n'affecte que l'affichage).
  await expect(page.locator('[data-test="story-card"][data-story-id="003-f-livre-complet"]')).toBeHidden();
  // Le compteur suit le filtre : Livré ne montre plus qu'une carte (007-t-livre porte « dette »).
  await expect(livreCount).toHaveText('1');

  // Retirer le filtre : tout réapparaît et le compteur revient au total.
  await page.locator('[data-test="filter-tag"]', { hasText: 'dette' }).click();
  await expect(page.locator('[data-test="story-card"][data-story-id="003-f-livre-complet"]')).toBeVisible();
  await expect(livreCount).toHaveText('2');

  // Tri par activité : dans la colonne Livré, la story mise à jour le plus récemment passe en tête.
  await page.locator('[data-test="sort-updated"]').click();
  const livre = page.locator('[data-stage="livre"] [data-test="story-card"]');
  await expect(livre.first()).toHaveAttribute('data-story-id', '003-f-livre-complet');
  await expect(livre.last()).toHaveAttribute('data-story-id', '007-t-livre');
});

test('the drawer exposes the story consolidated changelog', async ({ page }) => {
  await login(page);
  await declareBoardProject(page);

  // Une story planifiée porte deux entrées de changelog dans son metadata.
  await page.locator('[data-test="story-card"][data-story-id="010-f-planifie"]').click();
  await expect(page.locator('[data-test="story-drawer"]')).toHaveAttribute('aria-hidden', 'false');

  const changelog = page.locator('[data-test="drawer-changelog"]');
  await expect(changelog).toBeVisible();
  await changelog.locator('summary').click();
  await expect(page.locator('[data-test="changelog-entry"]')).toHaveCount(2);
});

test('clicking a card opens the drawer, lists documents and renders markdown', async ({ page }) => {
  await login(page);
  await declareBoardProject(page);

  // Une story livrée porte ses quatre documents.
  await page.locator('[data-test="story-card"][data-story-id="003-f-livre-complet"]').click();

  const drawer = page.locator('[data-test="story-drawer"]');
  await expect(drawer).toHaveAttribute('aria-hidden', 'false');

  // La liste des documents est présentée d'abord.
  await expect(page.locator('[data-test="drawer-doc"]')).toHaveCount(4);

  // Le contenu du document (chargé par Turbo) est rendu en markdown : le vrai titre `# H1` apparaît.
  await expect(page.locator('[data-test="doc-content"]')).toContainText('Titre réel de la story');

  // Choisir un autre document recharge le contenu sans quitter la page.
  await page.locator('[data-test="drawer-doc"]').last().click();
  await expect(page.locator('[data-test="doc-content"]')).toBeVisible();

  // Fermeture du drawer.
  await page.click('[data-test="drawer-close"]');
  await expect(drawer).toHaveAttribute('aria-hidden', 'true');
});
