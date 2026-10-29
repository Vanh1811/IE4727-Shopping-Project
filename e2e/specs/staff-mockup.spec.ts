import { test, expect } from '../fixtures/browser';
import { readE2EConfig, staffGateReason } from '../config/env';
import { syntheticCustomer } from '../fixtures/data';
import { createSyntheticOrder, createTwoVariantOrder } from '../fixtures/order-flow';
import { MockupPage } from '../pages/MockupPage';
import { StaffPage } from '../pages/StaffPage';

test.use({ screenshot: 'off', trace: 'off' });

test('E2E-11 staff publishes a revision, customer requests changes, approves, and staff completes @mutation @staff', async ({ page, browser }, testInfo) => {
  const reason = staffGateReason();
  test.skip(Boolean(reason), reason ?? undefined);

  const order = await createSyntheticOrder(page);
  testInfo.annotations.push({ type: 'synthetic-order-id', description: String(order.orderId) });
  const config = readE2EConfig();
  const staffContext = await browser.newContext();

  try {
    const staffPage = await staffContext.newPage();
    await staffPage.goto(`admin-orders.php?order=${order.orderId}`);
    await expect(staffPage).toHaveURL(/admin-login\.php/);

    const staff = new StaffPage(staffPage);
    await staff.login('known-wrong-synthetic-password');
    await expect(staffPage.getByRole('alert')).toContainText('Staff access could not be verified.');
    await staff.login(config.staffPassword);
    await expect(staffPage).toHaveURL(/admin-orders\.php/);
    await staff.openOrder(order.orderId);
    await expect(staff.item(order.productName)).toBeVisible();
    await staff.publishRevision(order.productName);
    await expect(staff.item(order.productName)).toContainText('READY FOR REVIEW');

    await page.goto('orders.php');
    await expect(page.locator('.orders-table')).toContainText(order.productName);
    await expect(page.locator('.order-action-link--accent').first()).toBeVisible();
    const mockupHref = await page.locator('.order-action-link--accent').first().getAttribute('href');
    expect(mockupHref).toMatch(/^mockup\.php\?mockup=\d+$/);
    const mockupId = Number(new URL(mockupHref!, 'http://127.0.0.1').searchParams.get('mockup'));
    expect(mockupId).toBeGreaterThan(0);

    const foreignContext = await browser.newContext();
    try {
      const foreignPage = await foreignContext.newPage();
      await foreignPage.goto(`mockup.php?mockup=${mockupId}`);
      await expect(foreignPage.getByRole('heading', { name: 'DESIGN PREVIEW UNAVAILABLE' })).toBeVisible();
      await expect(foreignPage.locator('body')).not.toContainText(syntheticCustomer.full_name);
    } finally {
      await foreignContext.close();
    }

    const review = new MockupPage(page);
    await review.open(mockupId);
    await expect(review.reviewForm()).toBeVisible();
    const changeRequest = `Move artwork slightly left (${Date.now()})`;
    await review.requestChanges(changeRequest);
    await expect(page.getByRole('status')).toContainText('change request has been saved');
    await expect(page.locator('.mockup-state-message')).toContainText('CHANGES HAVE BEEN REQUESTED');

    await staff.openOrder(order.orderId);
    await expect(staff.item(order.productName)).toContainText('CHANGES REQUESTED');
    await staff.publishRevision(order.productName);
    await expect(staff.item(order.productName)).toContainText('READY FOR REVIEW');
    await page.goto(`mockup.php?mockup=${mockupId}`);
    await expect(page.locator('.mockup-preview__edition')).toContainText('REVISION 2');
    await expect(page.locator('.review-history')).toContainText(changeRequest);
    await new MockupPage(page).approve();
    await expect(page.getByRole('status')).toContainText('Design approval has been saved');
    await expect(page.getByText('CONFIRMED', { exact: true })).toBeVisible();

    await staff.openOrder(order.orderId);
    await expect(staff.orderRow(order.orderId)).toContainText('CONFIRMED');
    await staff.advanceStatus(order.orderId, 'START PRODUCTION');
    await expect(staff.orderRow(order.orderId)).toContainText('PROCESSING');
    await staff.advanceStatus(order.orderId, 'MARK COMPLETED');
    await expect(staff.orderRow(order.orderId)).toContainText('COMPLETED');

    await staff.logout();
    await expect(staffPage).toHaveURL(/admin-login\.php/);
    await staffPage.goto('admin-orders.php');
    await expect(staffPage).toHaveURL(/admin-login\.php/);
  } finally {
    await staffContext.close();
  }
});

test('E2E-12 partial multi-item approval does not confirm the order early @mutation @staff', async ({ page, browser }, testInfo) => {
  const reason = staffGateReason();
  test.skip(Boolean(reason), reason ?? undefined);

  const order = await createTwoVariantOrder(page);
  testInfo.annotations.push({ type: 'synthetic-order-id', description: String(order.orderId) });
  const config = readE2EConfig();
  const staffContext = await browser.newContext();
  try {
    const staffPage = await staffContext.newPage();
    const staff = new StaffPage(staffPage);
    await staff.openLogin();
    await staff.login(config.staffPassword);
    await staff.openOrder(order.orderId);
    await staff.publishVariant(order.productName, 'S');
    await staff.openOrder(order.orderId);
    await staff.publishVariant(order.productName, 'M');
    const smallMockupId = await staff.mockupId(order.productName, 'S');
    const mediumMockupId = await staff.mockupId(order.productName, 'M');

    await new MockupPage(page).open(smallMockupId);
    await new MockupPage(page).approve();
    await page.goto('orders.php');
    await expect(page.locator('.orders-table')).toContainText('DESIGN REVIEW');
    await expect(page.locator('.orders-table')).toContainText(order.productName);

    await new MockupPage(page).open(mediumMockupId);
    await new MockupPage(page).approve();
    await page.goto('orders.php');
    await expect(page.locator('.orders-table')).toContainText('CONFIRMED');
  } finally {
    await staffContext.close();
  }
});
