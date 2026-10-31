import assert from 'node:assert/strict';
import { test } from 'node:test';

process.env.SESSION_SECRET = 'mysql-guard-test-secret-at-least-32-characters';
const { env } = await import('../config/env.js');
const { assertSafeDatabaseTarget, quoteAllowedDatabase } = await import('./guard.js');

test('database guard allows only the configured modern database and loopback host', () => {
  assert.doesNotThrow(() => assertSafeDatabaseTarget(env.DB_NAME));
  assert.equal(quoteAllowedDatabase(env.DB_NAME), `\`${env.DB_NAME}\``);
  assert.throws(() => assertSafeDatabaseTarget('f2b_shop'), /Base Version database is protected/);
  assert.throws(() => assertSafeDatabaseTarget(env.NODE_ENV === 'test' ? 'f2b_shop_modern' : 'f2b_shop_modern_test'), /restricted to/);
});
