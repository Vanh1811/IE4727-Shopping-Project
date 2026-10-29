import { test, expect } from '../fixtures/browser';
import { mailGateReason, readE2EConfig } from '../config/env';
import { syntheticCustomer } from '../fixtures/data';
import { readSavedOrder } from '../fixtures/db';
import { listMailpitMessages, getMailpitRaw } from '../fixtures/mailpit';
import { createSyntheticOrder } from '../fixtures/order-flow';

test.use({ screenshot: 'off', trace: 'off' });

test('E2E-13/14 optional local receipt includes saved item data and is not duplicated @mutation @mail', async ({ page, request }, testInfo) => {
  const reason = mailGateReason();
  test.skip(Boolean(reason), reason ?? undefined);
  const config = readE2EConfig();
  const before = await listMailpitMessages(request);
  const order = await createSyntheticOrder(page);
  testInfo.annotations.push({ type: 'synthetic-order-id', description: String(order.orderId) });

  await expect.poll(async () => {
    const messages = await listMailpitMessages(request);
    return messages.messages.filter((message) => (message.Subject ?? '').includes(`#${order.orderId}`)).length;
  }, { message: 'Wait for the local Mailpit inbox to capture this specific order receipt.' }).toBe(1);

  const after = await listMailpitMessages(request);
  const matches = after.messages.filter((message) => (message.Subject ?? '').includes(`#${order.orderId}`));
  expect(matches).toHaveLength(1);
  const raw = await getMailpitRaw(request, matches[0].ID);
  const headers = raw.split(/\r?\n\r?\n/, 1)[0];
  const toHeader = headers.match(/^To:\s*(.+)$/im)?.[1]?.trim() ?? '';
  expect(toHeader.toLowerCase()).toBe(config.expectedLocalRecipient.toLowerCase());
  expect(headers).not.toMatch(/^(?:Cc|Bcc):/im);
  expect(raw).toContain(order.productName);
  expect(raw).toContain(`Size: ${order.size}`);
  expect(raw).toContain(`Colour: ${order.colour}`);
  expect(raw).toContain('Quantity: 1');
  expect(raw).toContain('ORDER ACKNOWLEDGEMENT');
  expect(raw).not.toContain(order.accessCode);

  const saved = await readSavedOrder(order.orderId);
  if (saved) {
    expect(saved.order?.email_sent_at).not.toBeNull();
    expect(saved.items[0]?.product_name).toBe(order.productName);
    const savedItem = saved.items[0];
    expect(raw).toContain(`Unit price: $${Number(savedItem.unit_price).toFixed(2)}`);
    expect(raw).toContain(`Line total: $${Number(savedItem.line_total).toFixed(2)}`);
    expect(raw).toContain(`TOTAL: $${Number(saved.order?.total).toFixed(2)}`);
    expect(raw).toContain(`Discount: -$${Number(saved.order?.discount).toFixed(2)}`);
    expect(raw).toContain(`Delivery fee: $${Number(saved.order?.delivery_fee).toFixed(2)}`);
  } else if (config.dbUser) {
    throw new Error('Read-only DB was configured but receipt snapshot verification returned no order.');
  }

  await page.reload();
  await expect(page.locator('#confirmation-title')).toHaveText('ORDER PLACED');
  const afterRefresh = await listMailpitMessages(request);
  expect(afterRefresh.messages.filter((message) => (message.Subject ?? '').includes(`#${order.orderId}`))).toHaveLength(1);
  expect(before.total).toBeLessThanOrEqual(after.total);
  expect(raw).not.toContain(syntheticCustomer.email);
});
