/** Shared upload size limits and compression profiles (registration + profile). */

/** Max file size in MB — aligns with provider_registration.max_file_kilobytes / 1024. */
export const UPLOAD_MAX_FILE_SIZE_MB = 8;

export const UPLOAD_MAX_FILE_SIZE_BYTES = UPLOAD_MAX_FILE_SIZE_MB * 1024 * 1024;

export type ImageCompressionProfile = {
  maxWidthOrHeight: number;
  initialQuality: number;
  maxSizeMB: number;
};

/**
 * Logo compression: aggressive resize is safe (no document legibility concern).
 */
export const UPLOAD_LOGO_COMPRESSION: ImageCompressionProfile = {
  maxWidthOrHeight: 1024,
  initialQuality: 0.8,
  maxSizeMB: 1.5,
};

/**
 * KYC / certificate image compression: conservative — protect admin review legibility.
 * PDFs skip compression entirely.
 */
export const UPLOAD_DOCUMENT_IMAGE_COMPRESSION: ImageCompressionProfile = {
  maxWidthOrHeight: 2500,
  initialQuality: 0.92,
  maxSizeMB: UPLOAD_MAX_FILE_SIZE_MB,
};

export const UPLOAD_FIELD_LOGO = 'logo' as const;

export type UploadCompressionKind = 'logo' | 'document';
