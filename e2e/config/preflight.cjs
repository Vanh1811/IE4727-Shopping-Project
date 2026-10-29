const fs = require('node:fs');
const path = require('node:path');
const mysql = require('mysql2/promise');

const envPath = path.resolve(process.cwd(), '.env.e2e');
if (fs.existsSync(envPath)) {
  const content = fs.readFileSync(envPath, 'utf8');
  for (const line of content.split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
    if (!match || match[1] in process.env) continue;
    let value = match[2].trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) value = value.slice(1, -1);
    process.env[match[1]] = value;
  }
}

function localURL(value, name, allowPrivate = false) {
  let url;
  try { url = new URL(value); } catch { throw new Error(`${name} must be a valid URL.`); }
  if (url.protocol !== 'http:' || url.username || url.password) throw new Error(`${name} must use HTTP without credentials.`);
  const host = url.hostname.toLowerCase().replace(/^\[|\]$/g, '');
  const loopback = host === 'localhost' || host.endsWith('.localhost') || host === '127.0.0.1' || host === '::1';
  const parts = host.split('.').map(Number);
  const privateIPv4 = parts.length === 4 && parts.every((part) => Number.isInteger(part) && part >= 0 && part <= 255)
    && (parts[0] === 10 || (parts[0] === 172 && parts[1] >= 16 && parts[1] <= 31) || (parts[0] === 192 && parts[1] === 168));
  if (!loopback && !(allowPrivate && privateIPv4)) throw new Error(`${name} must use localhost; private IPv4 requires E2E_ALLOW_PRIVATE_HOST=1.`);
  return url;
}

async function main() {
  const allowPrivate = process.env.E2E_ALLOW_PRIVATE_HOST === '1';
  const baseURL = localURL(process.env.E2E_BASE_URL || 'http://127.0.0.1/f2b-shop/', 'E2E_BASE_URL', allowPrivate);
  const mailpit = localURL(process.env.E2E_MAILPIT_URL || 'http://127.0.0.1:8025', 'E2E_MAILPIT_URL');
  const dbName = process.env.E2E_DB_NAME || '';
  const gateReason = process.env.E2E_ALLOW_MUTATIONS !== '1'
    ? 'Set E2E_ALLOW_MUTATIONS=1 to enable disposable DB writes.'
    : dbName !== 'f2b_shop_e2e'
      ? 'E2E_DB_NAME must be exactly f2b_shop_e2e.'
      : process.env.E2E_PHP_DB_VERIFIED !== '1'
        ? 'Verify Apache DB target and set E2E_PHP_DB_VERIFIED=1.'
        : 'OPEN (local DB target and manual Apache attestation configured).';

  console.log(`Node: ${process.version}`);
  console.log(`Base URL: ${baseURL.toString()}`);
  console.log(`Mutation gate: ${gateReason}`);
  let appOK = false;
  try {
    const response = await fetch(new URL('index.php', baseURL), { signal: AbortSignal.timeout(6000) });
    const html = await response.text();
    appOK = response.ok && !/Fatal error|Uncaught (?:Error|Exception)/i.test(html);
    console.log(`PHP app: HTTP ${response.status}${appOK ? ' (home response received)' : ' (unavailable or PHP error)'}`);
  } catch {
    console.log('PHP app: unreachable from this machine; start Apache or correct E2E_BASE_URL.');
  }

  try {
    const response = await fetch(new URL('shop.php', baseURL), { signal: AbortSignal.timeout(6000) });
    const html = await response.text();
    const productCount = [...html.matchAll(/href=["']product\.php\?id=\d+["']/g)].length;
    console.log(`Seeded catalogue: HTTP ${response.status}; ${productCount} product links detected.`);
  } catch {
    console.log('Seeded catalogue: unavailable until the PHP app and DB are reachable.');
  }

  if (dbName && process.env.E2E_DB_USER) {
    let connection;
    try {
      connection = await mysql.createConnection({
        host: process.env.E2E_DB_HOST || '127.0.0.1',
        port: Number(process.env.E2E_DB_PORT || '3306'),
        user: process.env.E2E_DB_USER,
        password: process.env.E2E_DB_PASSWORD || '',
        database: dbName,
        connectTimeout: 5000,
      });
      const [rows] = await connection.query('SELECT DATABASE() AS currentDatabase');
      const actual = rows[0] && rows[0].currentDatabase;
      console.log(`E2E DB: ${actual === dbName ? 'read-only connection verified for configured database' : 'database identity mismatch'}.`);
    } catch {
      console.log('E2E DB: connection failed; check local MySQL and read-only credentials. Credentials were not printed.');
    } finally {
      if (connection) await connection.end();
    }
  } else {
    console.log('E2E DB: optional read-only credentials are not configured.');
  }

  if (process.env.E2E_ENABLE_MAIL === '1') {
    try {
      const response = await fetch(new URL('/api/v1/messages', mailpit), { signal: AbortSignal.timeout(4000) });
      console.log(`Mailpit API: HTTP ${response.status}${response.ok ? ' reachable' : ' unavailable'}; inbox not modified.`);
    } catch {
      console.log('Mailpit API: unreachable; mail tests need a loopback Mailpit server.');
    }
  } else {
    console.log('Mailpit: disabled.');
  }
  if (!appOK) process.exitCode = 2;
}

main().catch((error) => {
  console.error(error instanceof Error ? error.message : 'Preflight failed safely.');
  process.exitCode = 2;
});
