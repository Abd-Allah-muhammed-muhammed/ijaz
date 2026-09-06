import { describe, expect, it } from 'vitest';
import { PROFILE_LOGO_MAX_BYTES } from '@/apps/provider/pages/Profile/constants';
import { validateLogoFile } from '@/apps/provider/pages/Profile/hooks/use-logo-upload';

function makeFile(size: number, type: string, name = 'logo.png'): File {
  const buffer = new Uint8Array(size);
  return new File([buffer], name, { type });
}

describe('validateLogoFile', () => {
  it('accepts jpeg/png files within the 2MB server limit', () => {
    expect(validateLogoFile(makeFile(1024, 'image/jpeg', 'a.jpg'))).toBeNull();
    expect(validateLogoFile(makeFile(1024, 'image/png', 'a.png'))).toBeNull();
    expect(
      validateLogoFile(makeFile(PROFILE_LOGO_MAX_BYTES, 'image/png')),
    ).toBeNull();
  });

  it('rejects files larger than 2MB', () => {
    expect(
      validateLogoFile(makeFile(PROFILE_LOGO_MAX_BYTES + 1, 'image/png')),
    ).toBe('too_large');
  });

  it('rejects non jpeg/png mime types', () => {
    expect(validateLogoFile(makeFile(100, 'image/webp', 'a.webp'))).toBe(
      'invalid_type',
    );
    expect(validateLogoFile(makeFile(100, 'application/pdf', 'a.pdf'))).toBe(
      'invalid_type',
    );
  });
});
