/** Max file size in MB — must match config/provider_registration.php max_file_kilobytes / 1024. */
export const REGISTRATION_MAX_FILE_SIZE_MB = 8;

export const REGISTRATION_MAX_FILE_SIZE_BYTES = REGISTRATION_MAX_FILE_SIZE_MB * 1024 * 1024;

/** sessionStorage key for the per-attempt upload token (UUID). */
export const REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY = 'provider-registration-upload-token';

/**
 * Logo compression: aggressive resize is safe (no document legibility concern).
 * Must stay in sync with admin expectations for provider logos.
 */
export const REGISTRATION_LOGO_COMPRESSION = {
  maxWidthOrHeight: 1024,
  initialQuality: 0.8,
  maxSizeMB: 1.5,
} as const;

/**
 * KYC / certificate image compression: conservative — protect admin review legibility.
 * PDFs skip compression entirely.
 */
export const REGISTRATION_CERTIFICATE_IMAGE_COMPRESSION = {
  maxWidthOrHeight: 2500,
  initialQuality: 0.92,
  maxSizeMB: REGISTRATION_MAX_FILE_SIZE_MB,
} as const;

export const REGISTRATION_UPLOAD_FIELD_LOGO = 'logo' as const;

export type RegistrationUploadField =
  | typeof REGISTRATION_UPLOAD_FIELD_LOGO
  | 'id_image'
  | 'commercial_record'
  | 'iban_certification'
  | 'freelancer_certification'
  | 'license_to_practice_law';
