import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import type { Server } from 'node:http';

process.env.NODE_ENV = 'test';
process.env.SESSION_SECRET = 'stage-one-test-secret-with-at-least-32-characters';
process.env.CLIENT_ORIGIN = 'http://127.0.0.1:5173';
delete process.env.MONGODB_URI;

const { createApp } = await import('./app.js');
let server: Server;
let baseUrl = '';

before(async () => {
  server = createApp().listen(0, '127.0.0.1');
  await new Promise<void>((resolve) => server.once('listening', resolve));
  const address = server.address();
  assert.ok(address && typeof address !== 'string');
  baseUrl = `http://127.0.0.1:${address.port}`;
});

after(async () => {
  await new Promise<void>((resolve, reject) => server.close((error) => error ? reject(error) : resolve()));
});

test('health reports unavailable MongoDB without exposing configuration', async () => {
  const response = await fetch(`${baseUrl}/api/v1/health`);
  assert.equal(response.status, 503);
  assert.deepEqual(await response.json(), {
    data: { status: 'not_ready', database: 'not_configured' },
    error: null,
  });
});

test('CSRF bootstrap creates a private session token', async () => {
  const response = await fetch(`${baseUrl}/api/v1/session/csrf`);
  assert.equal(response.status, 200);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  assert.match(response.headers.get('set-cookie') ?? '', /HttpOnly/i);
  const payload = await response.json() as { data: { csrfToken: string } };
  assert.match(payload.data.csrfToken, /^[a-f0-9]{64}$/);
});

test('staff session status never reveals the configured password hash', async () => {
  const response = await fetch(`${baseUrl}/api/v1/staff/session`);
  assert.equal(response.status, 200);
  const payload = await response.json() as { data: { configured: boolean; authenticated: boolean }; error: null };
  assert.equal(typeof payload.data.configured, 'boolean');
  assert.equal(payload.data.authenticated, false);
  assert.equal(JSON.stringify(payload).includes('PASSWORD_HASH'), false);
});

test('staff-only APIs reject guests before looking up orders', async () => {
  const response = await fetch(`${baseUrl}/api/v1/staff/orders`);
  assert.equal(response.status, 401);
  assert.deepEqual(await response.json(), { data: null, error: { code: 'STAFF_AUTH_REQUIRED', message: 'Staff sign-in is required.' } });
});

test('unknown API route returns JSON', async () => {
  const response = await fetch(`${baseUrl}/api/v1/missing`);
  assert.equal(response.status, 404);
  assert.equal(response.headers.get('content-type')?.split(';')[0], 'application/json');
});

test('JSON request bodies above the configured limit return a safe 413', async () => {
  const response = await fetch(`${baseUrl}/api/v1/missing`, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ payload: 'x'.repeat(33 * 1024) }),
  });
  assert.equal(response.status, 413);
  assert.deepEqual(await response.json(), {
    data: null,
    error: { code: 'BODY_TOO_LARGE', message: 'Request body is too large.' },
  });
});
