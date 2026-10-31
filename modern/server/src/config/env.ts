import { z } from 'zod';

const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  PORT: z.coerce.number().int().min(1).max(65535).default(4000),
  MONGODB_URI: z.string().optional(),
  STAFF_PASSWORD_HASH: z.string().optional(),
  LOCAL_MAIL_ENABLED: z.string().optional().default('0'),
  LOCAL_MAIL_TO: z.string().optional(),
  LOCAL_MAIL_ALLOWED_TO: z.string().optional(),
  LOCAL_SMTP_HOST: z.string().optional().default('127.0.0.1'),
  LOCAL_SMTP_PORT: z.coerce.number().int().min(1).max(65535).optional().default(1025),
  SESSION_SECRET: z.string().min(32),
  CLIENT_ORIGIN: z.string().url().default('http://127.0.0.1:5173'),
}).superRefine((value, context) => {
  if (value.NODE_ENV === 'production' && !value.MONGODB_URI) {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ['MONGODB_URI'], message: 'MONGODB_URI is required in production.' });
  }
});

const parsed = envSchema.safeParse(process.env);
if (!parsed.success) {
  throw new Error(`Invalid server environment configuration: ${parsed.error.issues.map((issue) => issue.path.join('.')).join(', ')}`);
}

export const env = parsed.data;
export const sessionSecret = env.SESSION_SECRET;
