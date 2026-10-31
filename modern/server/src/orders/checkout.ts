import { createHash, randomBytes } from 'node:crypto';
import { Types } from 'mongoose';
import { ApiError } from '../middleware/errors.js';
import { CartMap, calculateTotals } from '../cart/service.js';
import { Product } from '../models/Product.js';
import { Order } from '../models/Order.js';
import { Counter } from '../models/Counter.js';

// Mirrors PHP's explicit C0/DEL control-character rejection.
// eslint-disable-next-line no-control-regex
const hasControls = (value: string) => /[\u0000-\u001f\u007f]/.test(value);
const bytes = (value: string) => Buffer.byteLength(value, 'utf8');
export type CheckoutValues = { full_name: string; email: string; phone: string; postal_code: string; delivery_address: string; special_instructions: string };

export function validateCheckout(input: unknown) {
  const source = input && typeof input === 'object' && !Array.isArray(input) ? input as Record<string, unknown> : {};
  const values = Object.fromEntries(['full_name','email','phone','postal_code','delivery_address','special_instructions'].map((key) => [key, typeof source[key] === 'string' ? (source[key] as string).trim() : ''])) as CheckoutValues;
  const errors: Partial<Record<keyof CheckoutValues, string>> = {};
  if (!values.full_name || bytes(values.full_name) < 2 || bytes(values.full_name) > 160 || hasControls(values.full_name)) errors.full_name = 'Enter your full name.';
  if (bytes(values.email) > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) errors.email = 'Enter a valid email address.';
  const digits = values.phone.replace(/[^0-9]/g, '');
  if (!values.phone || bytes(values.phone) < 7 || bytes(values.phone) > 30 || !/^[0-9+(). -]+$/.test(values.phone) || digits.length < 7) errors.phone = 'Enter a valid phone number.';
  if (bytes(values.postal_code) < 3 || bytes(values.postal_code) > 20 || !/^[0-9A-Za-z][0-9A-Za-z -]{2,19}$/.test(values.postal_code)) errors.postal_code = 'Enter a valid postal code.';
  if (!values.delivery_address || bytes(values.delivery_address) > 500 || hasControls(values.delivery_address)) errors.delivery_address = 'Enter a delivery address up to 500 characters.';
  if (bytes(values.special_instructions) > 1000 || hasControls(values.special_instructions)) errors.special_instructions = 'Special instructions must be 1,000 characters or fewer.';
  return { values, errors };
}

export async function createOrder(cart: CartMap, values: CheckoutValues, idempotencyKey: string) {
  const existing = await Order.findOne({ idempotencyKey }).lean();
  if (existing) return { orderNumber: existing.orderNumber, created: false };
  const lines = Object.values(cart);
  if (!lines.length) throw new ApiError(409, 'EMPTY_CART', 'Your bag is empty.');
  const products = await Product.find({ legacyId: { $in: [...new Set(lines.map((line) => line.productId))] } }).lean();
  const productById = new Map(products.map((product) => [product.legacyId, product]));
  const items = lines.map((line) => {
    const product = productById.get(line.productId);
    if (!product || product.colour !== line.colour || (product.categorySlug === 'accessories') !== (line.size === 'ONE SIZE')) throw new ApiError(409, 'CART_CHANGED', 'A product in your bag is no longer available. Review your bag and try again.');
    return { productId: product.legacyId, productSlug: product.slug, productName: product.name, image: product.images[0], size: line.size, colour: line.colour, unitPriceCents: product.priceCents, quantity: line.quantity, lineTotalCents: product.priceCents * line.quantity, _id: new Types.ObjectId() };
  });
  const totals = calculateTotals(items.map(({ unitPriceCents, quantity }) => ({ priceCents: unitPriceCents, quantity })));
  const accessCode = randomBytes(24).toString('hex');
  const accessCodeHash = createHash('sha256').update(accessCode).digest('hex');
  const mongoSession = await Order.startSession();
  let orderNumber = 0;
  try {
    await mongoSession.withTransaction(async () => {
      const counter = await Counter.findOneAndUpdate({ _id: 'orders' }, { $inc: { sequence: 1 } }, { upsert: true, new: true, session: mongoSession, setDefaultsOnInsert: true });
      if (!counter) throw new Error('Order sequence unavailable.');
      orderNumber = counter.sequence;
      await Order.create([{
        orderNumber, fullName: values.full_name, email: values.email, phone: values.phone,
        postalCode: values.postal_code, deliveryAddress: values.delivery_address,
        specialInstructions: values.special_instructions,
        items, subtotalCents: totals.subtotalCents, discountCents: totals.discountCents,
        deliveryCents: totals.deliveryCents, totalCents: totals.totalCents,
        accessCodeHash, idempotencyKey, status: 'ORDER_PLACED',
      }], { session: mongoSession });
    });
  } catch (error) {
    if (typeof error === 'object' && error !== null && 'code' in error && error.code === 11000) {
      const duplicate = await Order.findOne({ idempotencyKey }).lean();
      if (duplicate) return { orderNumber: duplicate.orderNumber, created: false };
    }
    throw error;
  } finally { await mongoSession.endSession(); }
  return { orderNumber, created: true, accessCode };
}
