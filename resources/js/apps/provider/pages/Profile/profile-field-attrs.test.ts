import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  PROFILE_ABOUT_MAX_LENGTH,
  PROFILE_ADDRESS_MAX_LENGTH,
  PROFILE_AVATAR_CLASS,
  PROFILE_AVATAR_SIZE_PX,
  PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX,
  PROFILE_FIELD_LABEL_CLASS,
  PROFILE_IBAN_MAX_LENGTH,
  PROFILE_LOGO_THUMB_CLASS,
  PROFILE_LOGO_THUMB_SIZE_PX,
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
const identitySrc = readFileSync(
  join(__dirname, 'components/ProfileIdentityHeader.tsx'),
  'utf8',
);
const categoriesSrc = readFileSync(
  join(__dirname, 'components/CategoriesCard.tsx'),
  'utf8',
);
const requiredSrc = readFileSync(
  join(__dirname, 'components/RequiredFilesCard.tsx'),
  'utf8',
);
const formHookSrc = readFileSync(
  join(__dirname, 'hooks/use-profile-form.ts'),
  'utf8',
);
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

  it('keeps the logo thumb as an explicit 80px box (no Metronic symbol + w-100)', () => {
    expect(PROFILE_LOGO_THUMB_SIZE_PX).toBe(80);
    expect(PROFILE_LOGO_THUMB_CLASS).not.toContain('symbol');
    expect(logoSrc).toContain('PROFILE_LOGO_THUMB_SIZE_PX');
    expect(logoSrc).toContain('data-pan="profile-logo-img"');
    expect(logoSrc).not.toContain('w-100 h-100 object-fit-cover');
    expect(logoSrc).not.toContain('symbol-70px');
    expect(constantsSrc).toContain('UPLOAD_LOGO_COMPRESSION');
    expect(constantsSrc).toContain(PROFILE_FIELD_LABEL_CLASS.split(' ')[0]);
    expect(PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX).toBe(240);
  });

  it('constrains identity avatar to a hard 56px circle', () => {
    expect(PROFILE_AVATAR_SIZE_PX).toBe(56);
    expect(PROFILE_AVATAR_CLASS).toContain('rounded-circle');
    expect(PROFILE_AVATAR_CLASS).toContain('overflow-hidden');
    expect(identitySrc).toContain('PROFILE_AVATAR_SIZE_PX');
    expect(identitySrc).toContain("borderRadius: '50%'");
    expect(identitySrc).toContain("objectFit: 'cover'");
    expect(identitySrc).not.toContain('symbol-70px');
    expect(identitySrc).not.toContain('w-100 h-100 object-fit-cover');
  });

  it('renders categories as a scrollable vertical list of rows', () => {
    expect(categoriesSrc).toContain('PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX');
    expect(categoriesSrc).toContain('list-unstyled');
    expect(categoriesSrc).toContain('onRemoveCategory');
    expect(categoriesSrc).not.toContain('badge');
  });

  it('uploads required files in the background outside the Save payload', () => {
    expect(requiredSrc).toContain('BackgroundUploadTray');
    expect(requiredSrc).toContain('selectAndUpload');
    expect(requiredSrc).toContain('replace_file');
    expect(formHookSrc).not.toContain('id_image: undefined');
    expect(formHookSrc).not.toContain('setFileField');
  });

  it('adds password visibility toggles with Keenicons eye / eye-slash', () => {
    expect(passwordSrc).toContain("iconName={showPassword ? 'eye-slash' : 'eye'}");
    expect(passwordSrc).toContain(
      "iconName={showPasswordConfirmation ? 'eye-slash' : 'eye'}",
    );
    expect(passwordSrc).toContain('show_password');
    expect(passwordSrc).toContain('hide_password');
    expect(passwordSrc).toContain('profile-password-visibility');
  });
});
