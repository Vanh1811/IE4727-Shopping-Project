import type { Page } from '@playwright/test';
import { syntheticCustomer } from './data';
import { ProductPage } from '../pages/ProductPage';
import { CheckoutPage } from '../pages/CheckoutPage';

export interface CreatedOrder {
  orderId: number;
  accessCode: string;
  productName: string;
  productHref: string;
  size: string;
  colour: string;
}

export async function createSyntheticOrder(page: Page): Promise<CreatedOrder> {
  await page.goto('shop.php');
  const firstProduct = page.locator('.product-card__name a').first();
  const productName = (await firstProduct.innerText()).trim();
  const productHref = (await firstProduct.getAttribute('href')) ?? '';
  const product = new ProductPage(page);
  await product.open(productHref);
  const colour = (await page.locator('#selected-colour').innerText()).trim();
  const sizeButton = page.locator('.product-size-grid button').first();
  const size = (await sizeButton.getAttribute('data-size')) ?? '';
  await sizeButton.click();
  await product.addToCart();

  const checkout = new CheckoutPage(page);
  await checkout.open();
  await checkout.fillCustomer(syntheticCustomer);
  await checkout.submit();
  const current = new URL(page.url());
  const orderId = Number(current.searchParams.get('order'));
  if (!Number.isSafeInteger(orderId) || orderId < 1) throw new Error('Synthetic checkout did not reach an order confirmation.');
  const accessCode = (await page.locator('.confirmation-note code').innerText()).trim();
  if (!accessCode) throw new Error('The authorized confirmation did not display its one-time guest code.');
  return { orderId, accessCode, productName, productHref, size, colour };
}

export interface CreatedMultiVariantOrder {
  orderId: number;
  accessCode: string;
  productName: string;
  variants: Array<{ size: string; colour: string }>;
}

export async function createTwoVariantOrder(page: Page): Promise<CreatedMultiVariantOrder> {
  await page.goto('shop.php');
  const cards = page.locator('.shop-product-card');
  let productHref = '';
  for (const card of await cards.all()) {
    const category = (await card.locator('.shop-product-card__category').innerText()).trim();
    if (!/accessor/i.test(category)) {
      productHref = (await card.locator('.product-card__name a').getAttribute('href')) ?? '';
      break;
    }
  }
  if (!productHref) throw new Error('No garment product was available for the two-variant workflow.');

  const product = new ProductPage(page);
  await product.open(productHref);
  const productName = (await page.locator('#product-title').innerText()).trim();
  const colour = (await page.locator('#selected-colour').innerText()).trim();
  for (const size of ['S', 'M']) {
    const sizeButton = page.locator(`.product-size-grid button[data-size="${size}"]`);
    if (await sizeButton.count() !== 1) throw new Error(`The selected garment does not offer required size ${size}.`);
    await sizeButton.click();
    await product.addToCart();
    if (size === 'S') await product.open(productHref);
  }

  const checkout = new CheckoutPage(page);
  await checkout.open();
  await checkout.fillCustomer(syntheticCustomer);
  await checkout.submit();
  const orderId = Number(new URL(page.url()).searchParams.get('order'));
  if (!Number.isSafeInteger(orderId) || orderId < 1) throw new Error('Synthetic multi-variant checkout did not reach confirmation.');
  const accessCode = (await page.locator('.confirmation-note code').innerText()).trim();
  return {
    orderId,
    accessCode,
    productName,
    variants: [{ size: 'S', colour }, { size: 'M', colour }],
  };
}
