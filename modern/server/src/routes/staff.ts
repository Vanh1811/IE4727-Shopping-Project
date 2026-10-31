import { Router, type Request, type Response, type NextFunction } from 'express';
import { isValidObjectId, Types } from 'mongoose';
import { rateLimit } from 'express-rate-limit';
import { requireCsrf } from '../middleware/csrfGuard.js';
import { ApiError } from '../middleware/errors.js';
import { Order } from '../models/Order.js';
import { Mockup } from '../models/Mockup.js';
import { requireStaff, rotateStaffSession, staffIsConfigured, verifyStaffPassword } from '../staff/auth.js';
import { listMockupAssets, resolveMockupAsset, validateMockupInput } from '../staff/mockups.js';
import { canMoveOrderStatus, nextMockupRevision, orderStatusAfterReview } from '../staff/workflow.js';

export const staffRouter = Router();
const loginLimiter = rateLimit({ windowMs: 15 * 60_000, limit: 10, skipSuccessfulRequests: true, standardHeaders: 'draft-8', legacyHeaders: false });

function staffOnly(request: Request, response: Response, next: NextFunction) {
  if (!requireStaff(request)) { response.status(401).json({ data: null, error: { code: 'STAFF_AUTH_REQUIRED', message: 'Staff sign-in is required.' } }); return; }
  next();
}

function positiveId(value: unknown) {
  if (!(typeof value === 'string' && /^\d{1,20}$/.test(value)) && !(typeof value === 'number' && Number.isSafeInteger(value))) return null;
  const number = Number(value);
  return Number.isSafeInteger(number) && number > 0 ? number : null;
}

function staffOrderDto(order: { orderNumber: number; status: string; createdAt: Date; fullName: string; email: string; phone: string; postalCode: string; deliveryAddress: string; specialInstructions: string; items: { _id: Types.ObjectId; productId: number; productName: string; image: string; size: string; colour: string; quantity: number; unitPriceCents: number; lineTotalCents: number }[]; subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number }) {
  return {
    orderNumber: order.orderNumber, status: order.status, createdAt: order.createdAt,
    fullName: order.fullName, email: order.email, phone: order.phone,
    postalCode: order.postalCode, deliveryAddress: order.deliveryAddress,
    specialInstructions: order.specialInstructions,
    items: order.items.map((item) => ({ id: item._id.toString(), productId: item.productId, productName: item.productName, image: item.image, size: item.size, colour: item.colour, quantity: item.quantity, unitPriceCents: item.unitPriceCents, lineTotalCents: item.lineTotalCents })),
    subtotalCents: order.subtotalCents, discountCents: order.discountCents,
    deliveryCents: order.deliveryCents, totalCents: order.totalCents,
  };
}

staffRouter.get('/staff/session', (request, response) => response.json({ data: { configured: staffIsConfigured(), authenticated: requireStaff(request) }, error: null }));
staffRouter.post('/staff/login', loginLimiter, requireCsrf, async (request, response) => {
  if (!staffIsConfigured()) throw new ApiError(503, 'STAFF_NOT_CONFIGURED', 'Staff access is not configured on this server.');
  if (!verifyStaffPassword(request.body?.password)) throw new ApiError(401, 'STAFF_LOGIN_FAILED', 'Staff access could not be verified.');
  await rotateStaffSession(request, true);
  response.json({ data: { authenticated: true }, error: null });
});
staffRouter.post('/staff/logout', staffOnly, requireCsrf, async (request, response) => {
  await rotateStaffSession(request, false);
  response.json({ data: { authenticated: false }, error: null });
});

staffRouter.get('/staff/orders', staffOnly, async (request, response) => {
  const page = positiveId(typeof request.query.page === 'string' ? request.query.page : '1') ?? 1;
  const limit = Math.min(50, positiveId(typeof request.query.limit === 'string' ? request.query.limit : '20') ?? 20);
  const status = typeof request.query.status === 'string' ? request.query.status : '';
  const allowed = ['ORDER_PLACED','DESIGN_REVIEW','CHANGES_REQUESTED','CONFIRMED','PROCESSING','COMPLETED'];
  const filter = status && allowed.includes(status) ? { status } : {};
  const [orders, total] = await Promise.all([
    Order.find(filter).sort({ createdAt: -1, orderNumber: -1 }).skip((Math.min(page, 1_000_000) - 1) * limit).limit(limit).lean(),
    Order.countDocuments(filter),
  ]);
  const mockups = await Mockup.find({ orderNumber: { $in: orders.map((order) => order.orderNumber) } }).lean();
  const byItem = new Map(mockups.map((mockup) => [mockup.orderItemId.toString(), mockup]));
  response.json({ data: { orders: orders.map((order) => ({ ...staffOrderDto(order), items: order.items.map((item) => ({ ...item, id: item._id.toString(), mockup: byItem.get(item._id.toString()) ?? null })) })), pagination: { page, limit, total, totalPages: Math.ceil(total / limit) } }, error: null });
});

