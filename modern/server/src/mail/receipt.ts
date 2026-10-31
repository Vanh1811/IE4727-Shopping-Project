import nodemailer from 'nodemailer';
import { env } from '../config/env.js';
import { findReceiptOrder, getOrderEmailStatus, setOrderEmailStatus, claimReceipt, stampReceipt } from '../orders/repository.js';

export type LocalMailConfig = { enabled: string; recipient?: string; allowedRecipient?: string; smtpHost: string; smtpPort: number };
export type ReceiptStatus = 'disabled'|'queued'|'failed'|'sent'|'already_sent';

export function isLocalEmail(address: string | undefined) {
  if (!address || address.length > 254 || (address.match(/@/g) ?? []).length !== 1) return false;
  const [local, rawDomain] = address.split('@');
  if (!local || local.length > 64 || local.startsWith('.') || local.endsWith('.') || local.includes('..') || !/^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+$/.test(local)) return false;
  const domain = rawDomain.toLowerCase();
  if (domain !== 'localhost' && (!domain.includes('.') || domain.split('.').some((label) => !/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/.test(label)))) return false;
  return domain === 'localhost' || domain.endsWith('.localhost') || domain.endsWith('.test') || domain.endsWith('.local');
}

export function isLoopbackSmtpHost(host: string) {
  return ['127.0.0.1', '::1'].includes(host.trim().toLowerCase());
}

export function localMailEnabled(config: LocalMailConfig) {
  return config.enabled === '1' && isLocalEmail(config.recipient) && isLocalEmail(config.allowedRecipient) &&
    config.recipient!.toLowerCase() === config.allowedRecipient!.toLowerCase() && isLoopbackSmtpHost(config.smtpHost) &&
    Number.isInteger(config.smtpPort) && config.smtpPort > 0 && config.smtpPort <= 65535;
}

function text(value: string, fallback = '') {
  // Remove disallowed control bytes before writing a plain-text message.
  // eslint-disable-next-line no-control-regex
  return value.replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/g, '').replace(/[\r\n]+/g, ' ').trim() || fallback;
}

const formatMoney = (cents: number) => `$${(cents / 100).toFixed(2)}`;

type ReceiptOrder = {
  orderNumber: number; fullName: string; status: string; createdAt: Date;
  items: { productName: string; size: string; colour: string; unitPriceCents: number; quantity: number; lineTotalCents: number }[];
  subtotalCents: number; discountCents: number; deliveryCents: number; totalCents: number;
};

export function buildReceiptText(order: ReceiptOrder) {
  const date = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Singapore' }).format(order.createdAt);
  const lines = [
    'F2B SHOP', 'ORDER ACKNOWLEDGEMENT', '',
    `Hello ${text(order.fullName, 'F2B SHOP customer')},`, '',
    'Thank you for shopping with F2B SHOP.',
    'We have received your order. Your design mock-up will be prepared for review.', '',
    `Order number: #${order.orderNumber}`,
    `Order date: ${date}`,
    `Status: ${order.status.replaceAll('_', ' ')}`, '',
    'ORDERED ITEMS',
  ];
  order.items.forEach((item, index) => {
    lines.push(`${index + 1}. ${text(item.productName, 'Product')}`,
      `   Size: ${text(item.size, 'Not specified')} | Colour: ${text(item.colour, 'Not specified')}`,
      `   Unit price: ${formatMoney(item.unitPriceCents)} | Quantity: ${item.quantity} | Line total: ${formatMoney(item.lineTotalCents)}`, '');
  });
  lines.push(`Subtotal: ${formatMoney(order.subtotalCents)}`,
    `Discount: -${formatMoney(order.discountCents)}`,
    `Delivery fee: ${formatMoney(order.deliveryCents)}`,
    `TOTAL: ${formatMoney(order.totalCents)}`, '',
    'Next step: our team will publish a mock-up for your approval or change requests.',
    'Thank you,', 'F2B SHOP');
  return lines.join('\n');
}

const config = (): LocalMailConfig => ({
  enabled: env.LOCAL_MAIL_ENABLED,
  recipient: env.LOCAL_MAIL_TO,
  allowedRecipient: env.LOCAL_MAIL_ALLOWED_TO,
  smtpHost: env.LOCAL_SMTP_HOST,
  smtpPort: env.LOCAL_SMTP_PORT,
});

export async function sendLocalReceipt(orderNumber: number): Promise<ReceiptStatus> {
  const order = await findReceiptOrder(orderNumber);
  if (!order) return 'failed';
  if (order.emailStatus === 'sent') return 'already_sent';
  const mailConfig = config();
  if (!localMailEnabled(mailConfig)) {
    if (order.emailStatus !== 'queued') await setOrderEmailStatus(orderNumber, 'disabled');
    return 'disabled';
  }

  const claimed = await claimReceipt(orderNumber);
  if (!claimed) {
    const current = await getOrderEmailStatus(orderNumber);
    return current === 'sent' ? 'already_sent' : 'queued';
  }

  const transport = nodemailer.createTransport({
    host: mailConfig.smtpHost,
    port: mailConfig.smtpPort,
    secure: false,
    ignoreTLS: true,
    connectionTimeout: 3_000,
    greetingTimeout: 3_000,
    socketTimeout: 5_000,
  });
  try {
    const result = await transport.sendMail({
      from: 'F2B SHOP <no-reply@f2b.test>',
      to: mailConfig.recipient!,
      subject: `F2B SHOP order acknowledgement #${order.orderNumber}`,
      text: buildReceiptText(order),
      disableFileAccess: true,
      disableUrlAccess: true,
    });
    const accepted = result.accepted.some((address) => (typeof address === 'string' ? address : address.address).toLowerCase() === mailConfig.recipient!.toLowerCase());
    if (!accepted) throw new Error('Local SMTP did not accept the allowlisted recipient.');
    const stamped = await stampReceipt(orderNumber, 'sent');
    return stamped ? 'sent' : 'queued';
  } catch {
    await stampReceipt(orderNumber, 'failed');
    return 'failed';
  } finally {
    transport.close();
  }
}
