import test from 'node:test';
import assert from 'node:assert/strict';
import { listMockupAssets, resolveMockupAsset, validateMockupInput } from './mockups.js';

test('mock-up input accepts bounded local settings and rejects traversal and controls', () => {
  assert.deepEqual(validateMockupInput({ previewImage: 'images/products/arc-denim.svg', printPosition: 'Front', printColour: 'Black', customText: 'F2B' }), {
    previewImage: 'images/products/arc-denim.svg', printPosition: 'Front', printColour: 'Black', customText: 'F2B',
  });
  assert.throws(() => validateMockupInput({ previewImage: 'images/products/../../server/.env', printPosition: 'Front', printColour: 'Black', customText: '' }), /valid mock-up details/);
  assert.throws(() => validateMockupInput({ previewImage: 'images/products/arc-denim.svg', printPosition: 'P'.repeat(101), printColour: 'Black', customText: '' }), /valid mock-up details/);
  assert.throws(() => validateMockupInput({ previewImage: 'images/products/arc-denim.svg', printPosition: 'Front', printColour: 'Black', customText: 'x'.repeat(501) }), /valid mock-up details/);
});

test('mock-up previews resolve only to existing local allowlisted product images', async () => {
  assert.equal(await resolveMockupAsset('images/products/arc-denim.svg'), '/images/products/arc-denim.svg');
  assert.equal(await resolveMockupAsset('images/products/../../server/.env'), null);
  assert.equal(await resolveMockupAsset('https://example.test/remote.svg'), null);
  assert.ok((await listMockupAssets()).includes('images/products/arc-denim.svg'));
});
