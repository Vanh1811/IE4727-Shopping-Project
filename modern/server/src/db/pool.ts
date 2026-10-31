import mysql from 'mysql2/promise';
import { env } from '../config/env.js';

export const pool = mysql.createPool({
  host: env.DB_HOST,
  port: env.DB_PORT,
  user: env.DB_USER,
  password: env.DB_PASSWORD,
  database: env.DB_NAME,
  waitForConnections: true,
  connectTimeout: 3000,
  connectionLimit: 10,
  queueLimit: 0,
  charset: 'utf8mb4',
  supportBigNumbers: true,
  bigNumberStrings: true,
  decimalNumbers: false,
  timezone: 'Z',
});

export async function closePool(): Promise<void> {
  await pool.end();
}
