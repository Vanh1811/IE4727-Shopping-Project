import { readdir, realpath, stat } from 'node:fs/promises';
import { dirname, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { ApiError } from '../middleware/errors.js';

export type MockupInput = { previewImage: string; printPosition: string; printColour: string; customText: string };
const assetRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../../../client/public/images/products');
// Match the legacy validator's excluded C0 control bytes while preserving tab/newline carriage-return behavior.
// eslint-disable-next-line no-control-regex
const hasDisallowedControl = (value: string) => /[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/.test(value);

export function validateMockupInput(input: unknown): MockupInput {
  if (!input || typeof input !== 'object' || Array.isArray(input)) throw new ApiError(422, 'INVALID_MOCKUP', 'Please provide valid mock-up details and a local product image.');
  const body = input as Record<string, unknown>;
  const previewImage = typeof body.previewImage === 'string' ? body.previewImage.trim() : '';
  const printPosition = typeof body.printPosition === 'string' ? body.printPosition.trim() : '';
  const printColour = typeof body.printColour === 'string' ? body.printColour.trim() : '';
  const customText = typeof body.customText === 'string' ? body.customText.trim() : '';
  if (!/^images\/products\/[A-Za-z0-9][A-Za-z0-9._/-]*$/.test(previewImage) || previewImage.includes('..') ||
      !printPosition || Buffer.byteLength(printPosition) > 100 || hasDisallowedControl(printPosition) ||
      !printColour || Buffer.byteLength(printColour) > 100 || hasDisallowedControl(printColour) ||
      Buffer.byteLength(customText) > 500 || hasDisallowedControl(customText)) {
    throw new ApiError(422, 'INVALID_MOCKUP', 'Please provide valid mock-up details and a local product image.');
  }
  return { previewImage, printPosition, printColour, customText };
}

export async function resolveMockupAsset(path: string): Promise<string | null> {
  if (!/^images\/products\/[A-Za-z0-9][A-Za-z0-9._/-]*$/.test(path) || path.includes('..')) return null;
  const root = await realpath(assetRoot).catch(() => null);
  if (!root) return null;
  const candidate = await realpath(resolve(root, path.slice('images/products/'.length))).catch(() => null);
  if (!candidate || !candidate.startsWith(root + sep) || !(await stat(candidate).catch(() => null))?.isFile()) return null;
  return `/images/products/${relative(root, candidate).split(sep).join('/')}`;
}

export async function listMockupAssets(): Promise<string[]> {
  async function walk(directory: string): Promise<string[]> {
    const entries = await readdir(directory, { withFileTypes: true }).catch(() => []);
    const nested = await Promise.all(entries.map(async (entry) => {
      const target = resolve(directory, entry.name);
      if (entry.isSymbolicLink()) return [];
      if (entry.isDirectory()) return walk(target);
      return entry.isFile() && /^[A-Za-z0-9][A-Za-z0-9._-]*$/.test(entry.name)
        ? [`images/products/${relative(assetRoot, target).split(sep).join('/')}`]
        : [];
    }));
    return nested.flat();
  }
  return (await walk(assetRoot)).sort();
}
