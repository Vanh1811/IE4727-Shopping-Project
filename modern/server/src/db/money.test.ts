import assert from 'node:assert/strict';
import { test } from 'node:test';
import { centsToDecimal, decimalToCents } from './money.js';

test('MySQL DECIMAL values convert to integer cents without floating point', () => {
  assert.equal(decimalToCents('24.90'), 2490);
  assert.equal(decimalToCents('0.5'), 50);
  assert.equal(decimalToCents('0.00'), 0);
  assert.equal(decimalToCents(null), null);
  assert.equal(centsToDecimal(2490), '24.90');
  assert.equal(centsToDecimal(5), '0.05');
  assert.throws(() => decimalToCents('24.901'), /Invalid MySQL DECIMAL/);
  assert.throws(() => centsToDecimal(1.5), /integer number of cents/);
});
