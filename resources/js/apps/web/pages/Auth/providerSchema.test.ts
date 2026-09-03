import { describe, expect, it } from 'vitest';
import i18n from '@/lang/i18next';
import {
  accountInformationStepRules,
  isAllowedCertificateMimeType,
  isValidSaudiIban,
  normalizeSaudiIban,
  PASSWORD_MIN_LENGTH,
} from '@/apps/web/pages/Auth/providerSchema';

const validAccountData = {
  name: 'Test Provider',
  about: 'About text that is long enough',
  email: 'test@example.com',
  phone: '512345678',
  address: 'Riyadh',
  region_id: 1,
  city_id: 1,
  iban: 'SA0380000000608010167519',
  password: 'secret12',
  password_confirmation: 'secret12',
};

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

describe('providerSchema certificate mime validation', () => {
  it('accepts pdf and image mime types for certificate uploads', () => {
    expect(isAllowedCertificateMimeType('application/pdf')).toBe(true);
    expect(isAllowedCertificateMimeType('image/jpeg')).toBe(true);
    expect(isAllowedCertificateMimeType('image/png')).toBe(true);
    expect(isAllowedCertificateMimeType('text/plain')).toBe(false);
  });
});

describe('providerSchema password minimum validation', () => {
  it('rejects passwords shorter than the configured minimum', () => {
    const result = accountInformationStepRules.safeParse({
      ...validAccountData,
      password: 'short',
      password_confirmation: 'short',
    });

    const passwordIssue = result.error?.issues.find((issue) => issue.path[0] === 'password');

    expect(passwordIssue?.message).toBeDefined();
    expect(String(PASSWORD_MIN_LENGTH)).toBe('8');
  });
});

describe('providerSchema validation i18n at runtime', () => {
  it('resolves validation.regex in Arabic when validating phone format', async () => {
    await i18n.changeLanguage('ar');

    const result = accountInformationStepRules.safeParse({
      ...validAccountData,
      phone: '12345',
    });

    const phoneIssue = result.error?.issues.find((issue) => issue.path[0] === 'phone');

    expect(phoneIssue?.message).toBeDefined();
    expect(phoneIssue?.message).not.toMatch(/^validation\./);
    expect(phoneIssue?.message).toContain('صيغة');
  });

  it('resolves validation.invalid_saudi_iban in Arabic when validating iban format', async () => {
    await i18n.changeLanguage('ar');

    const result = accountInformationStepRules.safeParse({
      ...validAccountData,
      iban: 'NOT-VALID',
    });

    const ibanIssue = result.error?.issues.find((issue) => issue.path[0] === 'iban');

    expect(ibanIssue?.message).toBeDefined();
    expect(ibanIssue?.message).not.toMatch(/^validation\./);
    expect(ibanIssue?.message).toContain('آيبان');
  });
});
