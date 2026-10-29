import type { Page } from '@playwright/test';

export class CartPage {
  constructor(private readonly page: Page) {}

  async open(): Promise<void> { await this.page.goto('cart.php'); }
  items() { return this.page.locator('.cart-item'); }
  async increaseFirstItem(): Promise<void> {
    const form = this.items().first().locator('.cart-quantity');
    const current = Number(await form.locator('button[name="quantity"]').last().getAttribute('value'));
    await form.locator(`button[name="quantity"][value="${current + 1}"]`).click();
  }
  async removeFirstItem(): Promise<void> { await this.items().first().getByRole('button', { name: /^remove/i }).click(); }
  async clear(): Promise<void> { await this.page.getByRole('button', { name: 'Clear cart' }).click(); }
}
