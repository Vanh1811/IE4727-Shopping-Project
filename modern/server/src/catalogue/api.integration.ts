import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import type { Server } from 'node:http';
import bcrypt from 'bcryptjs';
import type { RowDataPacket } from 'mysql2/promise';
import { randomBytes } from 'node:crypto';
import { env } from '../config/env.js';
import { createApp } from '../app.js';
import { assertSafeDatabaseTarget } from '../db/guard.js';
import { pool } from '../db/pool.js';
import { checkDatabaseReadiness } from '../db/readiness.js';
import { findProductByIdentifier } from './repository.js';
import { findOrderByNumber } from '../orders/repository.js';

let server: Server;
let baseUrl = '';
let databaseReady = false;
let setupFailure = 'MySQL is unavailable.';
let lifecycleFixture: { orderNumber: number; itemId: string; guestCookie: string; guestCsrf: string; productId: number; originalPriceCents: number } | null = null;
const integrationStaffPassword = 'F2B-local-integration-only';
const integrationRunId = randomBytes(12).toString('hex');
const integrationSessionIds = new Set<string>();

function captureCookie(response: Response) {
  const cookie = response.headers.get('set-cookie')?.split(';', 1)[0];
  if (!cookie) return undefined;
  const rawValue = decodeURIComponent(cookie.slice(cookie.indexOf('=') + 1));
  const match = /^s:([^.]+)\./.exec(rawValue);
  if (match?.[1]) integrationSessionIds.add(match[1]);
  return cookie;
}

interface ProductDto {
  name: string;
  categorySlug: string;
  colour: string;
  priceCents: number;
  images: string[];
}

interface ApiBody {
  data: {
    categories?: unknown[];
    items?: ProductDto[];
    pagination?: { total: number };
    filters?: { sort?: string; notice?: string | null };
    newArrivals?: unknown[];
    topSelling?: unknown[];
    priceFloor?: number;
    images?: string[];
  };
  error: { code: string; message: string } | null;
}

before(async () => {
  env.STAFF_PASSWORD_HASH = bcrypt.hashSync(integrationStaffPassword, 4);
  try {
    assertSafeDatabaseTarget();
    if (env.NODE_ENV !== 'test' || env.DB_NAME !== 'f2b_shop_modern_test') throw new Error('API integration tests require NODE_ENV=test and DB_NAME=f2b_shop_modern_test.');
    const readiness = await checkDatabaseReadiness();
    if (!readiness.connected || !readiness.schemaReady) throw new Error('The test database is not reachable or migrated. Run npm run db:init with DB_NAME=f2b_shop_modern_test.');
    const [countRows] = await pool.query<(RowDataPacket & { categories: number|string; products: number|string })[]>('SELECT (SELECT COUNT(*) FROM categories) AS categories, (SELECT COUNT(*) FROM products) AS products');
    const counts = countRows[0];
    if (Number(counts?.categories) !== 6 || Number(counts?.products) !== 64) throw new Error('The test database must contain the 6-category, 64-product SQL catalogue. Run npm run db:seed with DB_NAME=f2b_shop_modern_test.');
    server = createApp().listen(0, '127.0.0.1');
    await new Promise<void>((resolve) => server.once('listening', resolve));
    const address = server.address();
    assert.ok(address && typeof address !== 'string');
    baseUrl = `http://127.0.0.1:${address.port}`;
    databaseReady = true;
  } catch (error) {
    setupFailure = error instanceof Error ? error.message : 'MySQL test setup failed.';
  }
});

after(async () => {
  if (server) await new Promise<void>((resolve, reject) => server.close((error) => error ? reject(error) : resolve()));
  if (lifecycleFixture) {
    await pool.execute('UPDATE products SET price = ? WHERE id = ?', [(lifecycleFixture.originalPriceCents / 100).toFixed(2), lifecycleFixture.productId]);
    await pool.execute('DELETE FROM orders WHERE id = ?', [lifecycleFixture.orderNumber]);
  }
  if (integrationSessionIds.size) {
    const ids = [...integrationSessionIds];
    await pool.execute(`DELETE FROM sessions WHERE sid IN (${ids.map(() => '?').join(',')})`, ids);
  }
  await pool.end();
});

