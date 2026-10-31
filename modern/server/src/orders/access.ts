import { createHash, timingSafeEqual } from 'node:crypto';

export function hashAccessCode(accessCode: string) {
  return createHash('sha256').update(accessCode.trim(), 'utf8').digest('hex');
}

export function verifyAccessCode(storedHash: string | null | undefined, supplied: unknown) {
  const submitted = typeof supplied === 'string' ? supplied.trim() : '';
  if (!submitted || submitted.length > 128) return false;
  const expectedHex = /^[a-f0-9]{64}$/i.test(storedHash ?? '') ? storedHash! : createHash('sha256').update('f2b-invalid-order-access-code').digest('hex');
  const actual = Buffer.from(hashAccessCode(submitted), 'hex');
  const expected = Buffer.from(expectedHex, 'hex');
  const matches = timingSafeEqual(expected, actual);
  return Boolean(storedHash) && matches;
}
