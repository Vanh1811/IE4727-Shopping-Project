import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readLegacyCatalogueSeed } from './seedParser.js';
import { buildCatalogueFilter, colourHex, legacyLikePattern, legacyLikeRegex } from './service.js';

test('MySQL parser reads the verified source seed without dropping images', async () => {
  const seed = await readLegacyCatalogueSeed();
  assert.equal(seed.categories.length, 6);
  assert.equal(seed.products.length, 64);
  assert.equal(seed.products[0].images.length, 4);
  assert.equal(seed.products[0].images[0], 'https://pazhanir.github.io/product-images/UnisexClothing/tshirts/17686030_1.jpg');
  assert.equal(seed.products.every((product) => product.images.length === 4), true);
  assert.equal(seed.uniqueImageCount, 256);
  assert.equal(seed.duplicateImageCount, 0);
});

test('catalogue filters preserve supported multi-filters and safe sort defaults', () => {
  const result = buildCatalogueFilter({
    search: 'Navy%', categories: ['t-shirts', 'bad'], colours: ['Navy Blue', 'unknown'],
    minPrice: '25.00', maxPrice: '40', sort: 'price_asc', page: 1, limit: 100,
  }, ['Navy Blue', 'Maroon']);
  assert.deepEqual(result.appliedCategories, ['t-shirts']);
  assert.deepEqual(result.appliedColours, ['Navy Blue']);
  assert.match(result.whereSql, /p\.name LIKE \? ESCAPE '!'/);
  assert.match(result.whereSql, /c\.slug IN \(\?\)/);
  assert.match(result.whereSql, /p\.price >= \?/);
  assert.match(result.whereSql, /p\.price <= \?/);
  assert.deepEqual(result.parameters, ['%Navy%%', 't-shirts', 'Navy Blue', '25.00', '40.00']);
  assert.equal(result.orderBy, 'p.price ASC, p.id ASC');
  assert.equal(result.notice, null);
});

test('legacy LIKE wildcards and price notices follow PHP catalogue behavior', () => {
  assert.equal(legacyLikeRegex('Men Navy%').test('Men Navy Blue Tee'), true);
  assert.equal(legacyLikeRegex('M_n Navy').test('Men Navy'), true);
  assert.equal(legacyLikePattern('Navy%_'), '%Navy%_%');
  assert.equal(legacyLikePattern('Navy\\%'), '%Navy!%%');
  const invalid = buildCatalogueFilter({
    search: '', categories: [], colours: [], minPrice: 'bad', maxPrice: null,
    sort: 'unknown', page: 1, limit: 100,
  }, []);
  assert.equal(invalid.notice, 'Enter a valid minimum price to use that filter.');
  assert.equal(invalid.orderBy, 'p.is_top_selling DESC, p.is_new DESC, p.id ASC');
  assert.equal(colourHex('Navy Blue'), '#293c5d');
});

test('catalogue sort keys and multi-value filters compile to fixed parameterized SQL', () => {
  const sorts = {
    featured: 'p.is_top_selling DESC, p.is_new DESC, p.id ASC',
    newest: 'p.created_at DESC, p.id DESC',
    price_asc: 'p.price ASC, p.id ASC',
    price_desc: 'p.price DESC, p.id DESC',
  };
  for (const [sort, expected] of Object.entries(sorts)) {
    const result = buildCatalogueFilter({
      search: '', categories: ['t-shirts', 'shirts'], colours: ['navy blue'],
      minPrice: '29.991', maxPrice: '40.009', sort, page: 1, limit: 100,
    }, ['Navy Blue', 'Maroon']);
    assert.equal(result.orderBy, expected);
    assert.equal(result.sortKey, sort);
    assert.deepEqual(result.appliedCategories, ['t-shirts', 'shirts']);
    assert.deepEqual(result.appliedColours, ['Navy Blue']);
    assert.deepEqual(result.parameters.slice(-2), ['30.00', '40.00']);
    assert.match(result.whereSql, /c\.slug IN \(\?,\?\)/);
    assert.match(result.whereSql, /p\.colour IN \(\?\)/);
    assert.equal(result.orderBy.includes(';'), false);
  }
});
