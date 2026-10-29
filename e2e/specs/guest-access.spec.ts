import { test, expect } from '../fixtures/browser';
import { mutationGateReason } from '../config/env';
import { syntheticCustomer } from '../fixtures/data';
import { createSyntheticOrder } from '../fixtures/order-flow';
import { OrdersPage } from '../pages/OrdersPage';

test.use({ screenshot: 'off', trace: 'off' });

test('E2E-09 guest code is private and grants only the matching order @mutation @security', async ({ page, browser }, testInfo) => {
  const reason = mutationGateReason();
  test.skip(Boolean(reason), reason ?? undefined);

  const order = await createSyntheticOrder(page);
  testInfo.annotations.push({ type: 'synthetic-order-id', description: String(order.orderId) });

  const guestContext = await browser.newContext();
  try {
    const guestPage = await guestContext.newPage();
    await guestPage.goto(`confirmation.php?order=${order.orderId}`);
    await expect(guestPage.getByRole('heading', { name: 'ORDER NOT FOUND OR ACCESS REQUIRED' })).toBeVisible();
    await expect(guestPage.locator('body')).not.toContainText(syntheticCustomer.full_name);

    await guestPage.goto('mockup.php?mockup=999999999');
    await expect(guestPage.getByRole('heading', { name: 'DESIGN PREVIEW UNAVAILABLE' })).toBeVisible();
    await expect(guestPage.locator('body')).not.toContainText(syntheticCustomer.full_name);

    const orders = new OrdersPage(guestPage);
    await orders.open();
    await orders.requestAccess(order.orderId, 'incorrect-synthetic-access-code');
    await expect(guestPage.getByRole('alert')).toContainText('We could not find an order with those access details.');
    await expect(orders.authorizedOrders()).toHaveCount(0);

    await orders.open();
    await orders.requestAccess(order.orderId, order.accessCode);
    await expect(guestPage.getByRole('status')).toContainText('Order access granted.');
    await expect(orders.authorizedOrders()).toContainText(order.productName);
    await expect(orders.authorizedOrders()).toContainText(order.size);
    await expect(orders.authorizedOrders()).toContainText(order.colour);
    await expect(orders.authorizedOrders()).toContainText('ORDER PLACED');
    await guestPage.goto(`confirmation.php?order=${order.orderId}`);
    await expect(guestPage.locator('.confirmation-note code')).toHaveCount(0);
  } finally {
    await guestContext.close();
  }
});
