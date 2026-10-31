import type { PoolConnection, RowDataPacket } from 'mysql2/promise';
import { pool } from '../db/pool.js';
import { decimalToCents } from '../db/money.js';

export type OrderLine = {
  id: number; productId: number; productSlug: string; productName: string; image: string;
  size: string; colour: string; unitPriceCents: number; quantity: number; lineTotalCents: number;
};
export type OrderRecord = {
  orderNumber: number; fullName: string; email: string; phone: string; postalCode: string;
  deliveryAddress: string; specialInstructions: string; status: string; accessCodeHash: string;
  idempotencyKey: string; checkoutFingerprint: string|null; emailStatus: 'disabled'|'queued'|'failed'|'sent'; emailSentAt: Date|null;
  createdAt: Date; subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number;
  items: OrderLine[];
};
type OrderRow = RowDataPacket & {
  id: number; full_name: string; email: string; phone: string; postal_code: string; delivery_address: string;
  special_instructions: string; status: string; access_token_hash: string; idempotency_key: string; checkout_fingerprint: string|null;
  email_status: OrderRecord['emailStatus']; email_sent_at: Date|null; created_at: Date;
  subtotal: string; discount: string; delivery_fee: string; total: string;
};
type ItemRow = RowDataPacket & {
  id: number; product_id: number; product_slug: string; product_name: string; image: string; size: string;
  colour: string; unit_price: string; quantity: number; line_total: string;
};

function mapOrder(row: OrderRow, items: OrderLine[] = []): OrderRecord {
  const subtotal = decimalToCents(row.subtotal);
  const discount = decimalToCents(row.discount);
  const delivery = decimalToCents(row.delivery_fee);
  const total = decimalToCents(row.total);
  if (subtotal === null || discount === null || delivery === null || total === null) throw new Error('Order monetary snapshot is incomplete.');
  return {
    orderNumber: Number(row.id), fullName: row.full_name, email: row.email, phone: row.phone,
    postalCode: row.postal_code, deliveryAddress: row.delivery_address, specialInstructions: row.special_instructions,
    status: row.status, accessCodeHash: row.access_token_hash, idempotencyKey: row.idempotency_key, checkoutFingerprint: row.checkout_fingerprint,
    emailStatus: row.email_status, emailSentAt: row.email_sent_at, createdAt: row.created_at,
    subtotalCents: subtotal, discountCents: discount,
    deliveryCents: delivery, totalCents: total, items,
  };
}

function mapLine(row: ItemRow): OrderLine {
  const unitPrice = decimalToCents(row.unit_price);
  const lineTotal = decimalToCents(row.line_total);
  if (unitPrice === null || lineTotal === null) throw new Error('Order item monetary snapshot is incomplete.');
  return {
    id: Number(row.id), productId: Number(row.product_id), productSlug: row.product_slug,
    productName: row.product_name, image: row.image, size: row.size, colour: row.colour,
    unitPriceCents: unitPrice, quantity: Number(row.quantity), lineTotalCents: lineTotal,
  };
}

async function getLines(connection: PoolConnection, orderIds: number[]) {
  if (!orderIds.length) return new Map<number, OrderLine[]>();
  const [rows] = await connection.execute<ItemRow[]>(
    `SELECT id, order_id, product_id, product_slug, product_name, image, size, colour, unit_price, quantity, line_total
     FROM order_items WHERE order_id IN (${orderIds.map(() => '?').join(',')}) ORDER BY order_id, id`, orderIds,
  );
  const grouped = new Map<number, OrderLine[]>();
  for (const row of rows) grouped.set(Number(row.order_id), [...(grouped.get(Number(row.order_id)) ?? []), mapLine(row)]);
  return grouped;
}

const orderColumns = `id, full_name, email, phone, postal_code, delivery_address, special_instructions, status,
  access_token_hash, idempotency_key, checkout_fingerprint, email_status, email_sent_at, created_at, subtotal, discount, delivery_fee, total`;

export async function findOrderByNumber(orderNumber: number): Promise<OrderRecord|null> {
  const connection = await pool.getConnection();
  try {
    const [rows] = await connection.execute<OrderRow[]>(`SELECT ${orderColumns} FROM orders WHERE id = ? LIMIT 1`, [orderNumber]);
    const row = rows[0];
    if (!row) return null;
    return mapOrder(row, (await getLines(connection, [orderNumber])).get(orderNumber) ?? []);
  } finally { connection.release(); }
}

