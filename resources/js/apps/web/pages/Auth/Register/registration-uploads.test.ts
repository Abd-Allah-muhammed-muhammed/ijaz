import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { compressRegistrationFile, isPdfFile } from './compress-registration-image';
import {
  REGISTRATION_FILES_STEP,
  REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY,
  clearRegistrationSessionStorage,
  readOrCreateRegistrationUploadToken,
} from './registration-step-storage';
import { extractExpiredUploadFieldsForTest } from './hooks/upload-error-helpers';

vi.mock('browser-image-compression', () => ({
  default: vi.fn(async (file: File) => {
    // Simulate compression by returning a smaller blob while preserving type/name.
    const smaller = new Blob([new Uint8Array(Math.max(16, Math.floor(file.size / 4)))], { type: file.type });

    return new File([smaller], file.name, { type: file.type, lastModified: Date.now() });
  }),
}));

describe('compressRegistrationFile', () => {
  it('compresses oversized images to a smaller file without changing the name', async () => {
    const large = new File([new Uint8Array(2_000_000)], 'photo.jpg', { type: 'image/jpeg' });
    const compressed = await compressRegistrationFile(large, 'logo');

    expect(compressed.name).toBe('photo.jpg');
    expect(compressed.type).toBe('image/jpeg');
    expect(compressed.size).toBeLessThan(large.size);
  });

  it('passes PDFs through uncompressed', async () => {
    const pdf = new File([new Uint8Array(500_000)], 'cert.pdf', { type: 'application/pdf' });
    const result = await compressRegistrationFile(pdf, 'id_image');

    expect(isPdfFile(pdf)).toBe(true);
    expect(result).toBe(pdf);
    expect(result.size).toBe(pdf.size);
  });
});

describe('registration upload token storage', () => {
  const memory = new Map<string, string>();

  beforeEach(() => {
    memory.clear();
    vi.stubGlobal('sessionStorage', {
      getItem: (key: string) => memory.get(key) ?? null,
      setItem: (key: string, value: string) => {
        memory.set(key, value);
      },
      removeItem: (key: string) => {
        memory.delete(key);
      },
    });
    vi.stubGlobal('crypto', {
      randomUUID: () => '11111111-1111-4111-8111-111111111111',
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('persists a stable upload token and clears it with registration session storage', () => {
    const first = readOrCreateRegistrationUploadToken();
    const second = readOrCreateRegistrationUploadToken();

    expect(first).toBe(second);
    expect(memory.get(REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY)).toBe(first);

    clearRegistrationSessionStorage();
    expect(memory.get(REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY)).toBeUndefined();
  });
});

describe('expired upload reference routing helpers', () => {
  it('extracts field keys from uploads.* validation errors', () => {
    const fields = extractExpiredUploadFieldsForTest({
      'uploads.logo': 'Please re-upload logo',
      'uploads.license_to_practice_law': 'Please re-upload license',
      otp: 'invalid',
    });

    expect(fields).toEqual(['logo', 'license_to_practice_law']);
    expect(REGISTRATION_FILES_STEP).toBe(4);
  });
});