async function getJson(path: string): Promise<{ status: number; body: ApiBody }> {
  const response = await fetch(`${baseUrl}/api/v1${path}`);
  return { status: response.status, body: await response.json() as ApiBody };
}

test('catalogue API serves verified metadata, product details, filtering, and source sort orders', async () => {
  assert.ok(databaseReady, `API integration BLOCKED: ${setupFailure}`);
  const meta = await getJson('/catalogue/meta');
  assert.equal(meta.status, 200);
  assert.equal(meta.body.data.categories!.length, 6);
  assert.equal(meta.body.data.priceFloor, 24);

  const full = await getJson('/products?limit=100');
  assert.equal(full.status, 200);
  assert.equal(full.body.data.pagination!.total, 64);
  assert.equal(full.body.data.items!.length, 64);

  const search = await getJson('/products?search=Men%20Navy%25');
  assert.equal(search.body.data.items!.some((item) => item.name === 'Men Navy Blue Solid T-shirt'), true);
  const multi = await getJson('/products?category=t-shirts&category=shirts&colour=Navy%20Blue&colour=Maroon');
  assert.equal((multi.body.data.items?.length ?? 0) > 0, true);
  assert.equal(multi.body.data.items?.every((item) =>
    ['t-shirts', 'shirts'].includes(item.categorySlug) && ['Navy Blue', 'Maroon'].includes(item.colour)), true);
  const priceRange = await getJson('/products?min_price=30&max_price=35');
  assert.equal(priceRange.body.data.items?.every((item) => item.priceCents >= 3000 && item.priceCents <= 3500), true);

  for (const sort of ['featured', 'newest', 'price_asc', 'price_desc']) {
    const response = await getJson(`/products?sort=${sort}&limit=100`);
    assert.equal(response.status, 200);
    assert.equal(response.body.data.filters!.sort, sort);
  }
  const ascending = await getJson('/products?sort=price_asc&limit=100');
  const descending = await getJson('/products?sort=price_desc&limit=100');
  assert.equal((ascending.body.data.items?.[0].priceCents ?? 0) <= (ascending.body.data.items?.at(-1)?.priceCents ?? 0), true);
  assert.equal((descending.body.data.items?.[0].priceCents ?? 0) >= (descending.body.data.items?.at(-1)?.priceCents ?? 0), true);

  const product = await getJson('/products/1');
  assert.equal(product.status, 200);
  assert.equal(product.body.data.images?.length, 4);
  assert.equal(product.body.data.images?.[0], 'https://pazhanir.github.io/product-images/UnisexClothing/tshirts/17686030_1.jpg');
  const missing = await getJson('/products/999999');
  assert.equal(missing.status, 404);
  assert.equal(missing.body.error?.code, 'PRODUCT_NOT_FOUND');
});

test('home API and empty/invalid price-range states match the PHP baseline', async () => {
  assert.ok(databaseReady, `API integration BLOCKED: ${setupFailure}`);
  const home = await getJson('/home');
  assert.equal(home.status, 200);
  assert.equal(home.body.data.newArrivals!.length, 4);
  assert.equal(home.body.data.topSelling!.length, 4);
  const invalidRange = await getJson('/products?min_price=80&max_price=20');
  assert.equal(invalidRange.body.data.items!.length, 0);
  assert.equal(invalidRange.body.data.filters!.notice, 'Minimum price cannot be greater than maximum price.');
  const invalidBound = await getJson('/products?min_price=bad');
  assert.equal(invalidBound.body.data.filters!.notice, 'Enter a valid minimum price to use that filter.');
  assert.equal(invalidBound.body.data.pagination!.total, 64);
});

