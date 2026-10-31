import { readFile, readdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import type { RowDataPacket } from 'mysql2';
import type { PoolConnection } from 'mysql2/promise';
import { pool } from './pool.js';

const migrationDirectory = resolve(dirname(fileURLToPath(import.meta.url)), '../../sql/migrations');

export async function runMigrations(): Promise<string[]> {
  await pool.execute(`CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(100) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`);
  const files = (await readdir(migrationDirectory)).filter((file) => /^\d+_[a-z0-9_]+\.sql$/i.test(file)).sort();
  const appliedNow: string[] = [];
  for (const file of files) {
    const version = file.replace(/\.sql$/i, '');
    const [existing] = await pool.execute<(RowDataPacket & { version: string })[]>(
      'SELECT version FROM schema_migrations WHERE version = ?', [version],
    );
    if (existing.length) continue;

    const sql = (await readFile(resolve(migrationDirectory, file), 'utf8'))
      .split(/\r?\n/)
      .filter((line) => !/^\s*--/.test(line))
      .join('\n');
    const statements = sql.split(';').map((statement) => statement.trim()).filter(Boolean);
    const connection: PoolConnection = await pool.getConnection();
    try {
      for (const statement of statements) await connection.query(statement);
      await connection.execute('INSERT INTO schema_migrations (version) VALUES (?)', [version]);
      appliedNow.push(version);
    } finally {
      connection.release();
    }
  }
  return appliedNow;
}
