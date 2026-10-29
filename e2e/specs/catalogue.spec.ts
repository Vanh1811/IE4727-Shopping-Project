import { test, expect } from '../fixtures/browser';
import { ShopPage } from '../pages/ShopPage';

test.describe('catalogue browsing @catalogue', () => {
  test('E2E-03 search returns the selected seeded product', async ({ page }) => {
    const shop = new ShopPage(page);
    await shop.open();
    const firstName = (await shop.names().first().innerText()).trim();
    await shop.search(firstName);
    await expect(shop.cards()).toHaveCount(1);
    await expect(shop.names().first()).toHaveText(firstName);
  });

  test('an unmatched search shows the empty state without a PHP error', async ({ page }) => {
    const shop = new ShopPage(page);
    await shop.open();
    await shop.search(`e2e-no-match-${Date.now()}`);
    await expect(page.getByRole('heading', { name: 'No products found' })).toBeVisible();
    await expect(page.locator('body')).not.toContainText(/Fatal error|Uncaught (?:Error|Exception)/i);
  });

  test('E2E-03 category and colour filters preserve a real rendered choice', async ({ page }) => {
    await page.goto('shop.php');
    const category = page.locator('.shop-filter-form input[name="category[]"]').first();
    const categoryValue = await category.inputValue();
    await category.check();
    await expect(page).toHaveURL(new RegExp(`category(?:%5B%5D|\\[\\])=${categoryValue}`));
    await expect(category).toBeChecked();

    const colour = page.locator('.shop-filter-form input[name="colour[]"]').first();
    const colourValue = await colour.inputValue();
    await colour.check();
    await expect(page).toHaveURL(/colour%5B%5D=/i);
    const renderedColours = await page.locator('.shop-product-card__colour').allTextContents();
    expect(renderedColours.length).toBeGreaterThan(0);
    for (const renderedColour of renderedColours) expect(renderedColour.trim()).toBe(colourValue);
  });

  test('E2E-04 price sort is numerically ordered', async ({ page }) => {
    const shop = new ShopPage(page);
    await shop.open();
    await shop.selectSort('price_asc');
    const prices = await page.locator('.product-card__current-price').allTextContents();
    const values = prices.map((price) => Number(price.replace(/[^\d.]/g, '')));
    expect(values.length).toBeGreaterThan(0);
    expect(values).toEqual([...values].sort((a, b) => a - b));
  });

  test('E2E-04 server-side price range excludes products outside the bounds', async ({ page }) => {
    await page.goto('shop.php?min_price=20&max_price=40&sort=price_asc');
    const prices = await page.locator('.product-card__current-price').allTextContents();
    for (const text of prices) {
      const price = Number(text.replace(/[^\d.]/g, ''));
      expect(price).toBeGreaterThanOrEqual(20);
      expect(price).toBeLessThanOrEqual(40);
    }
    await expect(page.locator('#min-price-range')).toHaveAttribute('aria-valuenow', '20');
  });
});
