import {
  UPLOAD_DOCUMENT_IMAGE_COMPRESSION,
  UPLOAD_FIELD_LOGO,
  UPLOAD_LOGO_COMPRESSION,
  UPLOAD_MAX_FILE_SIZE_BYTES,
  UPLOAD_MAX_FILE_SIZE_MB,
} from '@/shared/uploads/constants';

/** @deprecated Prefer UPLOAD_MAX_FILE_SIZE_MB from shared/uploads. */
export const REGISTRATION_MAX_FILE_SIZE_MB = UPLOAD_MAX_FILE_SIZE_MB;

/** @deprecated Prefer UPLOAD_MAX_FILE_SIZE_BYTES from shared/uploads. */
export const REGISTRATION_MAX_FILE_SIZE_BYTES = UPLOAD_MAX_FILE_SIZE_BYTES;

/** sessionStorage key for the per-attempt upload token (UUID). */
export const REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY = 'provider-registration-upload-token';

/** @deprecated Prefer UPLOAD_LOGO_COMPRESSION from shared/uploads. */
export const REGISTRATION_LOGO_COMPRESSION = UPLOAD_LOGO_COMPRESSION;

/** @deprecated Prefer UPLOAD_DOCUMENT_IMAGE_COMPRESSION from shared/uploads. */
export const REGISTRATION_CERTIFICATE_IMAGE_COMPRESSION = UPLOAD_DOCUMENT_IMAGE_COMPRESSION;

export const REGISTRATION_UPLOAD_FIELD_LOGO = UPLOAD_FIELD_LOGO;

export type RegistrationUploadField =
  | typeof REGISTRATION_UPLOAD_FIELD_LOGO
  | 'id_image'
  | 'commercial_record'
  | 'iban_certification'
  | 'freelancer_certification'
  | 'license_to_practice_law';
