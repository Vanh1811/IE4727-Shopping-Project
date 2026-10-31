import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import type { Server } from 'node:http';
import mongoose from 'mongoose';
import { env } from '../config/env.js';
import { createApp } from '../app.js';
import { Category } from '../models/Category.js';
import { Product } from '../models/Product.js';
import { readLegacyCatalogueSeed } from './seedParser.js';

const databaseUri = env.MONGODB_URI;
if (env.NODE_ENV !== 'test' || !databaseUri) {
  throw new Error('API integration tests require NODE_ENV=test and MONGODB_URI for the throwaway f2b_shop_modern_test database.');
}
const parsedUri = new URL(databaseUri);
if (!['127.0.0.1', 'localhost', '::1'].includes(parsedUri.hostname) || parsedUri.pathname !== '/f2b_shop_modern_test') {
  throw new Error('API integration tests are restricted to loopback and the f2b_shop_modern_test database.');
}

let server: Server;
let baseUrl = '';
let databaseConnected = false;
let databaseReady = false;
let setupFailure = 'MongoDB is unavailable.';

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
  try {
    await mongoose.connect(databaseUri, { serverSelectionTimeoutMS: 5000 });
    databaseConnected = true;
    await Product.deleteMany({});
    await Category.deleteMany({});
    const seed = await readLegacyCatalogueSeed();
    const categories = await Category.insertMany(seed.categories.map((category) => ({
      legacyId: category.legacyId,
      name: category.name,
      slug: category.slug,
    })));
    const categoriesByLegacyId = new Map(categories.map((category) => [category.legacyId, category]));
    await Product.insertMany(seed.products.map((product) => {
      const category = categoriesByLegacyId.get(product.categoryLegacyId);
      assert.ok(category);
      return {
        legacyId: product.legacyId,
        categoryRef: category._id,
        categorySlug: category.slug,
        name: product.name,
        slug: product.slug,
        description: product.description,
        priceCents: product.priceCents,
        originalPriceCents: product.originalPriceCents,
        rating: product.rating,
        reviewCount: product.reviewCount,
        images: product.images,
        colour: product.colour,
        isNew: product.isNew,
        isTopSelling: product.isTopSelling,
        legacyCreatedAt: product.legacyCreatedAt,
      };
    }));
    server = createApp().listen(0, '127.0.0.1');
    await new Promise<void>((resolve) => server.once('listening', resolve));
    const address = server.address();
    assert.ok(address && typeof address !== 'string');
    baseUrl = `http://127.0.0.1:${address.port}`;
    databaseReady = true;
  } catch (error) {
    setupFailure = error instanceof Error ? error.message : 'MongoDB test setup failed.';
    if (databaseConnected) {
      await Product.deleteMany({}).catch(() => undefined);
      await Category.deleteMany({}).catch(() => undefined);
    }
    await mongoose.disconnect().catch(() => undefined);
  }
});

after(async () => {
  if (!databaseConnected) return;
  if (server) await new Promise<void>((resolve, reject) => server.close((error) => error ? reject(error) : resolve()));
  await Product.deleteMany({});
  await Category.deleteMany({});
  await mongoose.disconnect();
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
