import { describe, expect, it, vi } from 'vitest';
import { compressUploadFile, isPdfFile } from '@/shared/uploads/lib/compress-image';

vi.mock('browser-image-compression', () => ({
  default: async (file: File) => {
    const smaller = Math.max(1, Math.floor(file.size * 0.25));

    return new File([new Uint8Array(smaller)], file.name, { type: file.type });
  },
}));

describe('compressUploadFile', () => {
  it('compresses oversized images to a smaller file without changing the name', async () => {
    const large = new File([new Uint8Array(2 * 1024 * 1024)], 'photo.jpg', {
      type: 'image/jpeg',
    });
    const compressed = await compressUploadFile(large, 'logo');

    expect(compressed.name).toBe('photo.jpg');
    expect(compressed.type).toBe('image/jpeg');
    expect(compressed.size).toBeLessThan(large.size);
  });

  it('passes PDFs through uncompressed', async () => {
    const pdf = new File([new Uint8Array(64)], 'id_image.pdf', {
      type: 'application/pdf',
    });
    const result = await compressUploadFile(pdf, 'document');

    expect(result).toBe(pdf);
    expect(isPdfFile(pdf)).toBe(true);
  });
});
