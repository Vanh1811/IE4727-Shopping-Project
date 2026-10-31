import test from 'node:test';
import assert from 'node:assert/strict';
import { validateCheckout } from './checkout.js';

const valid = { full_name: 'Ada Customer', email: 'ada@example.test', phone: '+65 8123 4567', postal_code: '123456', delivery_address: '1 Sample Street', special_instructions: '' };

test('checkout accepts source-valid values and trims them', () => {
  const result = validateCheckout({ ...valid, full_name: ' Ada Customer ' });
  assert.deepEqual(result.errors, {});
  assert.equal(result.values.full_name, 'Ada Customer');
});

test('checkout preserves the PHP validation messages and rejects controls and limits', () => {
  const result = validateCheckout({ ...valid, full_name: 'A', email: 'bad', phone: '123', postal_code: '!', delivery_address: `line\nfeed`, special_instructions: 'x'.repeat(1001) });
  assert.deepEqual(result.errors, {
    full_name: 'Enter your full name.',
    email: 'Enter a valid email address.',
    phone: 'Enter a valid phone number.',
    postal_code: 'Enter a valid postal code.',
    delivery_address: 'Enter a delivery address up to 500 characters.',
    special_instructions: 'Special instructions must be 1,000 characters or fewer.',
  });
});
