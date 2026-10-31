import 'express-session';

declare module 'express-session' {
  interface SessionData {
    csrfToken?: string;
    staffAuthenticated?: boolean;
    cart?: Record<string, { productId: number; size: string; colour: string; quantity: number }>;
    checkoutLast?: { idempotencyKey: string; orderNumber: number; accessCode?: string };
    authorizedOrderNumbers?: number[];
  }
}
