import 'dotenv/config';
import mongoose from 'mongoose';
import { createApp } from './app.js';
import { env } from './config/env.js';

async function start() {
  const app = createApp();
  app.listen(env.PORT, '127.0.0.1', () => {
    process.stdout.write(`F2B modern API listening on http://127.0.0.1:${env.PORT}\n`);
  });
  if (env.MONGODB_URI) {
    mongoose.connect(env.MONGODB_URI, { serverSelectionTimeoutMS: 5000 }).catch((error: unknown) => {
      const message = error instanceof Error ? error.message : 'Unknown MongoDB connection error';
      process.stderr.write(`F2B modern API database connection unavailable: ${message}\n`);
    });
  }
}

start().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown startup error';
  process.stderr.write(`F2B modern API startup failed: ${message}\n`);
  process.exitCode = 1;
});
