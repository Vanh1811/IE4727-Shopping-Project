import { z } from 'zod';

const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  PORT: z.coerce.number().int().min(1).max(65535).default(4000),
  MONGODB_URI: z.string().optional(),
  DB_HOST: z.string().default('127.0.0.1'),
  DB_PORT: z.coerce.number().int().min(1).max(65535).default(3306),
  DB_USER: z.string().min(1).default('root'),
  DB_PASSWORD: z.string().default(''),
  DB_NAME: z.string().default(process.env.NODE_ENV === 'test' ? 'f2b_shop_modern_test' : 'f2b_shop_modern'),
  STAFF_PASSWORD_HASH: z.string().optional(),
  LOCAL_MAIL_ENABLED: z.string().optional().default('0'),
  LOCAL_MAIL_TO: z.string().optional(),
  LOCAL_MAIL_ALLOWED_TO: z.string().optional(),
  LOCAL_SMTP_HOST: z.string().optional().default('127.0.0.1'),
  LOCAL_SMTP_PORT: z.coerce.number().int().min(1).max(65535).optional().default(1025),
  SESSION_SECRET: z.string().min(32),
  CLIENT_ORIGIN: z.string().url().default('http://127.0.0.1:5173'),
}).superRefine((value, context) => {
  if (!['127.0.0.1', 'localhost', '::1'].includes(value.DB_HOST)) {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ['DB_HOST'], message: 'Modern MySQL must use a loopback host.' });
  }
  const expectedDatabase = value.NODE_ENV === 'test' ? 'f2b_shop_modern_test' : 'f2b_shop_modern';
  if (value.DB_NAME !== expectedDatabase) {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ['DB_NAME'], message: `DB_NAME must be ${expectedDatabase} for NODE_ENV=${value.NODE_ENV}.` });
  }
  if (value.NODE_ENV !== 'test' && value.DB_NAME === 'f2b_shop') {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ['DB_NAME'], message: 'The PHP Base Version database is protected.' });
  }
});

const parsed = envSchema.safeParse(process.env);
if (!parsed.success) {
  throw new Error(`Invalid server environment configuration: ${parsed.error.issues.map((issue) => issue.path.join('.')).join(', ')}`);
}

export const env = parsed.data;
export const sessionSecret = env.SESSION_SECRET;
