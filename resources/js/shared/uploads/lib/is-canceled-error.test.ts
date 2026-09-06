import { isCanceledError } from '@/shared/uploads/lib/is-canceled-error';
import { describe, expect, it } from 'vitest';

describe('isCanceledError', () => {
  it('returns false for nullish and non-objects', () => {
    expect(isCanceledError(null)).toBe(false);
    expect(isCanceledError(undefined)).toBe(false);
    expect(isCanceledError('AbortError')).toBe(false);
  });

  it('returns true for axios ERR_CANCELED', () => {
    expect(isCanceledError({ code: 'ERR_CANCELED' })).toBe(true);
  });

  it('returns false for other error codes', () => {
    expect(isCanceledError({ code: 'ERR_NETWORK' })).toBe(false);
  });

  it('returns true for DOMException AbortError', () => {
    expect(isCanceledError(new DOMException('Aborted', 'AbortError'))).toBe(true);
  });

  it('returns false for other DOMExceptions', () => {
    expect(isCanceledError(new DOMException('Nope', 'NotAllowedError'))).toBe(false);
  });
});
