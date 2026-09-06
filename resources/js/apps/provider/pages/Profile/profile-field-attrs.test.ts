import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  PROFILE_ABOUT_MAX_LENGTH,
  PROFILE_ADDRESS_MAX_LENGTH,
  PROFILE_FIELD_LABEL_CLASS,
  PROFILE_IBAN_MAX_LENGTH,
  PROFILE_LOGO_THUMB_CLASS,
  PROFILE_PASSWORD_MAX_LENGTH,
  PROFILE_PASSWORD_MIN_LENGTH,
  PROFILE_PHONE_MAX_LENGTH,
} from '@/apps/provider/pages/Profile/constants';

const generalSrc = readFileSync(
  join(__dirname, 'components/GeneralInfoCard.tsx'),
  'utf8',
);
const passwordSrc = readFileSync(
  join(__dirname, 'components/PasswordCard.tsx'),
  'utf8',
);
const logoSrc = readFileSync(join(__dirname, 'components/LogoCard.tsx'), 'utf8');
const constantsSrc = readFileSync(join(__dirname, 'constants.ts'), 'utf8');

describe('Profile HTML validation + layout constants', () => {
  it('wires registration field length constants into GeneralInfoCard inputs', () => {
    expect(generalSrc).toContain('maxLength={PROFILE_PHONE_MAX_LENGTH}');
    expect(generalSrc).toContain('maxLength={PROFILE_IBAN_MAX_LENGTH}');
    expect(generalSrc).toContain('maxLength={PROFILE_ADDRESS_MAX_LENGTH}');
    expect(generalSrc).toContain('maxLength={PROFILE_ABOUT_MAX_LENGTH}');
    expect(generalSrc).toContain('type="email"');
    expect(generalSrc).toContain('type="tel"');
    expect(PROFILE_PHONE_MAX_LENGTH).toBe(14);
    expect(PROFILE_IBAN_MAX_LENGTH).toBe(24);
    expect(PROFILE_ADDRESS_MAX_LENGTH).toBe(500);
    expect(PROFILE_ABOUT_MAX_LENGTH).toBe(1000);
  });

  it('wires password min/max length HTML attributes from registration constants', () => {
    expect(passwordSrc).toContain('minLength={enforceMinLength ? PROFILE_PASSWORD_MIN_LENGTH');
    expect(passwordSrc).toContain('maxLength={PROFILE_PASSWORD_MAX_LENGTH}');
    expect(PROFILE_PASSWORD_MIN_LENGTH).toBe(8);
    expect(PROFILE_PASSWORD_MAX_LENGTH).toBe(64);
  });

  it('keeps the logo thumb compact and reuses registration compression', () => {
    expect(PROFILE_LOGO_THUMB_CLASS).toContain('symbol-70px');
    expect(PROFILE_LOGO_THUMB_CLASS).not.toContain('symbol-100px');
    expect(logoSrc).toContain('PROFILE_LOGO_THUMB_CLASS');
    expect(logoSrc).toContain('object-fit-cover');
    expect(constantsSrc).toContain('REGISTRATION_LOGO_COMPRESSION');
    expect(constantsSrc).toContain(PROFILE_FIELD_LABEL_CLASS.split(' ')[0]);
  });
});
