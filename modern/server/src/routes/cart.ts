import { Router } from 'express';
import { ApiError } from '../middleware/errors.js';
import { requireCsrf } from '../middleware/csrfGuard.js';
import { cartDto, cartKey, validateCartLine, type CartMap } from '../cart/service.js';

export const cartRouter = Router();
const getCart = (request: { session: { cart?: CartMap } }) => request.session.cart ?? {};

cartRouter.get('/cart', async (request, response) => response.json({ data: await cartDto(getCart(request)), error: null }));
cartRouter.post('/cart/items', requireCsrf, async (request, response) => {
  const line = await validateCartLine(request.body);
  const cart = getCart(request);
  const key = cartKey(line);
  const quantity = (cart[key]?.quantity ?? 0) + line.quantity;
  if (quantity > 99) throw new ApiError(422, 'QUANTITY_LIMIT', 'A product option cannot exceed a quantity of 99.');
  cart[key] = { ...line, quantity };
  request.session.cart = cart;
  response.status(201).json({ data: await cartDto(cart), error: null });
});
cartRouter.patch('/cart/items/:key', requireCsrf, async (request, response) => {
  const cart = getCart(request); const key = String(request.params.key);
  if (!cart[key]) throw new ApiError(404, 'CART_ITEM_NOT_FOUND', 'That item is no longer in your bag.');
  const quantity = await validateCartLine({ ...cart[key], quantity: request.body?.quantity });
  cart[key] = quantity; request.session.cart = cart;
  response.json({ data: await cartDto(cart), error: null });
});
cartRouter.delete('/cart/items/:key', requireCsrf, async (request, response) => {
  const cart = getCart(request); delete cart[String(request.params.key)]; request.session.cart = cart;
  response.json({ data: await cartDto(cart), error: null });
});
cartRouter.delete('/cart', requireCsrf, async (request, response) => {
  request.session.cart = {};
  response.json({ data: await cartDto({}), error: null });
});
