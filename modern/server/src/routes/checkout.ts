import { Router } from 'express';
import { requireCsrf } from '../middleware/csrfGuard.js';
import { ApiError } from '../middleware/errors.js';
import { cartDto, type CartMap } from '../cart/service.js';
import { checkoutValuesFingerprint, createOrder, validateCheckout } from '../orders/checkout.js';
import { sendLocalReceipt } from '../mail/receipt.js';
import { findOrderByNumber, getOrderEmailStatus, setOrderEmailStatus } from '../orders/repository.js';

export const checkoutRouter = Router();
const sessionCart = (request: { session: { cart?: CartMap } }) => request.session.cart ?? {};

checkoutRouter.get('/checkout', async (request, response) => {
  const cart = await cartDto(sessionCart(request));
  if (!cart.items.length) throw new ApiError(409, 'EMPTY_CART', 'Your bag is empty.');
  response.json({ data: cart, error: null });
});

checkoutRouter.post('/checkout', requireCsrf, async (request, response) => {
  const key = request.get('idempotency-key') ?? '';
  if (!/^[A-Za-z0-9_-]{16,80}$/.test(key)) throw new ApiError(400, 'IDEMPOTENCY_KEY_REQUIRED', 'Refresh checkout and try again.');
  const { values, errors } = validateCheckout(request.body);
  if (Object.keys(errors).length) { response.status(422).json({ data: { values, errors }, error: { code: 'CHECKOUT_INVALID', message: 'Please correct the highlighted fields.' } }); return; }
  const previous = request.session.checkoutLast;
  if (previous?.idempotencyKey === key && previous.valuesFingerprint && previous.valuesFingerprint !== checkoutValuesFingerprint(values)) {
    response.status(409).json({ data: null, error: { code: 'IDEMPOTENCY_KEY_REUSED', message: 'This checkout key was already used for a different order.' } }); return;
  }
  if (previous?.idempotencyKey === key && !Object.keys(sessionCart(request)).length) {
    const existing = await findOrderByNumber(previous.orderNumber);
    if (existing) {
      response.status(200).json({ data: { orderNumber: existing.orderNumber, status: existing.status, confirmationUrl: `/confirmation/${existing.orderNumber}`, emailStatus: existing.emailStatus === 'sent' ? 'already_sent' : existing.emailStatus }, error: null });
      return;
    }
  }
  const result = await createOrder(sessionCart(request), values, key);
  if (result.conflict) {
    response.status(409).json({ data: null, error: { code: 'IDEMPOTENCY_KEY_REUSED', message: 'This checkout key was already used for a different order.' } }); return;
  }
  if (!result.created && request.session.checkoutLast?.idempotencyKey !== key) {
    response.status(409).json({ data: null, error: { code: 'DUPLICATE_CHECKOUT', message: 'This checkout was already submitted. Open your order confirmation.' } }); return;
  }
  if (result.created) {
    request.session.cart = {};
    request.session.checkoutLast = { idempotencyKey: key, orderNumber: result.orderNumber, valuesFingerprint: checkoutValuesFingerprint(values), accessCode: result.accessCode };
    request.session.authorizedOrderNumbers = [...new Set([...(request.session.authorizedOrderNumbers ?? []), result.orderNumber])];
  }
  let emailStatus: string;
  if (result.created) {
    try { emailStatus = await sendLocalReceipt(result.orderNumber); }
    catch {
      emailStatus = 'failed';
      await setOrderEmailStatus(result.orderNumber, 'failed').catch(() => undefined);
    }
  } else {
    try {
      const storedStatus = await getOrderEmailStatus(result.orderNumber) ?? 'disabled';
      emailStatus = storedStatus === 'sent' ? 'already_sent' : storedStatus;
    }
    catch { emailStatus = 'queued'; }
  }
  response.status(result.created ? 201 : 200).json({ data: { orderNumber: result.orderNumber, status: 'ORDER_PLACED', confirmationUrl: `/confirmation/${result.orderNumber}`, emailStatus }, error: null });
});

checkoutRouter.get('/orders/:orderNumber/confirmation', async (request, response) => {
  const orderNumber = Number(request.params.orderNumber);
  if (!Number.isSafeInteger(orderNumber) || orderNumber < 1) throw new ApiError(404, 'ORDER_NOT_FOUND', 'Order not found.');
  const allowed = request.session.authorizedOrderNumbers?.includes(orderNumber);
  if (!allowed) throw new ApiError(403, 'ORDER_ACCESS_REQUIRED', 'Enter your order access details to view this order.');
  const order = await findOrderByNumber(orderNumber);
  if (!order) throw new ApiError(404, 'ORDER_NOT_FOUND', 'Order not found.');
  const initialCode = request.session.checkoutLast?.orderNumber === orderNumber ? request.session.checkoutLast.accessCode : undefined;
  if (initialCode) { delete request.session.checkoutLast?.accessCode; }
  response.setHeader('Cache-Control', 'no-store');
  const emailStatus = order.emailStatus === 'sent' && !initialCode ? 'already_sent' : order.emailStatus;
  response.json({ data: { orderNumber, status: order.status, items: order.items, subtotalCents: order.subtotalCents, discountCents: order.discountCents, deliveryCents: order.deliveryCents, totalCents: order.totalCents, accessCode: initialCode ?? null, emailStatus }, error: null });
});
