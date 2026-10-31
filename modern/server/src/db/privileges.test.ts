import assert from 'node:assert/strict';
import { test } from 'node:test';
import { canInitializeDatabase } from './privileges.js';

test('database preflight recognizes only grants sufficient for an allowlisted Modern schema', () => {
  assert.equal(canInitializeDatabase(['GRANT ALL PRIVILEGES ON *.* TO `root`@`localhost`'], 'f2b_shop_modern', false), true);
  assert.equal(canInitializeDatabase(['GRANT CREATE, ALTER ON `f2b_shop_modern`.* TO `student`@`localhost`'], 'f2b_shop_modern', true), true);
  assert.equal(canInitializeDatabase(['GRANT SELECT ON `f2b_shop_modern`.* TO `student`@`localhost`'], 'f2b_shop_modern', true), false);
  assert.equal(canInitializeDatabase(['GRANT CREATE ON *.* TO `student`@`localhost`'], 'f2b_shop_modern', false), true);
  assert.equal(canInitializeDatabase(['GRANT CREATE ON *.* TO `student`@`localhost`'], 'f2b_shop_modern', true), false);
});
