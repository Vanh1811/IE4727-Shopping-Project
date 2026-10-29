import { randomUUID } from 'node:crypto';

const runId = randomUUID().replace(/-/g, '').slice(0, 12);

export const syntheticCustomer = {
  full_name: `Playwright Test ${runId}`,
  email: `e2e-${runId}@f2b.test`,
  phone: '+65 6000 1234',
  postal_code: '238801',
  delivery_address: `1 Test Lane ${runId}, Singapore`,
  special_instructions: `Automated E2E ${runId}`,
};

export const testRunId = runId;
