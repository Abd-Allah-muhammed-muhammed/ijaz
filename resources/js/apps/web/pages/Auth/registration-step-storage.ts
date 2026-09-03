export const REGISTRATION_STEP_STORAGE_KEY = 'provider-registration-current-step';

/** OTP / final-submit step index in the registration stepper (1-based). */
export const REGISTRATION_OTP_STEP = 6;

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

export function resolveInitialRegistrationStep(
  totalSteps: number,
  hasServerErrors: boolean,
): number {
  if (! hasServerErrors) {
    return 1;
  }

  return readStoredRegistrationStep(totalSteps) ?? REGISTRATION_OTP_STEP;
}
