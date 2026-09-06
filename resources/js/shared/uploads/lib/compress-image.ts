import {
  UPLOAD_DOCUMENT_IMAGE_COMPRESSION,
  UPLOAD_LOGO_COMPRESSION,
  type ImageCompressionProfile,
  type UploadCompressionKind,
} from '@/shared/uploads/constants';
import imageCompression from 'browser-image-compression';

export function isPdfFile(file: File): boolean {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

export function isImageFile(file: File): boolean {
  return file.type.startsWith('image/');
}

/**
 * Compress an image with an explicit profile. PDFs / non-images pass through.
 */
export async function compressImageFile(file: File, profile: ImageCompressionProfile): Promise<File> {
  if (isPdfFile(file) || !isImageFile(file)) {
    return file;
  }

  const compressed = await imageCompression(file, {
    maxWidthOrHeight: profile.maxWidthOrHeight,
    initialQuality: profile.initialQuality,
    maxSizeMB: profile.maxSizeMB,
    useWebWorker: true,
    fileType: file.type || undefined,
  });

  return new File([compressed], file.name, {
    type: compressed.type || file.type,
    lastModified: Date.now(),
  });
}

/**
 * Compress before eager upload using the logo or document (KYC) profile.
 */
export async function compressUploadFile(file: File, kind: UploadCompressionKind): Promise<File> {
  const profile = kind === 'logo' ? UPLOAD_LOGO_COMPRESSION : UPLOAD_DOCUMENT_IMAGE_COMPRESSION;

  return compressImageFile(file, profile);
}
