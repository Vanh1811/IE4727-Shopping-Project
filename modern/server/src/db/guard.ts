import { env } from '../config/env.js';

const allowedDatabases = new Set(['f2b_shop_modern', 'f2b_shop_modern_test']);

export function assertSafeDatabaseTarget(database = env.DB_NAME): void {
  const expected = env.NODE_ENV === 'test' ? 'f2b_shop_modern_test' : 'f2b_shop_modern';
  if (!['127.0.0.1', 'localhost', '::1'].includes(env.DB_HOST)) {
    throw new Error('Modern database scripts are restricted to a loopback MySQL host.');
  }
  if (!allowedDatabases.has(database) || database !== expected) {
    throw new Error(`This command is restricted to ${expected}; the PHP Base Version database is protected.`);
  }
}

export function quoteAllowedDatabase(database = env.DB_NAME): string {
  assertSafeDatabaseTarget(database);
  return `\`${database}\``;
}
