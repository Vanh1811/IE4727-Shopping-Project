import mysql, { type RowDataPacket } from 'mysql2/promise';
import { readE2EConfig, hasReadOnlyDBConfig } from '../config/env';

export interface SavedOrder extends RowDataPacket {
  id: number;
  full_name: string;
  status: string;
  subtotal: string;
  discount: string;
  delivery_fee: string;
  total: string;
  email_sent_at: string | null;
}

export interface SavedOrderItem extends RowDataPacket {
  product_name: string;
  size: string;
  colour: string;
  unit_price: string;
  quantity: number;
  line_total: string;
}

export async function withReadOnlyDB<T>(operation: (db: mysql.Connection) => Promise<T>): Promise<T | null> {
  const config = readE2EConfig();
  if (!hasReadOnlyDBConfig(config)) return null;
  if (config.dbName !== 'f2b_shop_e2e') throw new Error('Read-only assertions are restricted to E2E_DB_NAME=f2b_shop_e2e.');

  const db = await mysql.createConnection({
    host: config.dbHost,
    port: config.dbPort,
    user: config.dbUser,
    password: config.dbPassword,
    database: config.dbName,
    connectTimeout: 5000,
  });
  try {
    const [identity] = await db.execute<RowDataPacket[]>('SELECT DATABASE() AS name');
    if (identity[0]?.name !== 'f2b_shop_e2e') throw new Error('Read-only DB connection did not select f2b_shop_e2e.');
    return await operation(db);
  } finally {
    await db.end();
  }
}

export async function readSavedOrder(orderId: number): Promise<{ order: SavedOrder | undefined; items: SavedOrderItem[] } | null> {
  return withReadOnlyDB(async (db) => {
    const [orders] = await db.execute<SavedOrder[]>(
      'SELECT id, full_name, status, subtotal, discount, delivery_fee, total, email_sent_at FROM orders WHERE id = ? LIMIT 1',
      [orderId],
    );
    const [items] = await db.execute<SavedOrderItem[]>(
      'SELECT product_name, size, colour, unit_price, quantity, line_total FROM order_items WHERE order_id = ? ORDER BY id ASC',
      [orderId],
    );
    return { order: orders[0], items };
  });
}
