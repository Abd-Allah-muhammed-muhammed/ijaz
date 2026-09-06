/** Server-authoritative logo limit (`UpdateProfileRequest` `max:2048` = 2MB). */
export const PROFILE_LOGO_MAX_BYTES = 2 * 1024 * 1024;

export const PROFILE_LOGO_MAX_LABEL = '2MB';

export const PROFILE_LOGO_ACCEPT = 'image/jpeg,image/png';

export const PROFILE_LOGO_MIME_TYPES = ['image/jpeg', 'image/png'] as const;

/** Bounded internal scroll for selected category chips (not page-growing). */
export const PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX = 110;

export const PROFILE_LOGO_THUMB_CLASS =
  'symbol symbol-100px symbol-fixed overflow-hidden';

export const PROFILE_DANGER_ZONE_CARD_CLASS =
  'card border border-danger border-opacity-50 shadow-sm rounded-4 mb-5';