test('cart and checkout enforce CSRF, source validation, server totals, snapshots, idempotency and one-time access code', async () => {
  assert.ok(databaseReady, `API integration BLOCKED: ${setupFailure}`);
  const csrfResponse = await fetch(`${baseUrl}/api/v1/session/csrf`);
  const cookie = captureCookie(csrfResponse);
  assert.ok(cookie);
  const csrf = ((await csrfResponse.json()) as { data: { csrfToken: string } }).data.csrfToken;
  const product = await getJson('/products/1');
  const productId = (product.body.data as unknown as { id: number }).id;
  const sourceProduct = await findProductByIdentifier(productId);
  assert.ok(sourceProduct);
  const size = sourceProduct.categorySlug === 'accessories' ? 'ONE SIZE' : 'M';
  const add = async (quantity: number) => fetch(`${baseUrl}/api/v1/cart/items`, { method: 'POST', headers: { cookie, 'content-type': 'application/json', 'x-csrf-token': csrf }, body: JSON.stringify({ productId, size, colour: sourceProduct.colour, quantity }) });
  const denied = await fetch(`${baseUrl}/api/v1/cart/items`, { method: 'POST', headers: { cookie, 'content-type': 'application/json' }, body: JSON.stringify({ productId, size, colour: sourceProduct.colour, quantity: 1 }) });
  assert.equal(denied.status, 403);
  const cartKey = encodeURIComponent(`${productId}|${size}|${sourceProduct.colour}`);
  assert.equal((await add(1)).status, 201);
  for (const quantity of [2, 99]) assert.equal((await fetch(`${baseUrl}/api/v1/cart/items/${cartKey}`, { method: 'PATCH', headers: { cookie, 'content-type': 'application/json', 'x-csrf-token': csrf }, body: JSON.stringify({ quantity }) })).status, 200);
  assert.equal((await add(1)).status, 422);
  const cartResponse = await fetch(`${baseUrl}/api/v1/cart`, { headers: { cookie } });
  const cart = (await cartResponse.json() as { data: { items: { quantity: number }[]; totalCents: number } }).data;
  assert.equal(cart.items[0].quantity, 99);
  assert.equal(cart.totalCents, sourceProduct.priceCents * 99 - Math.floor(sourceProduct.priceCents * 99 * 0.1 + 0.5) + 500);

  const validCustomer = { full_name: 'Ada Customer', email: 'ada@example.test', phone: '+65 8123 4567', postal_code: '123456', delivery_address: '1 Sample Street', special_instructions: '' };
  const submit = (body: unknown, key: string) => fetch(`${baseUrl}/api/v1/checkout`, { method: 'POST', headers: { cookie, 'content-type': 'application/json', 'x-csrf-token': csrf, 'idempotency-key': key }, body: JSON.stringify(body) });
  const invalid = await submit({ ...validCustomer, full_name: 'A' }, 'invalid-checkout-key-01');
  assert.equal(invalid.status, 422);
  const invalidBody = await invalid.json() as { data: { errors: { full_name?: string } } };
  assert.equal(invalidBody.data.errors.full_name, 'Enter your full name.');
  const key = `checkout-${integrationRunId}`;
  const placedResponse = await submit(validCustomer, key);
  assert.equal(placedResponse.status, 201);
  const placed = await placedResponse.json() as { data: { orderNumber: number; status: string } };
  assert.equal(placed.data.status, 'ORDER_PLACED');
  const saved = await findOrderByNumber(placed.data.orderNumber);
  assert.ok(saved);
  lifecycleFixture = { orderNumber: placed.data.orderNumber, itemId: String(saved.items[0].id), guestCookie: cookie, guestCsrf: csrf, productId, originalPriceCents: sourceProduct.priceCents };
  assert.equal(saved.items[0].quantity, 99);
  assert.equal(saved.items[0].unitPriceCents, sourceProduct.priceCents);
  assert.equal(saved.emailStatus, 'disabled');
  assert.equal(saved.emailSentAt, null);
  assert.match(saved.accessCodeHash, /^[a-f0-9]{64}$/);
  assert.notEqual(saved.accessCodeHash, '');
  await pool.execute('UPDATE products SET price = ? WHERE id = ?', [((sourceProduct.priceCents + 100) / 100).toFixed(2), productId]);
  const confirmationPath = `/orders/${placed.data.orderNumber}/confirmation`;
  const firstConfirmation = await fetch(`${baseUrl}/api/v1${confirmationPath}`, { headers: { cookie } });
  const firstData = (await firstConfirmation.json() as { data: { accessCode: string | null; items: { unitPriceCents: number }[] } }).data;
  assert.match(firstData.accessCode ?? '', /^[a-f0-9]{48}$/);
  const initialAccessCode = firstData.accessCode!;
  assert.equal(firstData.items[0].unitPriceCents, sourceProduct.priceCents);
  const secondConfirmation = (await fetch(`${baseUrl}/api/v1${confirmationPath}`, { headers: { cookie } })).json();
  assert.equal(((await secondConfirmation) as { data: { accessCode: string | null } }).data.accessCode, null);
  const conflictingRetry = await submit({ ...validCustomer, email: 'different@example.test' }, key);
  assert.equal(conflictingRetry.status, 409);
  assert.equal(((await conflictingRetry.json()) as { error: { code: string } }).error.code, 'IDEMPOTENCY_KEY_REUSED');
  const outsiderCsrfResponse = await fetch(`${baseUrl}/api/v1/session/csrf`);
  const outsiderCookie = captureCookie(outsiderCsrfResponse);
  assert.ok(outsiderCookie);
  const outsiderCsrf = ((await outsiderCsrfResponse.json()) as { data: { csrfToken: string } }).data.csrfToken;
  assert.equal((await fetch(`${baseUrl}/api/v1${confirmationPath}`, { headers: { cookie: outsiderCookie } })).status, 403);
  const recover = (code: string) => fetch(`${baseUrl}/api/v1/orders/access`, { method: 'POST', headers: { cookie: outsiderCookie, 'content-type': 'application/json', 'x-csrf-token': outsiderCsrf }, body: JSON.stringify({ orderNumber: String(placed.data.orderNumber), accessCode: code }) });
  const wrongCode = await recover('wrong-code');
  assert.equal(wrongCode.status, 401);
  assert.equal(((await wrongCode.json()) as { error: { message: string } }).error.message, 'We could not find an order with those access details.');
  const recovered = await recover(initialAccessCode);
  assert.equal(recovered.status, 200);
  const recoveredCookie = captureCookie(recovered);
  assert.ok(recoveredCookie);
  assert.notEqual(recoveredCookie, outsiderCookie);
  assert.equal((await fetch(`${baseUrl}/api/v1/orders/${placed.data.orderNumber}`, { headers: { cookie: recoveredCookie } })).status, 200);
  assert.equal((await submit(validCustomer, key)).status, 200);
  const emptyCart = await fetch(`${baseUrl}/api/v1/cart`, { headers: { cookie } });
  assert.equal(((await emptyCart.json()) as { data: { itemCount: number } }).data.itemCount, 0);
  await pool.execute('UPDATE products SET price = ? WHERE id = ?', [(sourceProduct.priceCents / 100).toFixed(2), productId]);
});

