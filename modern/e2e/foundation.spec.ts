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

test('cart displays an honest empty state before any product is added', async ({ page }) => {
  await page.goto('/cart');
  await expect(page.getByRole('heading', { name: 'Your bag' })).toBeVisible();
  await expect(page.getByText('Your bag is empty')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Continue shopping' })).toHaveAttribute('href', '/shop');
});
