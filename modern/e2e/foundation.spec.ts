import { expect, test } from '@playwright/test';

test('modern home renders the F2B brand and legacy hero asset', async ({ page }) => {
  await page.goto('/');
  await expect(page.getByRole('heading', { name: 'Wear your style.' })).toBeVisible();
  await expect(page.getByRole('img', { name: 'Model in a neutral outfit seated beside a studio window' })).toHaveAttribute('src', '/images/branding/hero-fashion.jpg');
});

test('nested client route refreshes to the application', async ({ page }) => {
  await page.goto('/shop?category=t-shirts&sort=newest');
  await expect(page.getByRole('heading', { name: 'Shop all' })).toBeVisible();
  await page.reload();
  await expect(page.getByRole('heading', { name: 'Shop all' })).toBeVisible();
  await expect(page).toHaveURL(/category=t-shirts.*sort=newest/);
});
