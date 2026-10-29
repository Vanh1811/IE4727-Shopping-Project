import type { Page } from '@playwright/test';

export class StaffPage {
  constructor(private readonly page: Page) {}

  async openLogin(): Promise<void> { await this.page.goto('admin-login.php'); }
  async login(password: string): Promise<void> {
    await this.page.locator('#staff-password').fill(password);
    await this.page.getByRole('button', { name: /sign in/i }).click();
  }
  async openOrder(orderId: number): Promise<void> { await this.page.goto(`admin-orders.php?order=${orderId}`); }
  orderRow(orderId: number) { return this.page.locator(`#order-${orderId}`); }
  item(productName: string) { return this.page.locator('.admin-order-item').filter({ hasText: productName }).first(); }
  itemVariant(productName: string, size: string) {
    return this.page.locator('.admin-order-item').filter({ hasText: productName }).filter({ hasText: new RegExp(`Size\\s+${size}\\b`) }).first();
  }
  async publishVariant(productName: string, size: string): Promise<void> {
    const item = this.itemVariant(productName, size);
    const form = item.locator('.admin-mockup-form');
    await form.locator('[name="preview_image"]').selectOption({ index: 0 });
    await form.locator('[name="print_position"]').fill('Front center');
    await form.locator('[name="print_colour"]').fill('Black');
    await form.locator('[name="custom_text"]').fill(`Synthetic ${size} artwork`);
    await form.getByRole('button', { name: /publish mock-up/i }).click();
  }
  async mockupId(productName: string, size: string): Promise<number> {
    const label = await this.itemVariant(productName, size).getByRole('link', { name: /MOCK-UP #\d+/i }).innerText();
    const id = Number(label.match(/#(\d+)/)?.[1]);
    if (!Number.isSafeInteger(id) || id < 1) throw new Error(`Published ${size} mock-up did not expose an ID.`);
    return id;
  }
  async publishRevision(productName: string): Promise<void> {
    const item = this.item(productName);
    const form = item.locator('.admin-mockup-form');
    await form.locator('[name="preview_image"]').selectOption({ index: 0 });
    await form.locator('[name="print_position"]').fill('Front center');
    await form.locator('[name="print_colour"]').fill('Black');
    await form.locator('[name="custom_text"]').fill('Synthetic E2E artwork');
    await form.getByRole('button', { name: /publish (?:mock-up|revision)/i }).click();
  }
  async advanceStatus(orderId: number, action: 'START PRODUCTION' | 'MARK COMPLETED'): Promise<void> {
    await this.orderRow(orderId).getByRole('button', { name: action }).click();
  }
  async logout(): Promise<void> { await this.page.getByRole('button', { name: 'SIGN OUT' }).click(); }
}
