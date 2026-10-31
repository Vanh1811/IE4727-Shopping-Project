import { Router, type Request, type Response, type NextFunction } from 'express';
import type { RowDataPacket } from 'mysql2/promise';
import { rateLimit } from 'express-rate-limit';
import { pool } from '../db/pool.js';
import { requireCsrf } from '../middleware/csrfGuard.js';
import { ApiError } from '../middleware/errors.js';
import { findOrdersByNumbers } from '../orders/repository.js';
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
const orderStatuses = ['ORDER_PLACED','DESIGN_REVIEW','CHANGES_REQUESTED','CONFIRMED','PROCESSING','COMPLETED'];
type StaffOrder = Awaited<ReturnType<typeof findOrdersByNumbers>>[number];
function staffOrderDto(order: StaffOrder) {
  return {
    orderNumber: order.orderNumber, status: order.status, createdAt: order.createdAt,
    fullName: order.fullName, email: order.email, phone: order.phone,
    postalCode: order.postalCode, deliveryAddress: order.deliveryAddress,
    specialInstructions: order.specialInstructions,
    items: order.items.map((item) => ({ _id: String(item.id), id: String(item.id), productId: item.productId, productSlug: item.productSlug, productName: item.productName, image: item.image, size: item.size, colour: item.colour, quantity: item.quantity, unitPriceCents: item.unitPriceCents, lineTotalCents: item.lineTotalCents })),
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
  const requestedStatus = typeof request.query.status === 'string' ? request.query.status : '';
  const hasStatus = orderStatuses.includes(requestedStatus);
  const where = hasStatus ? ' WHERE status = ?' : '';
  const params = hasStatus ? [requestedStatus] : [];
  const offset = (Math.min(page, 1_000_000) - 1) * limit;
  const connection = await pool.getConnection();
  let ids: number[];
  let total: number;
  let mockupRows: (RowDataPacket & { id: number; order_id: number; order_item_id: number; preview_image: string; print_position: string; print_colour: string; custom_text: string; status: string; revision: number; created_at: Date; updated_at: Date })[];
  let reviewRows: (RowDataPacket & { id: number; mockup_id: number; decision: string; change_request: string; revision: number; created_at: Date })[];
  try {
    const [countRows] = await connection.execute<(RowDataPacket & { total: number|string })[]>(`SELECT COUNT(*) AS total FROM orders${where}`, params);
    total = Number(countRows[0]?.total ?? 0);
    const [orderRows] = await connection.execute<(RowDataPacket & { id: number })[]>(`SELECT id FROM orders${where} ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?`, [...params, limit, offset]);
    ids = orderRows.map((row) => Number(row.id));
    if (!ids.length) { mockupRows = []; reviewRows = []; }
    else {
      const [mocks] = await connection.execute<typeof mockupRows>(
        `SELECT id, order_id, order_item_id, preview_image, print_position, print_colour, custom_text, status, revision, created_at, updated_at
         FROM mockups WHERE order_id IN (${ids.map(() => '?').join(',')}) ORDER BY id`, ids,
      );
      mockupRows = mocks;
      const mockIds = mocks.map((mockup) => Number(mockup.id));
      if (!mockIds.length) reviewRows = [];
      else {
        const [reviews] = await connection.execute<typeof reviewRows>(
          `SELECT id, mockup_id, decision, change_request, revision, created_at FROM mockup_reviews
           WHERE mockup_id IN (${mockIds.map(() => '?').join(',')}) ORDER BY id`, mockIds,
        );
        reviewRows = reviews;
      }
    }
  } finally { connection.release(); }
  const orders = await findOrdersByNumbers(ids);
  const reviewsByMock = new Map<number, typeof reviewRows>();
  for (const review of reviewRows) reviewsByMock.set(Number(review.mockup_id), [...(reviewsByMock.get(Number(review.mockup_id)) ?? []), review]);
  const mockByItem = new Map<number, Record<string, unknown>>();
  for (const mock of mockupRows) mockByItem.set(Number(mock.order_item_id), {
    _id: String(mock.id), orderNumber: Number(mock.order_id), orderItemId: String(mock.order_item_id),
    previewImage: mock.preview_image, printPosition: mock.print_position, printColour: mock.print_colour,
    customText: mock.custom_text, status: mock.status, revision: Number(mock.revision),
      reviews: (reviewsByMock.get(Number(mock.id)) ?? []).map((review) => ({ _id: String(review.id), decision: review.decision, changeRequest: review.change_request, revision: Number(review.revision), createdAt: review.created_at })),
  });
  response.json({ data: { orders: orders.map((order) => ({ ...staffOrderDto(order), items: order.items.map((item) => ({ ...item, mockup: mockByItem.get(item.id) ?? null })) })), pagination: { page, limit, total, totalPages: Math.ceil(total / limit) } }, error: null });
});