staffRouter.get('/staff/mockup-assets', staffOnly, async (_request, response) => response.json({ data: { assets: await listMockupAssets() }, error: null }));

staffRouter.post('/staff/orders/:orderNumber/items/:itemId/mockup', staffOnly, requireCsrf, async (request, response) => {
  const orderNumber = positiveId(request.params.orderNumber);
  const itemId = request.params.itemId;
  if (!orderNumber || !isValidObjectId(itemId)) throw new ApiError(422, 'INVALID_MOCKUP', 'Please provide valid mock-up details and a local product image.');
  const input = validateMockupInput(request.body);
  const previewImage = await resolveMockupAsset(input.previewImage);
  if (!previewImage) throw new ApiError(422, 'INVALID_MOCKUP', 'Please provide valid mock-up details and a local product image.');
  const transaction = await Order.startSession();
  try {
    await transaction.withTransaction(async () => {
      const order = await Order.findOne({ orderNumber }).session(transaction);
      if (!order || !['ORDER_PLACED','DESIGN_REVIEW','CHANGES_REQUESTED'].includes(order.status)) throw new ApiError(409, 'MOCKUP_ORDER_STATE', 'Mock-ups can only be published while the order is awaiting design work.');
      const orderItem = order.items.find((item) => item._id.toString() === String(itemId));
      if (!orderItem) throw new ApiError(404, 'ORDER_ITEM_NOT_FOUND', 'The selected order item does not belong to this order.');
      let mockup = await Mockup.findOne({ orderNumber, orderItemId: orderItem._id }).session(transaction);
      const revision = nextMockupRevision(mockup?.status ?? null, mockup?.revision ?? 0);
      if (revision === null) throw new ApiError(409, mockup?.status === 'APPROVED' ? 'MOCKUP_APPROVED' : 'MOCKUP_PENDING', mockup?.status === 'APPROVED' ? 'Approved mock-ups cannot be overwritten.' : 'This mock-up is already waiting for customer review.');
      if (mockup) {
        mockup.previewImage = previewImage;
        mockup.printPosition = input.printPosition;
        mockup.printColour = input.printColour;
        mockup.customText = input.customText;
        mockup.revision = revision;
        mockup.status = 'READY_FOR_REVIEW';
        await mockup.save({ session: transaction, validateModifiedOnly: false });
      } else {
        [mockup] = await Mockup.create([{ orderNumber, orderItemId: orderItem._id, previewImage, printPosition: input.printPosition, printColour: input.printColour, customText: input.customText, status: 'READY_FOR_REVIEW', revision }], { session: transaction });
      }
      order.status = 'DESIGN_REVIEW';
      await order.save({ session: transaction });
      response.locals.mockupId = mockup._id.toString();
      response.locals.revision = mockup.revision;
    });
  } finally { await transaction.endSession(); }
  response.status(201).json({ data: { mockupId: response.locals.mockupId, revision: response.locals.revision }, error: null });
});

staffRouter.post('/staff/orders/:orderNumber/status', staffOnly, requireCsrf, async (request, response) => {
  const orderNumber = positiveId(request.params.orderNumber);
  const nextStatus = request.body?.status;
  if (!orderNumber || !['PROCESSING','COMPLETED'].includes(nextStatus)) throw new ApiError(422, 'INVALID_ORDER_TRANSITION', 'The requested order status is not valid.');
  const current = await Order.findOne({ orderNumber });
  const allowed = current && canMoveOrderStatus(current.status, nextStatus);
  if (!allowed) throw new ApiError(409, 'INVALID_ORDER_TRANSITION', 'The requested order status is not valid.');
  const updated = await Order.findOneAndUpdate({ orderNumber, status: current.status }, { $set: { status: nextStatus } }, { new: true, runValidators: true });
  if (!updated) throw new ApiError(409, 'INVALID_ORDER_TRANSITION', 'The requested order status is not valid.');
  response.json({ data: { orderNumber, status: updated.status }, error: null });
});

