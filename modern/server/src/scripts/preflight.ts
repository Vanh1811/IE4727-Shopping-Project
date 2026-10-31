import 'dotenv/config';
import mysql from 'mysql2/promise';
import { env } from '../config/env.js';
import { assertSafeDatabaseTarget, quoteAllowedDatabase } from '../db/guard.js';
import { pool } from '../db/pool.js';
import { checkDatabaseReadiness } from '../db/readiness.js';
import { canInitializeDatabase } from '../db/privileges.js';

async function main(): Promise<void> {
  assertSafeDatabaseTarget();
  const connection = await mysql.createConnection({ host: env.DB_HOST, port: env.DB_PORT, user: env.DB_USER, password: env.DB_PASSWORD, charset: 'utf8mb4' });
  try {
    const [[server]] = await connection.query<mysql.RowDataPacket[]>('SELECT VERSION() AS version');
    const [[engine]] = await connection.query<mysql.RowDataPacket[]>("SELECT SUPPORT AS support FROM information_schema.ENGINES WHERE ENGINE = 'InnoDB'");
    const [schemas] = await connection.execute<mysql.RowDataPacket[]>(
      'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [env.DB_NAME],
    );
    const [grantRows] = await connection.query<mysql.RowDataPacket[]>('SHOW GRANTS FOR CURRENT_USER()');
    const grants = grantRows.map((row) => String(Object.values(row)[0] ?? ''));
    if (!canInitializeDatabase(grants, env.DB_NAME, schemas.length > 0)) {
      throw new Error(`MySQL user lacks CREATE/ALTER permission for the allowlisted ${env.DB_NAME} schema.`);
    }
    process.stdout.write(`MySQL connection: PASS (${String(server?.version ?? 'version unavailable')})\n`);
    if (!['YES', 'DEFAULT'].includes(String(engine?.support ?? '').toUpperCase())) throw new Error('InnoDB is not available on this MySQL server.');
    process.stdout.write('InnoDB support: PASS\n');
    process.stdout.write(`Allowlisted target: ${env.DB_NAME}\n`);
    process.stdout.write(`Database exists: ${schemas.length > 0 ? 'yes' : 'no'}\n`);
    process.stdout.write('Modern schema initialization privileges: PASS\n');
  } finally {
    await connection.end();
  }
  const readiness = await checkDatabaseReadiness();
  if (readiness.schemaReady) {
    process.stdout.write('Modern schema readiness: PASS\n');
  } else {
    process.stdout.write(`Modern schema readiness: not initialized; run db:init for ${quoteAllowedDatabase()}\n`);
  }
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown database preflight error.';
  process.stderr.write(`${message}\n`);
  process.exitCode = 1;
}).finally(async () => { await pool.end().catch(() => undefined); });
