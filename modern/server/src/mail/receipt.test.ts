import test from 'node:test';
import assert from 'node:assert/strict';
process.env.SESSION_SECRET = 'stage-seven-mail-test-secret-with-more-than-32-bytes';
process.env.CLIENT_ORIGIN = 'http://127.0.0.1:5173';
process.env.NODE_ENV = 'test';
const { buildReceiptText, isLocalEmail, isLoopbackSmtpHost, localMailEnabled } = await import('./receipt.js');

test('local receipt is disabled unless the exact local recipient and loopback transport agree', () => {
  const base = { enabled: '1', recipient: 'student@f2b.test', allowedRecipient: 'STUDENT@F2B.TEST', smtpHost: '127.0.0.1', smtpPort: 1025 };
  assert.equal(localMailEnabled(base), true);
  assert.equal(localMailEnabled({ ...base, enabled: 'true' }), false);
  assert.equal(localMailEnabled({ ...base, recipient: 'person@example.com' }), false);
  assert.equal(localMailEnabled({ ...base, allowedRecipient: 'other@f2b.test' }), false);
  assert.equal(localMailEnabled({ ...base, smtpHost: 'smtp.example.com' }), false);
  assert.equal(isLocalEmail('person@localhost'), true);
  assert.equal(isLocalEmail('person@mail.test'), true);
  assert.equal(isLocalEmail('person@mail.localhost'), true);
  assert.equal(isLocalEmail('person@mail.local'), true);
  assert.equal(isLocalEmail('person@example.com'), false);
  assert.equal(isLoopbackSmtpHost('::1'), true);
  assert.equal(isLoopbackSmtpHost('localhost'), false);
});

test('receipt text uses immutable order snapshots and totals without customer email', () => {
  const body = buildReceiptText({
    orderNumber: 42, fullName: 'Ada Customer', status: 'ORDER_PLACED', createdAt: new Date('2026-10-31T00:00:00Z'),
    items: [{ productName: 'Studio Tee', size: 'M', colour: 'Navy Blue', unitPriceCents: 2490, quantity: 2, lineTotalCents: 4980 }],
    subtotalCents: 4980, discountCents: 498, deliveryCents: 500, totalCents: 4982,
  });
  assert.match(body, /Order number: #42/);
  assert.match(body, /Size: M \| Colour: Navy Blue/);
  assert.match(body, /Unit price: \$24\.90 \| Quantity: 2 \| Line total: \$49\.80/);
  assert.match(body, /Discount: -\$4\.98/);
  assert.match(body, /TOTAL: \$49\.82/);
  assert.match(body, /mock-up for your approval/);
  assert.equal(body.includes('@'), false);
});
