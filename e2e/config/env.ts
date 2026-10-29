import { config as loadDotenv } from 'dotenv';
import { isIP } from 'node:net';
import path from 'node:path';

export interface E2EConfig {
  baseURL: string;
  allowPrivateHost: boolean;
  allowMutations: boolean;
  phpDbVerified: boolean;
  dbName: string;
  dbHost: string;
  dbPort: number;
  dbUser: string;
  dbPassword: string;
  staffEnabled: boolean;
  staffPassword: string;
  mailEnabled: boolean;
  mailpitURL: string;
  expectedLocalRecipient: string;
}

const truthy = (value: string | undefined): boolean => value === '1';

export function loadE2EEnv(): void {
  loadDotenv({ path: path.resolve(process.cwd(), '.env.e2e'), override: false, quiet: true });
}

function isPrivateIPv4(host: string): boolean {
  if (isIP(host) !== 4) return false;
  const octets = host.split('.').map(Number);
  return octets[0] === 10
    || (octets[0] === 172 && octets[1] >= 16 && octets[1] <= 31)
    || (octets[0] === 192 && octets[1] === 168);
}

export function validateLocalHttpURL(value: string, name: string, allowPrivateHost = false): string {
  let parsed: URL;
  try {
    parsed = new URL(value);
  } catch {
    throw new Error(`${name} must be a valid HTTP URL.`);
  }
  if (parsed.protocol !== 'http:' || parsed.username || parsed.password) {
    throw new Error(`${name} must use HTTP and must not contain credentials.`);
  }

  const host = parsed.hostname.toLowerCase().replace(/^\[|\]$/g, '');
  const loopback = host === 'localhost' || host.endsWith('.localhost') || host === '127.0.0.1' || host === '::1';
  if (!loopback && !(allowPrivateHost && isPrivateIPv4(host))) {
    throw new Error(`${name} must point to localhost. Private IPv4 hosts require E2E_ALLOW_PRIVATE_HOST=1.`);
  }
  return parsed.toString();
}

export function readE2EConfig(): E2EConfig {
  const allowPrivateHost = truthy(process.env.E2E_ALLOW_PRIVATE_HOST);
  const baseURL = validateLocalHttpURL(
    process.env.E2E_BASE_URL ?? 'http://127.0.0.1/f2b-shop/',
    'E2E_BASE_URL',
    allowPrivateHost,
  );
  const mailpitURL = validateLocalHttpURL(
    process.env.E2E_MAILPIT_URL ?? 'http://127.0.0.1:8025',
    'E2E_MAILPIT_URL',
  );
  const dbPort = Number(process.env.E2E_DB_PORT ?? '3306');
  if (!Number.isInteger(dbPort) || dbPort < 1 || dbPort > 65535) {
    throw new Error('E2E_DB_PORT must be a valid TCP port.');
  }

  return {
    baseURL,
    allowPrivateHost,
    allowMutations: truthy(process.env.E2E_ALLOW_MUTATIONS),
    phpDbVerified: truthy(process.env.E2E_PHP_DB_VERIFIED),
    dbName: process.env.E2E_DB_NAME ?? '',
    dbHost: process.env.E2E_DB_HOST ?? '127.0.0.1',
    dbPort,
    dbUser: process.env.E2E_DB_USER ?? '',
    dbPassword: process.env.E2E_DB_PASSWORD ?? '',
    staffEnabled: truthy(process.env.E2E_ENABLE_STAFF),
    staffPassword: process.env.E2E_STAFF_PASSWORD ?? '',
    mailEnabled: truthy(process.env.E2E_ENABLE_MAIL),
    mailpitURL,
    expectedLocalRecipient: process.env.E2E_EXPECTED_LOCAL_RECIPIENT ?? '',
  };
}

export function mutationGateReason(config = readE2EConfig()): string | null {
  if (!config.allowMutations) return 'Set E2E_ALLOW_MUTATIONS=1 to enable disposable E2E database writes.';
  if (config.dbName !== 'f2b_shop_e2e') return 'E2E_DB_NAME must equal f2b_shop_e2e; the normal f2b_shop database is never allowed.';
  if (!config.phpDbVerified) return 'Verify Apache uses f2b_shop_e2e, then explicitly set E2E_PHP_DB_VERIFIED=1.';
  return null;
}

export function requireMutationsEnabled(config = readE2EConfig()): void {
  const reason = mutationGateReason(config);
  if (reason) throw new Error(`Mutation safety gate is closed: ${reason}`);
}

export function staffGateReason(config = readE2EConfig()): string | null {
  const mutationReason = mutationGateReason(config);
  if (mutationReason) return mutationReason;
  if (!config.staffEnabled) return 'Set E2E_ENABLE_STAFF=1 to opt in to staff workflow tests.';
  if (!config.staffPassword) return 'Set E2E_STAFF_PASSWORD in the ignored .env.e2e file.';
  return null;
}

export function requireStaffEnabled(config = readE2EConfig()): void {
  const reason = staffGateReason(config);
  if (reason) throw new Error(`Staff safety gate is closed: ${reason}`);
}

export function hasReadOnlyDBConfig(config = readE2EConfig()): boolean {
  return Boolean(config.dbHost && config.dbName && config.dbUser);
}
