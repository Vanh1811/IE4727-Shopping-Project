import test from 'node:test';
import assert from 'node:assert/strict';

process.env.SESSION_SECRET = 'cart-service-test-secret-at-least-32-characters';
const { calculateTotals, cartKey, parseQuantity } = await import('./service.js');

test('cart quantity accepts 1, 2, and 99 and rejects invalid limits', () => {
  assert.equal(parseQuantity(1), 1);
  assert.equal(parseQuantity(2), 2);
  assert.equal(parseQuantity(99), 99);
  assert.equal(parseQuantity(0), null);
  assert.equal(parseQuantity(100), null);
  assert.equal(parseQuantity(1.5), null);
});

test('variant keys separate size and colour and totals use cents with PHP half-up discount', () => {
  assert.equal(cartKey({ productId: 3, size: 'M', colour: 'Black' }), '3|M|Black');
  assert.notEqual(cartKey({ productId: 3, size: 'M', colour: 'Black' }), cartKey({ productId: 3, size: 'L', colour: 'Black' }));
  assert.deepEqual(calculateTotals([{ priceCents: 2490, quantity: 2 }]), { subtotalCents: 4980, discountCents: 498, deliveryCents: 500, totalCents: 4982, itemCount: 2 });
  assert.deepEqual(calculateTotals([{ priceCents: 5, quantity: 1 }]), { subtotalCents: 5, discountCents: 1, deliveryCents: 500, totalCents: 504, itemCount: 1 });
  assert.deepEqual(calculateTotals([]), { subtotalCents: 0, discountCents: 0, deliveryCents: 0, totalCents: 0, itemCount: 0 });
});
