import test from 'node:test';
import assert from 'node:assert/strict';
import { hashAccessCode, verifyAccessCode } from './access.js';

test('guest order access stores only SHA-256 and compares supplied codes safely', () => {
  const code = 'ab'.repeat(24);
  const hash = hashAccessCode(code);
  assert.match(hash, /^[a-f0-9]{64}$/);
  assert.notEqual(hash, code);
  assert.equal(verifyAccessCode(hash, code), true);
  assert.equal(verifyAccessCode(hash, 'wrong-code'), false);
  assert.equal(verifyAccessCode(null, code), false);
  assert.equal(verifyAccessCode(hash, 'x'.repeat(129)), false);
  assert.equal(verifyAccessCode(hash, ['array']), false);
});
