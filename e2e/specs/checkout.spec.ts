import { test, expect } from '../fixtures/browser';
import { mutationGateReason, readE2EConfig } from '../config/env';
import { syntheticCustomer } from '../fixtures/data';
import { readSavedOrder } from '../fixtures/db';
import { ProductPage } from '../pages/ProductPage';
import { CheckoutPage } from '../pages/CheckoutPage';

test.use({ screenshot: 'off', trace: 'off' });

test.describe('checkout @checkout', () => {
  test('E2E-07 required field validation keeps the session cart', async ({ page }) => {
    await page.goto('shop.php');
    const productHref = await page.locator('.product-card__name a').first().getAttribute('href');
    const product = new ProductPage(page);
    await product.open(productHref!);
    await product.chooseFirstValidSize();
    await product.addToCart();
    await page.goto('checkout.php');
    await expect(page.locator('#checkout-form')).toBeVisible();
    await page.locator('#email-address').fill('not-an-email');
    await page.getByRole('button', { name: /place order/i }).click();
    await expect(page.locator('#email-address')).toBeFocused();
    await expect(page).toHaveURL(/checkout\.php/);
    await page.goto('cart.php');
    await expect(page.locator('.cart-item')).toHaveCount(1);
  });

  test('E2E-08 valid checkout creates a synthetic order in the E2E database', async ({ page }) => {
    const reason = mutationGateReason();
    test.skip(Boolean(reason), reason ?? undefined);
    await page.goto('shop.php');
    const productHref = await page.locator('.product-card__name a').first().getAttribute('href');
    const product = new ProductPage(page);
    await product.open(productHref!);
    await product.chooseFirstValidSize();
    await product.addToCart();

    const checkout = new CheckoutPage(page);
    await checkout.open();
    await checkout.fillCustomer(syntheticCustomer);
    await checkout.submit();
    await expect(page).toHaveURL(/confirmation\.php\?order=\d+/);
    await expect(page.locator('#confirmation-title')).toHaveText('ORDER PLACED');
    const orderId = Number(new URL(page.url()).searchParams.get('order'));
    expect(orderId).toBeGreaterThan(0);
    await expect(page.locator('.confirmation-summary__items .confirmation-item')).toHaveCount(1);
    await expect(page.locator('.confirmation-summary__total')).toContainText('$');

    const saved = await readSavedOrder(orderId);
    if (saved) {
      expect(saved.order?.status).toBe('ORDER_PLACED');
      expect(saved.order?.full_name).toBe(syntheticCustomer.full_name);
      expect(saved.items).toHaveLength(1);
    } else if (readE2EConfig().dbUser) {
      throw new Error('E2E_DB_USER is configured but read-only order verification returned no result.');
    }
  });
});

test('empty cart cannot open checkout and returns to cart', async ({ page }) => {
  await page.goto('checkout.php');
  await expect(page).toHaveURL(/cart\.php/);
  await expect(page.locator('#cart-empty')).toBeVisible();
});
