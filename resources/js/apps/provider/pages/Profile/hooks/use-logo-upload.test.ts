import { describe, expect, it, vi } from 'vitest';
import { PROFILE_LOGO_MAX_BYTES } from '@/apps/provider/pages/Profile/constants';
import {
  prepareLogoFile,
  validateLogoFile,
  validateLogoMime,
  validateLogoSize,
} from '@/apps/provider/pages/Profile/hooks/use-logo-upload';

vi.mock('browser-image-compression', () => ({
  default: async (file: File) => {
    // Simulate compression: return a ~25% size blob while preserving type/name.
    const smaller = Math.max(1, Math.floor(file.size * 0.25));
    return new File([new Uint8Array(smaller)], file.name, { type: file.type });
  },
}));

function makeFile(size: number, type: string, name = 'logo.png'): File {
  const buffer = new Uint8Array(size);
  return new File([buffer], name, { type });
}

describe('validateLogoFile helpers', () => {
  it('accepts jpeg/png files within the 2MB server limit', () => {
    expect(validateLogoFile(makeFile(1024, 'image/jpeg', 'a.jpg'))).toBeNull();
    expect(validateLogoFile(makeFile(1024, 'image/png', 'a.png'))).toBeNull();
    expect(
      validateLogoFile(makeFile(PROFILE_LOGO_MAX_BYTES, 'image/png')),
    ).toBeNull();
  });

  it('rejects files larger than 2MB on size check', () => {
    expect(
      validateLogoSize(makeFile(PROFILE_LOGO_MAX_BYTES + 1, 'image/png')),
    ).toBe('too_large');
  });

  it('rejects non jpeg/png mime types', () => {
    expect(validateLogoMime(makeFile(100, 'image/webp', 'a.webp'))).toBe(
      'invalid_type',
    );
    expect(validateLogoMime(makeFile(100, 'application/pdf', 'a.pdf'))).toBe(
      'invalid_type',
    );
  });
});

describe('prepareLogoFile', () => {
  it('compresses oversized camera images before the 2MB size check', async () => {
    const original = makeFile(PROFILE_LOGO_MAX_BYTES + 500_000, 'image/jpeg', 'camera.jpg');
    const result = await prepareLogoFile(original);

    expect(result.errorCode).toBeNull();
    expect(result.file).not.toBeNull();
    expect(result.originalSize).toBe(original.size);
    expect(result.compressedSize).toBeLessThan(result.originalSize);
    expect(result.file?.size).toBeLessThanOrEqual(PROFILE_LOGO_MAX_BYTES);
  });

  it('rejects invalid mime types before compression', async () => {
    const result = await prepareLogoFile(makeFile(100, 'image/webp', 'a.webp'));
    expect(result.errorCode).toBe('invalid_type');
    expect(result.file).toBeNull();
  });
});