staffRouter.get('/mockups/:id', async (request, response) => {
  if (!isValidObjectId(request.params.id)) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That design preview is not available to this guest session.');
  const mockup = await Mockup.findById(request.params.id).lean();
  if (!mockup || !request.session.authorizedOrderNumbers?.includes(mockup.orderNumber)) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That design preview is not available to this guest session.');
  const order = await Order.findOne({ orderNumber: mockup.orderNumber }).lean();
  const item = order?.items.find((entry) => entry._id.toString() === mockup.orderItemId.toString());
  if (!order || !item) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That design preview is not available to this guest session.');
  response.json({ data: { mockupId: mockup._id.toString(), orderNumber: order.orderNumber, productName: item.productName, size: item.size, colour: item.colour, previewImage: mockup.previewImage, printPosition: mockup.printPosition, printColour: mockup.printColour, customText: mockup.customText, status: mockup.status, revision: mockup.revision, reviews: mockup.reviews }, error: null });
});

staffRouter.post('/mockups/:id/review', requireCsrf, async (request, response) => {
  const invalid = () => new ApiError(422, 'INVALID_REVIEW', 'That review request is not valid.');
  const id = request.params.id;
  const expectedRevision = positiveId(request.body?.expectedRevision);
  const decision = request.body?.decision;
  const changeRequest = typeof request.body?.changeRequest === 'string' ? request.body.changeRequest.trim() : '';
  if (!isValidObjectId(id) || !expectedRevision || !['approve','request_changes'].includes(decision)) throw invalid();
  if (decision === 'request_changes' && (!changeRequest || Buffer.byteLength(changeRequest) > 500 || hasReviewControls(changeRequest))) throw new ApiError(422, 'CHANGE_REQUEST_REQUIRED', 'Please describe the changes you would like to request.');
  if (Buffer.byteLength(changeRequest) > 500 || hasReviewControls(changeRequest)) throw invalid();
  const initial = await Mockup.findById(id).lean();
  if (!initial || !request.session.authorizedOrderNumbers?.includes(initial.orderNumber)) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That review is not available to this guest session.');
  const transaction = await Order.startSession();
  let nextOrderStatus: 'CHANGES_REQUESTED'|'DESIGN_REVIEW'|'CONFIRMED' = 'DESIGN_REVIEW';
  try {
    await transaction.withTransaction(async () => {
      const mockup = await Mockup.findById(id).session(transaction);
      const order = await Order.findOne({ orderNumber: initial.orderNumber }).session(transaction);
      if (!mockup || !order || mockup.status !== 'READY_FOR_REVIEW' || mockup.revision !== expectedRevision) throw new ApiError(409, 'REVISION_STALE', 'This mock-up revision is no longer awaiting review.');
      if (!order.items.some((item) => item._id.toString() === mockup.orderItemId.toString())) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That review is not available to this guest session.');
      mockup.reviews.push({ decision, changeRequest: decision === 'request_changes' ? changeRequest : '', revision: expectedRevision, createdAt: new Date() });
      mockup.status = decision === 'approve' ? 'APPROVED' : 'CHANGES_REQUESTED';
      await mockup.save({ session: transaction, validateModifiedOnly: false });
      const allMockups = await Mockup.find({ orderNumber: order.orderNumber }).session(transaction).lean();
      const approvedIds = allMockups.filter((entry) => entry.status === 'APPROVED').map((entry) => entry.orderItemId.toString());
      nextOrderStatus = orderStatusAfterReview(decision, order.items.map((item) => item._id.toString()), approvedIds);
      order.status = nextOrderStatus;
      await order.save({ session: transaction });
    });
  } finally { await transaction.endSession(); }
  response.json({ data: { status: decision === 'approve' ? 'APPROVED' : 'CHANGES_REQUESTED', orderStatus: nextOrderStatus }, error: null });
});

// Match the legacy review validator's excluded C0 control bytes.
// eslint-disable-next-line no-control-regex
function hasReviewControls(value: string) { return /[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/.test(value); }