staffRouter.get('/staff/mockup-assets', staffOnly, async (_request, response) => response.json({ data: { assets: await listMockupAssets() }, error: null }));

staffRouter.post('/staff/orders/:orderNumber/items/:itemId/mockup', staffOnly, requireCsrf, async (request, response) => {
  const orderNumber = positiveId(request.params.orderNumber);
  const itemId = positiveId(request.params.itemId);
  if (!orderNumber || !itemId) throw new ApiError(422, 'INVALID_MOCKUP', 'Please provide valid mock-up details and a local product image.');
  const input = validateMockupInput(request.body);
  const previewImage = await resolveMockupAsset(input.previewImage);
  if (!previewImage) throw new ApiError(422, 'INVALID_MOCKUP', 'Please provide valid mock-up details and a local product image.');
  const connection = await pool.getConnection();
  let mockupId = 0;
  let revision = 0;
  try {
    await connection.beginTransaction();
    const [orders] = await connection.execute<(RowDataPacket & { id: number; status: string })[]>(`SELECT id, status FROM orders WHERE id = ? FOR UPDATE`, [orderNumber]);
    const order = orders[0];
    if (!order || !['ORDER_PLACED','DESIGN_REVIEW','CHANGES_REQUESTED'].includes(order.status)) throw new ApiError(409, 'MOCKUP_ORDER_STATE', 'Mock-ups can only be published while the order is awaiting design work.');
    const [items] = await connection.execute<(RowDataPacket & { id: number })[]>('SELECT id FROM order_items WHERE id = ? AND order_id = ? LIMIT 1', [itemId, orderNumber]);
    if (!items[0]) throw new ApiError(404, 'ORDER_ITEM_NOT_FOUND', 'The selected order item does not belong to this order.');
    const [mocks] = await connection.execute<(RowDataPacket & { id: number; status: 'READY_FOR_REVIEW'|'CHANGES_REQUESTED'|'APPROVED'; revision: number })[]>(
      'SELECT id, status, revision FROM mockups WHERE order_item_id = ? FOR UPDATE', [itemId],
    );
    const current = mocks[0];
    revision = nextMockupRevision(current?.status ?? null, Number(current?.revision ?? 0)) ?? 0;
    if (!revision) throw new ApiError(409, current?.status === 'APPROVED' ? 'MOCKUP_APPROVED' : 'MOCKUP_PENDING', current?.status === 'APPROVED' ? 'Approved mock-ups cannot be overwritten.' : 'This mock-up is already waiting for customer review.');
    if (current) {
      mockupId = Number(current.id);
      await connection.execute(
        `UPDATE mockups SET preview_image = ?, print_position = ?, print_colour = ?, custom_text = ?, revision = ?, status = 'READY_FOR_REVIEW' WHERE id = ? AND status = 'CHANGES_REQUESTED' AND revision = ?`,
        [previewImage, input.printPosition, input.printColour, input.customText, revision, mockupId, current.revision],
      );
    } else {
      const [insert] = await connection.execute(
        `INSERT INTO mockups (order_id, order_item_id, preview_image, print_position, print_colour, custom_text, status, revision)
         VALUES (?, ?, ?, ?, ?, ?, 'READY_FOR_REVIEW', 1)`,
        [orderNumber, itemId, previewImage, input.printPosition, input.printColour, input.customText],
      );
      mockupId = Number('insertId' in insert ? insert.insertId : 0);
      revision = 1;
    }
    await connection.execute("UPDATE orders SET status = 'DESIGN_REVIEW' WHERE id = ?", [orderNumber]);
    await connection.commit();
  } catch (error) { await connection.rollback().catch(() => undefined); throw error; }
  finally { connection.release(); }
  response.status(201).json({ data: { mockupId: String(mockupId), revision }, error: null });
});

