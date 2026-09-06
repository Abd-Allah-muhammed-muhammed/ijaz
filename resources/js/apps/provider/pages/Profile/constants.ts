import {
  PASSWORD_MAX_LENGTH,
  PASSWORD_MIN_LENGTH,
  SAUDI_IBAN_MAX_LENGTH,
  SAUDI_PHONE_MAX_LENGTH,
} from '@/apps/web/pages/Auth/Register/providerSchema';
import { REGISTRATION_LOGO_COMPRESSION } from '@/apps/web/pages/Auth/Register/registration-upload-constants';

/** Server-authoritative logo limit (`UpdateProfileRequest` `max:2048` = 2MB). */
export const PROFILE_LOGO_MAX_BYTES = 2 * 1024 * 1024;

export const PROFILE_LOGO_MAX_LABEL = '2MB';

export const PROFILE_LOGO_ACCEPT = 'image/jpeg,image/png';

export const PROFILE_LOGO_MIME_TYPES = ['image/jpeg', 'image/png'] as const;

/** Re-export registration logo compression profile (1024px / ~80% quality). */
export const PROFILE_LOGO_COMPRESSION = REGISTRATION_LOGO_COMPRESSION;

/** Re-export registration field limits for HTML attributes. */
export const PROFILE_PHONE_MAX_LENGTH = SAUDI_PHONE_MAX_LENGTH;
export const PROFILE_IBAN_MAX_LENGTH = SAUDI_IBAN_MAX_LENGTH;
export const PROFILE_PASSWORD_MIN_LENGTH = PASSWORD_MIN_LENGTH;
export const PROFILE_PASSWORD_MAX_LENGTH = PASSWORD_MAX_LENGTH;

/** Match `UpdateProfileRequest` string max rules. */
export const PROFILE_NAME_MAX_LENGTH = 255;
export const PROFILE_EMAIL_MAX_LENGTH = 255;
export const PROFILE_ADDRESS_MAX_LENGTH = 500;
export const PROFILE_ABOUT_MAX_LENGTH = 1000;

/**
 * Categories chip list: tall enough for ~4–5 rows before internal scroll.
 * (Previously 110px — only ~2 rows visible.)
 */
export const PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX = 240;

/**
 * Fixed logo thumb size (px). Do NOT use Metronic `symbol-*` + Bootstrap `w-100`
 * together — `w-100` is `width: 100% !important` and overrides symbol sizing,
 * expanding the preview to the card width.
 */
export const PROFILE_LOGO_THUMB_SIZE_PX = 64;

/** Decorative classes only — size comes from PROFILE_LOGO_THUMB_SIZE_PX inline style. */
export const PROFILE_LOGO_THUMB_CLASS =
  'overflow-hidden rounded-3 border border-gray-100 flex-shrink-0 bg-light';

export const PROFILE_CARD_CLASS = 'mb-5';

/** Field labels — Orders/Show approved language. */
export const PROFILE_FIELD_LABEL_CLASS = 'fw-semibold text-gray-700 mb-2';

export const PROFILE_FIELD_LABEL_REQUIRED_CLASS = `${PROFILE_FIELD_LABEL_CLASS} required`;

export const PROFILE_DANGER_ZONE_CARD_CLASS =
  'card border border-danger border-opacity-50 shadow-sm rounded-4 mb-5';
