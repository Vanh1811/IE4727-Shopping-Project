import { test, expect } from '../fixtures/browser';
import { syntheticCustomer } from '../fixtures/data';

test.describe('scoped security regressions @security', () => {
  test('E2E-10 unauthenticated staff views redirect to login', async ({ page }) => {
    await page.goto('admin-orders.php');
    await expect(page).toHaveURL(/admin-login\.php/);
    await expect(page.locator('#staff-password')).toBeVisible();
  });

  test('E2E-15 forged cart CSRF is rejected without changing the session cart', async ({ page }) => {
    await page.goto('cart.php');
    const response = await page.request.post(new URL('cart-action.php', page.url()).toString(), {
      form: {
        action: 'add',
        csrf_token: 'forged-e2e-token',
        product_id: '1',
        size: 'M',
        colour: 'Navy Blue',
        quantity: '1',
      },
    });
    expect(response.status()).toBeLessThan(500);
    await page.goto('cart.php');
    await expect(page.locator('#cart-empty')).toBeVisible();
  });

  test('E2E-16 unknown guest order and mock-up URLs disclose no synthetic PII', async ({ page }) => {
    await page.goto('orders.php');
    await page.locator('#order-id').fill('999999999');
    await page.locator('#access-code').fill('incorrect-synthetic-access-code');
    await page.getByRole('button', { name: /view order/i }).click();
    await expect(page.getByRole('alert')).toContainText('We could not find an order with those access details.');
    await page.goto('confirmation.php?order=999999999');
    await expect(page.getByRole('heading', { name: 'ORDER NOT FOUND OR ACCESS REQUIRED' })).toBeVisible();
    await page.goto('mockup.php?mockup=999999999');
    await expect(page.getByRole('heading', { name: 'DESIGN PREVIEW UNAVAILABLE' })).toBeVisible();
    await expect(page.locator('body')).not.toContainText(syntheticCustomer.full_name);
  });

  test('E2E-18 bounded XSS search text renders as an input value only', async ({ page }) => {
    const payload = `\"><script>window.__f2bE2eXss = true</script>${syntheticCustomer.full_name}`;
    await page.goto(`shop.php?search=${encodeURIComponent(payload)}`);
    await expect(page.locator('#catalogue-search')).toHaveValue(payload);
    expect(await page.evaluate(() => Boolean((window as Window & { __f2bE2eXss?: boolean }).__f2bE2eXss))).toBe(false);
  });
});
