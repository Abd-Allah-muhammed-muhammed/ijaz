import { describe, expect, it } from 'vitest';
import { isValidSaudiIban, normalizeSaudiIban } from '@/apps/web/pages/Auth/providerSchema';

describe('providerSchema Saudi IBAN validation', () => {
  it('normalizes whitespace and uppercase before validating', () => {
    expect(normalizeSaudiIban('sa03 8000 0000 6080 1016 7519')).toBe('SA0380000000608010167519');
    expect(isValidSaudiIban('sa03 8000 0000 6080 1016 7519')).toBe(true);
  });

  it('rejects invalid iban formats', () => {
    expect(isValidSaudiIban('NOT-A-VALID-IBAN')).toBe(false);
    expect(isValidSaudiIban('SA123')).toBe(false);
  });

  it('accepts a valid saudi iban', () => {
    expect(isValidSaudiIban('SA0380000000608010167519')).toBe(true);
  });
});
