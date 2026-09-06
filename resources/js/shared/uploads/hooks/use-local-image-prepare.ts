import { compressImageFile } from '@/shared/uploads/lib/compress-image';
import type {
  LocalImagePrepareErrorCode,
  LocalImagePrepareOptions,
  LocalImagePrepareResult,
} from '@/shared/uploads/types';

export function validateImageMime(
  file: File,
  allowedMimeTypes: readonly string[],
): Extract<LocalImagePrepareErrorCode, 'invalid_type'> | null {
  if (!allowedMimeTypes.includes(file.type)) {
    return 'invalid_type';
  }

  return null;
}

export function validateImageSize(
  file: File,
  maxBytes: number,
): Extract<LocalImagePrepareErrorCode, 'too_large'> | null {
  if (file.size > maxBytes) {
    return 'too_large';
  }

  return null;
}

/**
 * Mime-check → compress → size-check on the compressed file.
 * Shared by profile logo prepare (and any future local image pickers).
 */
export async function prepareLocalImage(
  file: File,
  options: LocalImagePrepareOptions,
): Promise<LocalImagePrepareResult> {
  const originalSize = file.size;
  const mimeError = validateImageMime(file, options.allowedMimeTypes);
  if (mimeError) {
    return {
      file: null,
      errorCode: mimeError,
      originalSize,
      compressedSize: null,
    };
  }

  try {
    const compressed = await compressImageFile(file, options.compressionProfile);
    const sizeError = validateImageSize(compressed, options.maxBytes);
    if (sizeError) {
      return {
        file: null,
        errorCode: sizeError,
        originalSize,
        compressedSize: compressed.size,
      };
    }

    return {
      file: compressed,
      errorCode: null,
      originalSize,
      compressedSize: compressed.size,
    };
  } catch {
    return {
      file: null,
      errorCode: 'compression_failed',
      originalSize,
      compressedSize: null,
    };
  }
}

export type { LocalImagePrepareOptions, LocalImagePrepareResult };
