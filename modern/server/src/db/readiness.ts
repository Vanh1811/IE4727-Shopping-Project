import type { RowDataPacket } from 'mysql2';
import { pool } from './pool.js';

export type DatabaseReadiness = { connected: boolean; schemaReady: boolean };

export async function checkDatabaseReadiness(): Promise<DatabaseReadiness> {
  try {
    await pool.query('SELECT 1');
  } catch {
    return { connected: false, schemaReady: false };
  }
  try {
    const [tables] = await pool.query<(RowDataPacket & { table_name: string })[]>(
      `SELECT table_name FROM information_schema.tables
       WHERE table_schema = DATABASE() AND table_name IN
         ('schema_migrations','categories','products','orders','order_items','mockups','mockup_reviews','sessions')`,
    );
    if (tables.length !== 8) return { connected: true, schemaReady: false };
    const [versions] = await pool.execute<(RowDataPacket & { version: string })[]>(
      'SELECT version FROM schema_migrations WHERE version = ?', ['002_checkout_fingerprint'],
    );
    return { connected: true, schemaReady: versions.length === 1 };
  } catch {
    return { connected: true, schemaReady: false };
  }
}
