import type { Page } from '@playwright/test';

export class ShopPage {
  constructor(private readonly page: Page) {}

  async open(): Promise<void> { await this.page.goto('shop.php'); }
  cards() { return this.page.locator('.product-card.shop-product-card'); }
  names() { return this.page.locator('.product-card__name a'); }
  async search(term: string): Promise<void> { await this.page.locator('#catalogue-search').fill(term); }
  async selectSort(value: 'price_asc' | 'price_desc'): Promise<void> { await this.page.locator('#sort-products').selectOption(value); }
}
