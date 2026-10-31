import test from 'node:test';
import assert from 'node:assert/strict';
import bcrypt from 'bcryptjs';

process.env.STAFF_PASSWORD_HASH = bcrypt.hashSync('Correct-Horse-Battery', 4).replace(/^\$2a\$/, '$2y$');
process.env.SESSION_SECRET = 'stage-six-staff-auth-test-secret-with-32-bytes';
process.env.CLIENT_ORIGIN = 'http://127.0.0.1:5173';
process.env.NODE_ENV = 'test';
const { verifyStaffPassword, staffIsConfigured } = await import('./auth.js');

test('configured bcrypt staff hash verifies without exposing the hash', () => {
  assert.equal(staffIsConfigured(), true);
  assert.equal(verifyStaffPassword('Correct-Horse-Battery'), true);
  assert.equal(verifyStaffPassword('wrong'), false);
  assert.equal(verifyStaffPassword('x'.repeat(257)), false);
});
