import type { ErrorRequestHandler, Request, Response, NextFunction } from 'express';

export class ApiError extends Error {
  constructor(public readonly status: number, public readonly code: string, message: string) {
    super(message);
    this.name = 'ApiError';
  }
}

export function notFound(_request: Request, response: Response): void {
  response.status(404).json({ data: null, error: { code: 'NOT_FOUND', message: 'Resource not found.' } });
}

export const errorHandler: ErrorRequestHandler = (error: unknown, _request: Request, response: Response, _next: NextFunction) => {
  if (error instanceof ApiError) {
    response.status(error.status).json({ data: null, error: { code: error.code, message: error.message } });
    return;
  }
  if (error instanceof SyntaxError && 'body' in error) {
    response.status(400).json({ data: null, error: { code: 'INVALID_JSON', message: 'Request body must be valid JSON.' } });
    return;
  }
  if (typeof error === 'object' && error !== null && 'type' in error && error.type === 'entity.too.large') {
    response.status(413).json({ data: null, error: { code: 'BODY_TOO_LARGE', message: 'Request body is too large.' } });
    return;
  }
  response.status(500).json({ data: null, error: { code: 'INTERNAL_ERROR', message: 'The request could not be completed.' } });
};
