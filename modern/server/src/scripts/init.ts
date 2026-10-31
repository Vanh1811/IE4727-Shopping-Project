import 'dotenv/config';
import mysql from 'mysql2/promise';
import { env } from '../config/env.js';
import { assertSafeDatabaseTarget, quoteAllowedDatabase } from '../db/guard.js';
import { pool } from '../db/pool.js';
import { runMigrations } from '../db/migrations.js';

async function main(): Promise<void> {
  assertSafeDatabaseTarget();
  const admin = await mysql.createConnection({
    host: env.DB_HOST, port: env.DB_PORT, user: env.DB_USER,
    password: env.DB_PASSWORD, charset: 'utf8mb4', timezone: 'Z',
  });
  try {
    await admin.query(`CREATE DATABASE IF NOT EXISTS ${quoteAllowedDatabase()} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
  } finally {
    await admin.end();
  }
  const applied = await runMigrations();
  process.stdout.write(`Database target: ${env.DB_NAME}\nMigrations applied: ${applied.length ? applied.join(', ') : 'none (already current)'}\n`);
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown database initialization error.';
  process.stderr.write(`${message}\n`);
  process.exitCode = 1;
}).finally(async () => { await pool.end().catch(() => undefined); });
