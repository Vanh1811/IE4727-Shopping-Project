import type { Page } from '@playwright/test';

export class MockupPage {
  constructor(private readonly page: Page) {}

  async open(mockupId: number): Promise<void> { await this.page.goto(`mockup.php?mockup=${mockupId}`); }
  reviewForm() { return this.page.locator('#mockup-review-form'); }
  async requestChanges(text: string): Promise<void> {
    await this.reviewForm().locator('[name="review_decision"][value="request_changes"]').check();
    await this.reviewForm().locator('#change-request').fill(text);
    await this.reviewForm().getByRole('button', { name: /submit review/i }).click();
  }
  async approve(): Promise<void> {
    await this.reviewForm().locator('[name="review_decision"][value="approve"]').check();
    await this.reviewForm().getByRole('button', { name: /submit review/i }).click();
  }
}
