import { test, expect } from '../fixtures/browser';
import { ProductPage } from '../pages/ProductPage';
import { CartPage } from '../pages/CartPage';

test.describe('product and session cart @cart', () => {
  test('E2E-05 product gallery, size, and quantity controls work', async ({ page }) => {
    await page.goto('shop.php');
    const productHref = await page.locator('.product-card__name a').first().getAttribute('href');
    expect(productHref).toMatch(/^product\.php\?id=\d+$/);
    const product = new ProductPage(page);
    await product.open(productHref!);
    const firstImage = await page.locator('#product-main-image').getAttribute('src');
    const thumbnails = page.locator('.product-thumbnail');
    if (await thumbnails.count() > 1) {
      await product.selectGalleryThumbnail(1);
      await expect(page.locator('#product-main-image')).not.toHaveAttribute('src', firstImage!);
    }
    await product.chooseFirstValidSize();
    await expect(page.locator('#product-size')).not.toHaveValue('');
    await product.increaseQuantity();
    await expect(page.locator('#product-quantity')).toHaveValue('2');
    await page.getByRole('button', { name: 'Decrease quantity' }).click();
    await page.getByRole('button', { name: 'Decrease quantity' }).click();
    await expect(page.locator('#product-quantity')).toHaveValue('1');
  });

  test('E2E-06 same product variant aggregates and cart actions update state', async ({ page }) => {
    await page.goto('shop.php');
    const productHref = await page.locator('.product-card__name a').first().getAttribute('href');
    expect(productHref).toMatch(/^product\.php\?id=\d+$/);
    const product = new ProductPage(page);
    await product.open(productHref!);
    await product.chooseFirstValidSize();
    await product.addToCart();

    await product.open(productHref!);
    await product.chooseFirstValidSize();
    await product.addToCart();
    const cart = new CartPage(page);
    await cart.open();
    await expect(cart.items()).toHaveCount(1);
    await expect(cart.items().first().locator('.cart-item__details')).toContainText('Size');
    await expect(cart.items().first().locator('.cart-quantity button[name="quantity"]').last()).toHaveAttribute('value', '2');

    await cart.increaseFirstItem();
    await expect(cart.items().first().locator('.cart-quantity button[name="quantity"]').last()).toHaveAttribute('value', '3');
    await cart.removeFirstItem();
    await expect(page.locator('#cart-empty')).toBeVisible();
  });

  test('E2E-06 clear cart removes all session items', async ({ page }) => {
    await page.goto('shop.php');
    const productHref = await page.locator('.product-card__name a').first().getAttribute('href');
    const product = new ProductPage(page);
    await product.open(productHref!);
    await product.chooseFirstValidSize();
    await product.addToCart();
    const cart = new CartPage(page);
    await cart.open();
    await cart.clear();
    await expect(page.locator('#cart-empty')).toBeVisible();
  });
});
