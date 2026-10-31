import bcrypt from 'bcryptjs';
import type { Request } from 'express';
import { env } from '../config/env.js';

export function staffIsConfigured() {
  return /^\$2[aby]\$\d\d\$[./A-Za-z0-9]{53}$/.test(env.STAFF_PASSWORD_HASH ?? '');
}

export function verifyStaffPassword(password: unknown) {
  if (typeof password !== 'string' || password.length > 256 || !staffIsConfigured()) return false;
  const phpHash = env.STAFF_PASSWORD_HASH!;
  // PHP's $2y$ marker uses the bcrypt algorithm accepted by bcryptjs as $2a$.
  const compatibleHash = phpHash.replace(/^\$2y\$/, '$2a$');
  try { return bcrypt.compareSync(password, compatibleHash); } catch { return false; }
}

export function requireStaff(request: Request) {
  return request.session.staffAuthenticated === true;
}

export async function rotateStaffSession(request: Request, authenticated: boolean) {
  const prior = request.session;
  const saved = { cart: prior.cart, checkoutLast: prior.checkoutLast, authorizedOrderNumbers: prior.authorizedOrderNumbers };
  await new Promise<void>((resolve, reject) => prior.regenerate((error) => error ? reject(error) : resolve()));
  request.session.staffAuthenticated = authenticated;
  if (saved.cart) request.session.cart = saved.cart;
  if (saved.checkoutLast) request.session.checkoutLast = saved.checkoutLast;
  if (saved.authorizedOrderNumbers) request.session.authorizedOrderNumbers = saved.authorizedOrderNumbers;
}
