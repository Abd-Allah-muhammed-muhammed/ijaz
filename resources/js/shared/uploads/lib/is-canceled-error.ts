/**
 * Detect axios cancel / AbortController abort errors so callers can ignore them.
 */
export function isCanceledError(error: unknown): boolean {
  if (!error || typeof error !== 'object') {
    return false;
  }

  if ('code' in error && (error as { code?: string }).code === 'ERR_CANCELED') {
    return true;
  }

  return error instanceof DOMException && error.name === 'AbortError';
}