export async function findOrderByIdempotencyKey(key: string): Promise<OrderRecord|null> {
  const [rows] = await pool.execute<OrderRow[]>(`SELECT ${orderColumns} FROM orders WHERE idempotency_key = ? LIMIT 1`, [key]);
  return rows[0] ? mapOrder(rows[0]) : null;
}

export async function findOrdersByNumbers(orderNumbers: number[]): Promise<OrderRecord[]> {
  if (!orderNumbers.length) return [];
  const connection = await pool.getConnection();
  try {
    const [rows] = await connection.execute<OrderRow[]>(
      `SELECT ${orderColumns} FROM orders WHERE id IN (${orderNumbers.map(() => '?').join(',')}) ORDER BY created_at DESC, id DESC`, orderNumbers,
    );
    const lines = await getLines(connection, rows.map((row) => Number(row.id)));
    return rows.map((row) => mapOrder(row, lines.get(Number(row.id)) ?? []));
  } finally { connection.release(); }
}

export async function setOrderEmailStatus(orderNumber: number, status: OrderRecord['emailStatus']) {
  await pool.execute('UPDATE orders SET email_status = ? WHERE id = ? AND email_status <> \'sent\'', [status, orderNumber]);
}

export async function getOrderEmailStatus(orderNumber: number): Promise<OrderRecord['emailStatus']|null> {
  const [rows] = await pool.execute<(RowDataPacket & { email_status: OrderRecord['emailStatus'] })[]>(
    'SELECT email_status FROM orders WHERE id = ? LIMIT 1', [orderNumber],
  );
  return rows[0]?.email_status ?? null;
}

export async function findReceiptOrder(orderNumber: number) {
  const order = await findOrderByNumber(orderNumber);
  return order;
}

export async function claimReceipt(orderNumber: number) {
  const [result] = await pool.execute('UPDATE orders SET email_status = \'queued\' WHERE id = ? AND email_status IN (\'disabled\', \'failed\')', [orderNumber]);
  return 'affectedRows' in result ? Number(result.affectedRows) === 1 : false;
}

export async function stampReceipt(orderNumber: number, status: 'sent'|'failed') {
  if (status === 'sent') {
    const [result] = await pool.execute('UPDATE orders SET email_status = \'sent\', email_sent_at = CURRENT_TIMESTAMP WHERE id = ? AND email_status = \'queued\'', [orderNumber]);
    return 'affectedRows' in result ? Number(result.affectedRows) === 1 : false;
  }
  const [result] = await pool.execute('UPDATE orders SET email_status = \'failed\' WHERE id = ? AND email_status = \'queued\'', [orderNumber]);
  return 'affectedRows' in result ? Number(result.affectedRows) === 1 : false;
}

export async function insertOrder(connection: PoolConnection, input: {
  fullName: string; email: string; phone: string; postalCode: string; deliveryAddress: string;
  specialInstructions: string; subtotal: string; discount: string; delivery: string; total: string;
  accessCodeHash: string; idempotencyKey: string; checkoutFingerprint: string;
}) {
  const [result] = await connection.execute(
    `INSERT INTO orders (full_name, email, phone, postal_code, delivery_address, special_instructions,
      subtotal, discount, delivery_fee, total, status, access_token_hash, idempotency_key, checkout_fingerprint)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ORDER_PLACED', ?, ?, ?)`,
    [input.fullName, input.email, input.phone, input.postalCode, input.deliveryAddress, input.specialInstructions,
      input.subtotal, input.discount, input.delivery, input.total, input.accessCodeHash, input.idempotencyKey, input.checkoutFingerprint],
  );
  return Number('insertId' in result ? result.insertId : 0);
}

export async function insertOrderLines(connection: PoolConnection, orderNumber: number, lines: OrderLine[]) {
  for (const line of lines) {
    await connection.execute(
      `INSERT INTO order_items (order_id, product_id, product_slug, product_name, image, size, colour, unit_price, quantity, line_total)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [orderNumber, line.productId, line.productSlug, line.productName, line.image, line.size, line.colour,
        (line.unitPriceCents / 100).toFixed(2), line.quantity, (line.lineTotalCents / 100).toFixed(2)],
    );
  }
}
