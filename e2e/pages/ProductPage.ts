import type { Page } from '@playwright/test';

export class ProductPage {
  constructor(private readonly page: Page) {}

  async open(productHref: string): Promise<void> { await this.page.goto(productHref); }
  title() { return this.page.locator('#product-title'); }
  async chooseFirstValidSize(): Promise<void> {
    const size = this.page.locator('.product-size-grid button').first();
    await size.click();
  }
  async increaseQuantity(): Promise<void> { await this.page.getByRole('button', { name: 'Increase quantity' }).click(); }
  async addToCart(): Promise<void> { await this.page.locator('#add-to-cart').click(); }
  async selectGalleryThumbnail(index = 1): Promise<void> { await this.page.locator('.product-thumbnail').nth(index).click(); }
}
