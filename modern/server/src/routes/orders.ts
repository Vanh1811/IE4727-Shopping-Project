import { Router } from 'express';
import { rateLimit } from 'express-rate-limit';
import type { Request } from 'express';
import { requireCsrf } from '../middleware/csrfGuard.js';
import { ApiError } from '../middleware/errors.js';
import { Order } from '../models/Order.js';
import { verifyAccessCode } from '../orders/access.js';

export const ordersRouter = Router();
const lookupLimiter = rateLimit({ windowMs: 15 * 60_000, limit: 10, skipSuccessfulRequests: true, standardHeaders: 'draft-8', legacyHeaders: false });

type OrderView = {
  orderNumber: number; status: string; createdAt: Date; fullName: string;
  items: { _id: { toString(): string }; productId: number; productName: string; image: string; size: string; colour: string; unitPriceCents: number; quantity: number; lineTotalCents: number }[];
  subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number; emailStatus: string;
};

function orderDto(order: OrderView | null) {
  if (!order) return null;
  return {
    orderNumber: order.orderNumber,
    status: order.status,
    createdAt: order.createdAt,
    fullName: order.fullName,
    items: order.items.map((item) => ({ id: item._id.toString(), productId: item.productId, productName: item.productName, image: item.image, size: item.size, colour: item.colour, unitPriceCents: item.unitPriceCents, quantity: item.quantity, lineTotalCents: item.lineTotalCents })),
    subtotalCents: order.subtotalCents,
    discountCents: order.discountCents,
    deliveryCents: order.deliveryCents,
    totalCents: order.totalCents,
    emailStatus: order.emailStatus,
  };
}

function isAuthorized(request: Request, orderNumber: number) {
  return request.session.authorizedOrderNumbers?.includes(orderNumber) ?? false;
}

ordersRouter.get('/orders', async (request, response) => {
  const numbers = request.session.authorizedOrderNumbers ?? [];
  if (!numbers.length) { response.json({ data: { orders: [] }, error: null }); return; }
  const orders = await Order.find({ orderNumber: { $in: numbers } }).sort({ createdAt: -1 }).lean();
  response.json({ data: { orders: orders.map(orderDto) }, error: null });
});

ordersRouter.post('/orders/access', lookupLimiter, requireCsrf, async (request, response) => {
  const rawNumber = typeof request.body?.orderNumber === 'string' ? request.body.orderNumber.trim() : '';
  const orderNumber = /^\d{1,20}$/.test(rawNumber) ? Number(rawNumber) : 0;
  const accessCode = request.body?.accessCode;
  const order = Number.isSafeInteger(orderNumber) && orderNumber > 0 ? await Order.findOne({ orderNumber }) : null;
  if (!verifyAccessCode(order?.accessCodeHash, accessCode)) {
    response.status(401).json({ data: null, error: { code: 'ORDER_ACCESS_INVALID', message: 'We could not find an order with those access details.' } });
    return;
  }
  await new Promise<void>((resolve, reject) => request.session.regenerate((error) => error ? reject(error) : resolve()));
  request.session.authorizedOrderNumbers = [orderNumber];
  response.json({ data: { order: orderDto(order) }, error: null });
});

ordersRouter.get('/orders/:orderNumber', async (request, response) => {
  const orderNumber = Number(request.params.orderNumber);
  if (!Number.isSafeInteger(orderNumber) || orderNumber < 1 || !isAuthorized(request, orderNumber)) throw new ApiError(403, 'ORDER_ACCESS_REQUIRED', 'Enter your order access details to view this order.');
  const order = await Order.findOne({ orderNumber }).lean();
  if (!order) throw new ApiError(404, 'ORDER_NOT_FOUND', 'Order not found.');
  response.json({ data: { order: orderDto(order) }, error: null });
});
