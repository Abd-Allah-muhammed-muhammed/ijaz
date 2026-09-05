import imageCompression from 'browser-image-compression';
import {
  REGISTRATION_CERTIFICATE_IMAGE_COMPRESSION,
  REGISTRATION_LOGO_COMPRESSION,
  REGISTRATION_UPLOAD_FIELD_LOGO,
  type RegistrationUploadField,
} from './registration-upload-constants';

export function isPdfFile(file: File): boolean {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

export function isImageFile(file: File): boolean {
  return file.type.startsWith('image/');
}

/**
 * Compress images before eager upload. PDFs pass through unchanged.
 * Logo uses a smaller max edge; certificate images stay conservative for KYC legibility.
 */
export async function compressRegistrationFile(
  file: File,
  field: RegistrationUploadField,
): Promise<File> {
  if (isPdfFile(file) || ! isImageFile(file)) {
    return file;
  }

  const profile = field === REGISTRATION_UPLOAD_FIELD_LOGO
    ? REGISTRATION_LOGO_COMPRESSION
    : REGISTRATION_CERTIFICATE_IMAGE_COMPRESSION;

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
