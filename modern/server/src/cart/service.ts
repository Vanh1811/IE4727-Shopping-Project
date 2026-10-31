import { ApiError } from '../middleware/errors.js';
import { findProductByIdentifier, findProductsByIds } from '../catalogue/repository.js';

export type CartLine = { productId: number; size: string; colour: string; quantity: number };
export type CartMap = Record<string, CartLine>;
export const cartKey = (line: Pick<CartLine, 'productId'|'size'|'colour'>) => `${line.productId}|${line.size}|${line.colour}`;
export const parseQuantity = (value: unknown) => typeof value === 'number' && Number.isInteger(value) && value >= 1 && value <= 99 ? value : null;

export function calculateTotals(lines: { priceCents: number; quantity: number }[]) {
  const subtotalCents = lines.reduce((sum, line) => sum + line.priceCents * line.quantity, 0);
  // PHP round() uses half away from zero; all values are nonnegative cents.
  const discountCents = Math.floor(subtotalCents * 0.1 + 0.5);
  const deliveryCents = subtotalCents > 0 ? 500 : 0;
  return { subtotalCents, discountCents, deliveryCents, totalCents: Math.max(0, subtotalCents - discountCents + deliveryCents), itemCount: lines.reduce((sum, line) => sum + line.quantity, 0) };
}

export async function cartDto(cart: CartMap) {
  const ids = [...new Set(Object.values(cart).map((line) => line.productId))];
  if (!ids.length) return { items: [], ...calculateTotals([]) };
  const products = await findProductsByIds(ids);
  const byId = new Map(products.map((product) => [product.legacyId, product]));
  const items = Object.entries(cart).flatMap(([key, line]) => {
    const product = byId.get(line.productId);
    if (!product) return [];
    return [{ key, productId: product.legacyId, slug: product.slug, name: product.name, image: product.images[0], categorySlug: product.categorySlug, size: line.size, colour: line.colour, quantity: line.quantity, priceCents: product.priceCents, lineTotalCents: product.priceCents * line.quantity }];
  });
  return { items, ...calculateTotals(items.map((item) => ({ priceCents: item.priceCents, quantity: item.quantity }))) };
}

export async function validateCartLine(input: unknown) {
  if (!input || typeof input !== 'object' || Array.isArray(input)) throw new ApiError(422, 'INVALID_CART_ITEM', 'Select a valid product option.');
  const body = input as Record<string, unknown>;
  const productId = typeof body.productId === 'number' && Number.isSafeInteger(body.productId) && body.productId > 0 ? body.productId : null;
  const size = typeof body.size === 'string' ? body.size.trim().toUpperCase() : '';
  const colour = typeof body.colour === 'string' ? body.colour.trim() : '';
  const quantity = parseQuantity(body.quantity);
  if (productId === null || !['S','M','L','XL','ONE SIZE'].includes(size) || !colour || colour.length > 50 || quantity === null) throw new ApiError(422, 'INVALID_CART_ITEM', 'Select a valid product option and quantity from 1 to 99.');
  const product = await findProductByIdentifier(productId);
  if (!product || product.colour !== colour || (product.categorySlug === 'accessories') !== (size === 'ONE SIZE')) throw new ApiError(422, 'INVALID_CART_ITEM', 'The selected product option is no longer available.');
  return { productId, size, colour, quantity } satisfies CartLine;
}
