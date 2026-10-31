import 'dotenv/config';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const serverFile = resolve(root, 'server/dist/server.js');
const viteFile = resolve(root, 'node_modules/vite/bin/vite.js');
const env = {
  ...process.env,
  NODE_ENV: process.env.NODE_ENV ?? 'development',
  PORT: process.env.PORT ?? '4000',
  CLIENT_ORIGIN: process.env.CLIENT_ORIGIN ?? 'http://127.0.0.1:5173',
};

if (!env.SESSION_SECRET || env.SESSION_SECRET.length < 32) {
  process.stderr.write('Set SESSION_SECRET in modern/server/.env (at least 32 characters) before starting the app.\n');
  process.exit(1);
}

const children = [
  ['api', spawn(process.execPath, [serverFile], { cwd: root, env, stdio: 'inherit' })],
  ['web', spawn(process.execPath, [viteFile, '--host', '127.0.0.1', '--port', '5173', '--strictPort'], { cwd: resolve(root, 'client'), env, stdio: 'inherit' })],
];

let stopping = false;
function stopAll(code = 0) {
  if (stopping) return;
  stopping = true;
  for (const [, child] of children) if (!child.killed) child.kill();
  process.exitCode = code;
}

for (const [name, child] of children) {
  child.on('error', (error) => {
    process.stderr.write(`${name} process failed to start: ${error.message}\n`);
    stopAll(1);
  });
  child.on('exit', (code) => {
    if (!stopping) {
      process.stderr.write(`${name} process exited (${code ?? 'signal'}).\n`);
      stopAll(code ?? 1);
    }
  });
}

process.on('SIGINT', () => stopAll(0));
process.on('SIGTERM', () => stopAll(0));
