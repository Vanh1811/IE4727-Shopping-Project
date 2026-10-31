import type { Request, Response, NextFunction } from 'express';

export function requireCsrf(request: Request, response: Response, next: NextFunction) {
  const token = request.get('x-csrf-token');
  if (!request.session.csrfToken || !token || token.length > 128 || token !== request.session.csrfToken) {
    response.status(403).json({ data: null, error: { code: 'CSRF_INVALID', message: 'Refresh the page and try again.' } });
    return;
  }
  next();
}