test('staff and customer complete publish, revision, approval, and production lifecycle across sessions', async () => {
  assert.ok(databaseReady, `API integration BLOCKED: ${setupFailure}`);
  assert.ok(lifecycleFixture, 'The checkout integration fixture must complete first.');
  const guest = lifecycleFixture;
  const staffSession = await fetch(`${baseUrl}/api/v1/session/csrf`);
  let staffCookie = captureCookie(staffSession);
  assert.ok(staffCookie);
  let staffCsrf = ((await staffSession.json()) as { data: { csrfToken: string } }).data.csrfToken;
  const login = await fetch(`${baseUrl}/api/v1/staff/login`, { method: 'POST', headers: { cookie: staffCookie, 'content-type': 'application/json', 'x-csrf-token': staffCsrf }, body: JSON.stringify({ password: integrationStaffPassword }) });
  assert.equal(login.status, 200);
  const rotatedStaffCookie = captureCookie(login);
  assert.ok(rotatedStaffCookie);
  assert.notEqual(rotatedStaffCookie, staffCookie);
  staffCookie = rotatedStaffCookie;
  const renewed = await fetch(`${baseUrl}/api/v1/session/csrf`, { headers: { cookie: staffCookie } });
  staffCsrf = ((await renewed.json()) as { data: { csrfToken: string } }).data.csrfToken;
  const staffQueue = await fetch(`${baseUrl}/api/v1/staff/orders`, { headers: { cookie: staffCookie } });
  assert.equal(staffQueue.status, 200);
  const staffQueueText = await staffQueue.text();
  assert.equal(staffQueueText.includes('accessCodeHash'), false);
  assert.equal(staffQueueText.includes('idempotencyKey'), false);
  const publish = (revision?: number) => fetch(`${baseUrl}/api/v1/staff/orders/${guest.orderNumber}/items/${guest.itemId}/mockup`, { method: 'POST', headers: { cookie: staffCookie!, 'content-type': 'application/json', 'x-csrf-token': staffCsrf }, body: JSON.stringify({ previewImage: 'images/products/arc-denim.svg', printPosition: 'Front', printColour: 'Black', customText: revision ? 'Updated mark' : 'F2B' }) });
  const firstPublish = await publish();
  assert.equal(firstPublish.status, 201);
  const first = (await firstPublish.json() as { data: { mockupId: string; revision: number } }).data;
  assert.equal(first.revision, 1);
  assert.equal((await publish()).status, 409);
  assert.equal((await findOrderByNumber(guest.orderNumber))?.status, 'DESIGN_REVIEW');
  const unauthorizedSession = await fetch(`${baseUrl}/api/v1/session/csrf`);
  const unauthorizedCookieHeader = captureCookie(unauthorizedSession);
  assert.ok(unauthorizedCookieHeader);
  const unauthorizedCookie = unauthorizedCookieHeader;
  assert.equal((await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}`, { headers: { cookie: unauthorizedCookie } })).status, 404);
  const customerPreview = await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}`, { headers: { cookie: guest.guestCookie } });
  assert.equal(customerPreview.status, 200);
  const requestChanges = await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}/review`, { method: 'POST', headers: { cookie: guest.guestCookie, 'content-type': 'application/json', 'x-csrf-token': guest.guestCsrf }, body: JSON.stringify({ expectedRevision: 1, decision: 'request_changes', changeRequest: 'Move the print higher.' }) });
  assert.equal(requestChanges.status, 200);
  const stale = await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}/review`, { method: 'POST', headers: { cookie: guest.guestCookie, 'content-type': 'application/json', 'x-csrf-token': guest.guestCsrf }, body: JSON.stringify({ expectedRevision: 1, decision: 'approve' }) });
  assert.equal(stale.status, 409);
  assert.equal((await publish(2)).status, 201);
  const secondPreview = await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}`, { headers: { cookie: guest.guestCookie } });
  assert.equal(((await secondPreview.json()) as { data: { revision: number; reviews: unknown[] } }).data.revision, 2);
  const approve = await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}/review`, { method: 'POST', headers: { cookie: guest.guestCookie, 'content-type': 'application/json', 'x-csrf-token': guest.guestCsrf }, body: JSON.stringify({ expectedRevision: 2, decision: 'approve' }) });
  assert.equal(approve.status, 200);
  assert.equal((await findOrderByNumber(guest.orderNumber))?.status, 'CONFIRMED');
  const transition = (status: string) => fetch(`${baseUrl}/api/v1/staff/orders/${guest.orderNumber}/status`, { method: 'POST', headers: { cookie: staffCookie!, 'content-type': 'application/json', 'x-csrf-token': staffCsrf }, body: JSON.stringify({ status }) });
  assert.equal((await transition('PROCESSING')).status, 200);
  assert.equal((await transition('COMPLETED')).status, 200);
  assert.equal((await transition('PROCESSING')).status, 409);
  const finalOrder = await findOrderByNumber(guest.orderNumber);
  assert.equal(finalOrder?.status, 'COMPLETED');
  const finalMockup = await fetch(`${baseUrl}/api/v1/mockups/${first.mockupId}`, { headers: { cookie: guest.guestCookie } });
  assert.deepEqual(((await finalMockup.json()) as { data: { reviews: { decision: string }[] } }).data.reviews.map((review) => review.decision), ['request_changes','approve']);
});
