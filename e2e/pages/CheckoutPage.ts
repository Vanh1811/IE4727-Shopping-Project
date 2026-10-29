import type { Page } from '@playwright/test';

export class CheckoutPage {
  constructor(private readonly page: Page) {}

  async open(): Promise<void> { await this.page.goto('checkout.php'); }
  form() { return this.page.locator('#checkout-form'); }
  async fillCustomer(customer: Record<string, string>): Promise<void> {
    for (const [name, value] of Object.entries(customer)) {
      await this.form().locator(`[name="${name}"]`).fill(value);
    }
  }
  async submit(): Promise<void> { await this.form().getByRole('button', { name: /place order/i }).click(); }
}
