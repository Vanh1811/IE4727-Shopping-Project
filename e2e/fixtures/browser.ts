import { test as base, expect } from '@playwright/test';
import { readE2EConfig } from '../config/env';

export const test = base.extend({
  page: async ({ page }, use, testInfo) => {
    const diagnostics: string[] = [];
    const appOrigin = new URL(readE2EConfig().baseURL).origin;

    page.on('pageerror', () => diagnostics.push('Browser JavaScript page error.'));
    page.on('requestfailed', (request) => {
      const failure = request.failure()?.errorText ?? '';
      if (failure.includes('ERR_ABORTED')) return;
      try {
        const url = new URL(request.url());
        if (url.origin !== appOrigin) return;
        if (!(/\.php$|\.(?:js|css)$/.test(url.pathname))) return;
        diagnostics.push(`${request.method()} ${url.pathname} failed (${failure || 'network error'}).`);
      } catch {
        diagnostics.push('A first-party request failed.');
      }
    });

    await use(page);
    if (diagnostics.length) {
      await testInfo.attach('first-party-browser-diagnostics.txt', {
        body: Buffer.from(diagnostics.join('\n')),
        contentType: 'text/plain',
      });
      throw new Error(`First-party browser diagnostics: ${diagnostics.join(' ')}`);
    }
  },
});

export { expect };
