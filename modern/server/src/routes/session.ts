import { randomBytes } from 'node:crypto';
import { Router } from 'express';

export const sessionRouter = Router();

sessionRouter.get('/csrf', (request, response) => {
  if (!request.session.csrfToken) request.session.csrfToken = randomBytes(32).toString('hex');
  response.setHeader('Cache-Control', 'no-store');
  response.json({ data: { csrfToken: request.session.csrfToken }, error: null });
});