staffRouter.post('/staff/orders/:orderNumber/status', staffOnly, requireCsrf, async (request, response) => {
  const orderNumber = positiveId(request.params.orderNumber);
  const nextStatus = request.body?.status;
  if (!orderNumber || !['PROCESSING','COMPLETED'].includes(nextStatus)) throw new ApiError(422, 'INVALID_ORDER_TRANSITION', 'The requested order status is not valid.');
  const connection = await pool.getConnection();
  let updatedStatus = '';
  try {
    await connection.beginTransaction();
    const [rows] = await connection.execute<(RowDataPacket & { status: string })[]>('SELECT status FROM orders WHERE id = ? FOR UPDATE', [orderNumber]);
    const current = rows[0]?.status;
    if (!current || !canMoveOrderStatus(current, nextStatus)) throw new ApiError(409, 'INVALID_ORDER_TRANSITION', 'The requested order status is not valid.');
    const [result] = await connection.execute('UPDATE orders SET status = ? WHERE id = ? AND status = ?', [nextStatus, orderNumber, current]);
    if (!('affectedRows' in result) || Number(result.affectedRows) !== 1) throw new ApiError(409, 'INVALID_ORDER_TRANSITION', 'The requested order status is not valid.');
    updatedStatus = nextStatus;
    await connection.commit();
  } catch (error) { await connection.rollback().catch(() => undefined); throw error; }
  finally { connection.release(); }
  response.json({ data: { orderNumber, status: updatedStatus }, error: null });
});

staffRouter.get('/mockups/:id', async (request, response) => {
  const mockupId = positiveId(request.params.id);
  if (!mockupId) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That design preview is not available to this guest session.');
  const connection = await pool.getConnection();
  try {
    const [rows] = await connection.execute<(RowDataPacket & { id: number; order_id: number; order_item_id: number; preview_image: string; print_position: string; print_colour: string; custom_text: string; status: string; revision: number })[]>(
      'SELECT id, order_id, order_item_id, preview_image, print_position, print_colour, custom_text, status, revision FROM mockups WHERE id = ? LIMIT 1', [mockupId],
    );
    const mockup = rows[0];
    if (!mockup || !request.session.authorizedOrderNumbers?.includes(Number(mockup.order_id))) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That design preview is not available to this guest session.');
    const [items] = await connection.execute<(RowDataPacket & { product_name: string; size: string; colour: string })[]>(
      'SELECT product_name, size, colour FROM order_items WHERE id = ? AND order_id = ? LIMIT 1', [mockup.order_item_id, mockup.order_id],
    );
    if (!items[0]) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That design preview is not available to this guest session.');
    const [reviews] = await connection.execute<(RowDataPacket & { id: number; decision: string; change_request: string; revision: number; created_at: Date })[]>(
      'SELECT id, decision, change_request, revision, created_at FROM mockup_reviews WHERE mockup_id = ? ORDER BY id', [mockupId],
    );
    response.json({ data: { mockupId: String(mockup.id), orderNumber: Number(mockup.order_id), productName: items[0].product_name, size: items[0].size, colour: items[0].colour, previewImage: mockup.preview_image, printPosition: mockup.print_position, printColour: mockup.print_colour, customText: mockup.custom_text, status: mockup.status, revision: Number(mockup.revision), reviews: reviews.map((review) => ({ _id: String(review.id), decision: review.decision, changeRequest: review.change_request, revision: Number(review.revision), createdAt: review.created_at })) }, error: null });
  } finally { connection.release(); }
});

