export function decimalToCents(value: string | number | null): number | null {
  if (value === null) return null;
  const raw = String(value);
  const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(raw);
  if (!match) throw new Error(`Invalid MySQL DECIMAL(10,2) value: ${raw}`);
  const cents = Number(match[1]) * 100 + Number((match[2] ?? '').padEnd(2, '0'));
  if (!Number.isSafeInteger(cents)) throw new Error('MySQL monetary value is outside the supported integer-cent range.');
  return cents;
}

export function centsToDecimal(cents: number): string {
  if (!Number.isSafeInteger(cents) || cents < 0) throw new Error('Money must be a nonnegative integer number of cents.');
  return `${Math.floor(cents / 100)}.${String(cents % 100).padStart(2, '0')}`;
}
