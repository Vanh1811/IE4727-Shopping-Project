import { test, expect } from '../fixtures/browser';

test.describe('read-only smoke @smoke', () => {
  test('E2E-01 homepage renders and Shop navigation works', async ({ page }) => {
    await page.goto('index.php');
    await expect(page.locator('h1').first()).toBeVisible();
    const shopLink = page.getByRole('link', { name: /^shop$/i }).first();
    await expect(shopLink).toBeVisible();
    await shopLink.click();
    await expect(page).toHaveURL(/shop\.php/);
    await expect(page.getByRole('heading', { name: 'Filters' })).toBeVisible();
  });

  test('E2E-02 shop renders a non-empty seeded catalogue', async ({ page }) => {
    await page.goto('shop.php');
    await expect(page.getByRole('heading', { name: 'Filters' })).toBeVisible();
    await expect(page.locator('.product-card.shop-product-card').first()).toBeVisible();
    await expect(page.locator('.product-card__name a').first()).toHaveAttribute('href', /product\.php\?id=\d+/);
    await expect(page.locator('body')).not.toContainText(/Fatal error|Uncaught (?:Error|Exception)/i);
  });

  test('E2E-03 first catalogue product opens with title and add-to-cart control', async ({ page }) => {
    await page.goto('shop.php');
    const firstProduct = page.locator('.product-card__name a').first();
    await expect(firstProduct).toBeVisible();
    const expectedName = (await firstProduct.innerText()).trim();
    await firstProduct.click();
    await expect(page.locator('#product-title')).toHaveText(expectedName);
    await expect(page.locator('#add-to-cart')).toBeVisible();
  });

  test('E2E-04 invalid product identifiers render the not-found state', async ({ page }) => {
    await page.goto('product.php?id=0');
    await expect(page.getByRole('heading', { name: 'PRODUCT NOT FOUND' })).toBeVisible();
    await page.goto('product.php?id=999999999');
    await expect(page.getByRole('heading', { name: 'PRODUCT NOT FOUND' })).toBeVisible();
    await expect(page.locator('body')).not.toContainText(/Fatal error|Uncaught (?:Error|Exception)/i);
  });

  test('E2E-05 a fresh browser context sees an empty cart', async ({ page }) => {
    await page.goto('cart.php');
    await expect(page.getByRole('heading', { name: 'YOUR CART' })).toBeVisible();
    await expect(page.locator('#cart-empty')).toBeVisible();
  });

  test('E2E-17 product image loading failures are reported separately', async ({ page }, testInfo) => {
    await page.goto('shop.php');
    const imageStates = await page.locator('.product-card__image img').evaluateAll(async (nodes) => Promise.all(
      nodes.map((node) => new Promise<{ src: string; ok: boolean }>((resolve) => {
        const image = node as HTMLImageElement;
        const finish = () => resolve({ src: new URL(image.currentSrc || image.src, location.href).origin + new URL(image.currentSrc || image.src, location.href).pathname, ok: image.complete && image.naturalWidth > 0 });
        if (image.complete) return finish();
        image.addEventListener('load', finish, { once: true });
        image.addEventListener('error', finish, { once: true });
        setTimeout(finish, 5000);
      })),
    ));
    const failures = imageStates.filter((image) => !image.ok).map((image) => image.src);
    if (failures.length) {
      await testInfo.attach('external-product-image-health.txt', { body: failures.join('\n'), contentType: 'text/plain' });
      testInfo.annotations.push({ type: 'image-health', description: `${failures.length} product image(s) failed; core page render remains a separate check.` });
    }
    await expect(page.locator('.product-card.shop-product-card').first()).toBeVisible();
  });
});
