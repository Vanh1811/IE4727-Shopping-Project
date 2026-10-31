import 'dotenv/config';
import mongoose from 'mongoose';
import { MysqlSessionStore } from './db/mysqlSessionStore.js';
import { pool } from './db/pool.js';
import { createApp } from './app.js';
import { env } from './config/env.js';

async function start() {
  const store = new MysqlSessionStore(pool);
  const app = createApp({ sessionStore: store });
  app.listen(env.PORT, '127.0.0.1', () => {
    process.stdout.write(`F2B modern API listening on http://127.0.0.1:${env.PORT}\n`);
  });
  if (env.MONGODB_URI) {
    mongoose.connect(env.MONGODB_URI, { serverSelectionTimeoutMS: 5000 }).catch(() => {
      process.stderr.write('F2B modern API legacy MongoDB dependency is unavailable during migration.\n');
    });
  }
  const pruneTimer = setInterval(() => { void store.pruneExpired().catch(() => undefined); }, 60 * 60 * 1000);
  pruneTimer.unref();
}

start().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown startup error';
  process.stderr.write(`F2B modern API startup failed: ${message}\n`);
  process.exitCode = 1;
});
