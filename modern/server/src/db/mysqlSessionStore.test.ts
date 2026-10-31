import assert from 'node:assert/strict';
import { test } from 'node:test';
import type { SessionData } from 'express-session';
import type { Pool } from 'mysql2/promise';
import { MysqlSessionStore } from './mysqlSessionStore.js';

function createFakePool() {
  const calls: { sql: string; values: unknown[] }[] = [];
  let saved: string | null = null;
  const fake = {
    execute: async (sql: string, values: unknown[] = []) => {
      calls.push({ sql, values });
      if (sql.startsWith('SELECT sess')) return [[...(saved ? [{ sess: saved, expires: new Date() }] : [])], []];
      if (sql.startsWith('SELECT sid')) return [[...(saved ? [{ sid: 'session-id', sess: saved, expires: new Date() }] : [])], []];
      if (sql.startsWith('SELECT COUNT')) return [[{ count: saved ? 1 : 0 }], []];
      if (sql.startsWith('INSERT')) saved = String(values[1]);
      if (sql.startsWith('DELETE FROM sessions')) saved = null;
      return [{ affectedRows: 1 }, []];
    },
  } as unknown as Pool;
  return { fake, calls, get saved() { return saved; } };
}

function call<T>(invoke: (callback: (error: unknown, value?: T | null) => void) => void): Promise<T | null> {
  return new Promise((resolve, reject) => invoke((error, value) => error ? reject(error) : resolve(value ?? null)));
}

test('MySQL session store upserts parameterized data and restores cart and authorization state', async () => {
  const state = createFakePool();
  const store = new MysqlSessionStore(state.fake);
  const session = {
    cookie: { expires: new Date('2026-11-01T00:00:00.000Z') },
    csrfToken: 'csrf-token',
    cart: { '4|M|Black': { productId: 4, size: 'M', colour: 'Black', quantity: 2 } },
    authorizedOrderNumbers: [81],
  } as unknown as SessionData;
  await new Promise<void>((resolve, reject) => store.set('session-id', session, (error) => error ? reject(error) : resolve()));
  assert.match(state.calls[0]!.sql, /ON DUPLICATE KEY UPDATE/);
  assert.equal(state.calls[0]!.values[0], 'session-id');
  assert.equal(state.calls[0]!.values[2] instanceof Date, true);
  assert.equal(state.calls[0]!.sql.includes('csrf-token'), false);

  const loaded = await call<SessionData>((callback) => store.get('session-id', callback));
  assert.deepEqual(loaded?.cart, session.cart);
  assert.deepEqual(loaded?.authorizedOrderNumbers, [81]);
  assert.equal(loaded?.csrfToken, 'csrf-token');
});

test('MySQL session store uses placeholders for touch, destroy, and expiry pruning', async () => {
  const state = createFakePool();
  const store = new MysqlSessionStore(state.fake);
  const session = { cookie: { expires: new Date('2026-11-01T00:00:00.000Z') } } as unknown as SessionData;
  await new Promise<void>((resolve) => store.touch('sid', session, resolve));
  await new Promise<void>((resolve, reject) => store.destroy('sid', (error) => error ? reject(error) : resolve()));
  assert.equal(await store.pruneExpired(), 1);
  assert.ok(state.calls.every((call) => !call.sql.includes('`sid`') || call.values.includes('sid')));
  assert.ok(state.calls.some((call) => call.sql.includes('expires <= CURRENT_TIMESTAMP(3)')));
});