staffRouter.post('/mockups/:id/review', requireCsrf, async (request, response) => {
  const invalid = () => new ApiError(422, 'INVALID_REVIEW', 'That review request is not valid.');
  const id = positiveId(request.params.id);
  const expectedRevision = positiveId(request.body?.expectedRevision);
  const decision = request.body?.decision;
  const changeRequest = typeof request.body?.changeRequest === 'string' ? request.body.changeRequest.trim() : '';
  if (!id || !expectedRevision || !['approve','request_changes'].includes(decision)) throw invalid();
  if (decision === 'request_changes' && (!changeRequest || Buffer.byteLength(changeRequest) > 500 || hasReviewControls(changeRequest))) throw new ApiError(422, 'CHANGE_REQUEST_REQUIRED', 'Please describe the changes you would like to request.');
  if (Buffer.byteLength(changeRequest) > 500 || hasReviewControls(changeRequest)) throw invalid();
  const connection = await pool.getConnection();
  let nextOrderStatus: 'CHANGES_REQUESTED'|'DESIGN_REVIEW'|'CONFIRMED' = 'DESIGN_REVIEW';
  try {
    await connection.beginTransaction();
    const [initialMocks] = await connection.execute<(RowDataPacket & { id: number; order_id: number })[]>(
      'SELECT id, order_id FROM mockups WHERE id = ? LIMIT 1', [id],
    );
    const initial = initialMocks[0];
    if (!initial || !request.session.authorizedOrderNumbers?.includes(Number(initial.order_id))) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That review is not available to this guest session.');
    const [orders] = await connection.execute<(RowDataPacket & { status: string })[]>('SELECT status FROM orders WHERE id = ? FOR UPDATE', [initial.order_id]);
    const [mocks] = await connection.execute<(RowDataPacket & { id: number; order_id: number; order_item_id: number; status: string; revision: number })[]>(
      'SELECT id, order_id, order_item_id, status, revision FROM mockups WHERE id = ? AND order_id = ? FOR UPDATE', [id, initial.order_id],
    );
    const mockup = mocks[0];
    const [items] = await connection.execute<(RowDataPacket & { id: number })[]>('SELECT id FROM order_items WHERE order_id = ? ORDER BY id', [initial.order_id]);
    if (!orders[0] || !items.some((item) => Number(item.id) === Number(mockup.order_item_id))) throw new ApiError(404, 'MOCKUP_NOT_FOUND', 'That review is not available to this guest session.');
    if (mockup.status !== 'READY_FOR_REVIEW' || Number(mockup.revision) !== expectedRevision) throw new ApiError(409, 'REVISION_STALE', 'This mock-up revision is no longer awaiting review.');
    const nextMockStatus = decision === 'approve' ? 'APPROVED' : 'CHANGES_REQUESTED';
    const [update] = await connection.execute(
      'UPDATE mockups SET status = ? WHERE id = ? AND status = \'READY_FOR_REVIEW\' AND revision = ?', [nextMockStatus, id, expectedRevision],
    );
    if (!('affectedRows' in update) || Number(update.affectedRows) !== 1) throw new ApiError(409, 'REVISION_STALE', 'This mock-up revision is no longer awaiting review.');
    await connection.execute('INSERT INTO mockup_reviews (mockup_id, decision, change_request, revision) VALUES (?, ?, ?, ?)', [id, decision, decision === 'request_changes' ? changeRequest : '', expectedRevision]);
    const [approved] = await connection.execute<(RowDataPacket & { order_item_id: number })[]>("SELECT order_item_id FROM mockups WHERE order_id = ? AND status = 'APPROVED'", [mockup.order_id]);
    nextOrderStatus = orderStatusAfterReview(decision as 'approve'|'request_changes', items.map((item) => String(item.id)), approved.map((item) => String(item.order_item_id)));
    await connection.execute('UPDATE orders SET status = ? WHERE id = ?', [nextOrderStatus, mockup.order_id]);
    await connection.commit();
  } catch (error) { await connection.rollback().catch(() => undefined); throw error; }
  finally { connection.release(); }
  response.json({ data: { status: decision === 'approve' ? 'APPROVED' : 'CHANGES_REQUESTED', orderStatus: nextOrderStatus }, error: null });
});

// Match the legacy review validator's excluded C0 control bytes.
// eslint-disable-next-line no-control-regex
function hasReviewControls(value: string) { return /[\u0000-\u0008\u000b\u000c-\u001f\u007f]/.test(value); }
