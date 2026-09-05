export const REGISTRATION_STEP_STORAGE_KEY = 'provider-registration-current-step';

/** OTP / final-submit step index in the registration stepper (1-based). */
export const REGISTRATION_OTP_STEP = 6;

/** Files step index (1-based) — used when recovering from expired upload references. */
export const REGISTRATION_FILES_STEP = 4;

export {
  REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY,
} from './registration-upload-constants';

import { REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY } from './registration-upload-constants';

export function readStoredRegistrationStep(totalSteps: number): number | null {
  try {
    const stored = sessionStorage.getItem(REGISTRATION_STEP_STORAGE_KEY);

    if (! stored) {
      return null;
    }

    const step = Number.parseInt(stored, 10);

    if (Number.isNaN(step) || step < 1 || step > totalSteps) {
      return null;
    }

    return step;
  } catch {
    return null;
  }
}

export function writeStoredRegistrationStep(step: number): void {
  try {
    sessionStorage.setItem(REGISTRATION_STEP_STORAGE_KEY, String(step));
  } catch {
    // Ignore storage failures (private browsing, SSR, etc.).
  }
}

export function clearStoredRegistrationStep(): void {
  try {
    sessionStorage.removeItem(REGISTRATION_STEP_STORAGE_KEY);
  } catch {
    // Ignore storage failures.
  }
}

export function readOrCreateRegistrationUploadToken(): string {
  try {
    const existing = sessionStorage.getItem(REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY);

    if (existing && isUuid(existing)) {
      return existing;
    }

    const token = crypto.randomUUID();
    sessionStorage.setItem(REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY, token);

    return token;
  } catch {
    return crypto.randomUUID();
  }
}

export function clearStoredRegistrationUploadToken(): void {
  try {
    sessionStorage.removeItem(REGISTRATION_UPLOAD_TOKEN_STORAGE_KEY);
  } catch {
    // Ignore storage failures.
  }
}

/** Clears step + upload-token session keys (success or explicit restart). */
export function clearRegistrationSessionStorage(): void {
  clearStoredRegistrationStep();
  clearStoredRegistrationUploadToken();
}

export function resolveInitialRegistrationStep(
  totalSteps: number,
  hasServerErrors: boolean,
): number {
  if (! hasServerErrors) {
    return 1;
  }

  return readStoredRegistrationStep(totalSteps) ?? REGISTRATION_OTP_STEP;
}

function isUuid(value: string): boolean {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
}
