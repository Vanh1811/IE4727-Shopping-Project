import { Router } from 'express';
import { checkDatabaseReadiness } from '../db/readiness.js';

export const healthRouter = Router();

healthRouter.get('/health', async (_request, response) => {
  const mysql = await checkDatabaseReadiness();
  const ready = mysql.schemaReady;
  response.status(ready ? 200 : 503).json({
    data: {
      status: ready ? 'ok' : 'not_ready',
      database: !mysql.connected ? 'disconnected' : ready ? 'connected' : 'not_ready',
    },
    error: null,
  });
});
