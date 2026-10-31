import express from 'express';
import session from 'express-session';
import MongoStore from 'connect-mongo';
import helmet from 'helmet';
import { rateLimit } from 'express-rate-limit';
import { env, sessionSecret } from './config/env.js';
import { errorHandler, notFound } from './middleware/errors.js';
import { healthRouter } from './routes/health.js';
import { sessionRouter } from './routes/session.js';
import { catalogueRouter } from './routes/catalogue.js';
import { cartRouter } from './routes/cart.js';
import { checkoutRouter } from './routes/checkout.js';
import { ordersRouter } from './routes/orders.js';

export function createApp() {
  const app = express();
  app.disable('x-powered-by');
  app.use(helmet({ contentSecurityPolicy: env.NODE_ENV === 'production' ? undefined : false }));
  app.use(express.json({ limit: '32kb', strict: true }));
  app.use(rateLimit({ windowMs: 60_000, limit: 120, standardHeaders: 'draft-8', legacyHeaders: false }));

  app.use('/api', (request, response, next) => {
    const origin = request.get('origin');
    if (origin && origin !== env.CLIENT_ORIGIN) {
      response.status(403).json({ data: null, error: { code: 'ORIGIN_DENIED', message: 'Request origin is not allowed.' } });
      return;
    }
    next();
  });

  // Readiness is public and must remain inspectable while the DB-backed
  // session store is disconnected.
  app.use('/api/v1', healthRouter);
  app.use('/api/v1', catalogueRouter);

  app.use(session({
    name: 'f2b.sid',
    secret: sessionSecret,
    resave: false,
    saveUninitialized: false,
    ...(env.MONGODB_URI ? { store: MongoStore.create({ mongoUrl: env.MONGODB_URI, collectionName: 'sessions' }) } : {}),
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: env.NODE_ENV === 'production',
      maxAge: 1000 * 60 * 60 * 24,
    },
  }));

  app.use('/api/v1/session', sessionRouter);
  app.use('/api/v1', cartRouter);
  app.use('/api/v1', checkoutRouter);
  app.use('/api/v1', ordersRouter);
  app.use('/api', notFound);
  app.use(errorHandler);
  return app;
}
