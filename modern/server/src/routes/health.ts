import { Router } from 'express';
import mongoose from 'mongoose';
import { env } from '../config/env.js';

export const healthRouter = Router();

healthRouter.get('/health', (_request, response) => {
  const configured = Boolean(env.MONGODB_URI);
  const ready = configured && mongoose.connection.readyState === 1;
  response.status(ready ? 200 : 503).json({
    data: {
      status: ready ? 'ok' : 'not_ready',
      database: !configured ? 'not_configured' : ready ? 'connected' : 'disconnected',
    },
    error: null,
  });
});
