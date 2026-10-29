import type { Page } from '@playwright/test';

export class OrdersPage {
  constructor(private readonly page: Page) {}

  async open(): Promise<void> { await this.page.goto('orders.php'); }
  async requestAccess(orderId: number, accessCode: string): Promise<void> {
    await this.page.locator('#order-id').fill(String(orderId));
    await this.page.locator('#access-code').fill(accessCode);
    await this.page.getByRole('button', { name: /view order/i }).click();
  }
  authorizedOrders() { return this.page.locator('.orders-table tbody tr'); }
}
