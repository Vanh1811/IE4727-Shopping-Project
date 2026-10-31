import 'dotenv/config';
import mongoose from 'mongoose';
import { env } from '../config/env.js';

async function main() {
  if (!env.MONGODB_URI) throw new Error('MONGODB_URI is required for the database preflight.');
  if (env.NODE_ENV === 'test' && !/(^|[_-])test([_-]|$)/i.test(new URL(env.MONGODB_URI).pathname)) {
    throw new Error('Test database URI must name a clearly isolated test database.');
  }
  await mongoose.connect(env.MONGODB_URI, { serverSelectionTimeoutMS: 5000 });
  const hello = await mongoose.connection.db?.admin().command({ hello: 1 });
  const replicaSet = typeof hello?.setName === 'string';
  if (!replicaSet) throw new Error('MongoDB is reachable but is not a replica set; transactional stages require a single-node replica set.');
  process.stdout.write(`MongoDB connected; replica set ${hello.setName} is ready.\n`);
  await mongoose.disconnect();
}

main().catch(async (error: unknown) => {
  const message = error instanceof Error ? error.message : 'Unknown preflight error';
  process.stderr.write(`${message}\n`);
  await mongoose.disconnect().catch(() => undefined);
  process.exitCode = 1;
});
