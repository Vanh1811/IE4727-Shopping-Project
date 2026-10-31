import { Router } from 'express';
import mongoose from 'mongoose';
import { checkDatabaseReadiness } from '../db/readiness.js';

export const healthRouter = Router();

healthRouter.get('/health', async (_request, response) => {
  const mysql = await checkDatabaseReadiness();
  const legacyCatalogueReady = mongoose.connection.readyState === 1;
  const ready = mysql.schemaReady && legacyCatalogueReady;
  response.status(ready ? 200 : 503).json({
    data: {
      status: ready ? 'ok' : 'not_ready',
      database: !mysql.connected ? 'disconnected' : ready ? 'connected' : 'not_ready',
    },
    error: null,
  });
});
