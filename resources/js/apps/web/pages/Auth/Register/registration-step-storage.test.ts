import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
  clearStoredRegistrationStep,
  readStoredRegistrationStep,
  REGISTRATION_OTP_STEP,
  REGISTRATION_STEP_STORAGE_KEY,
  resolveInitialRegistrationStep,
  writeStoredRegistrationStep,
} from './registration-step-storage';

function createSessionStorageMock(): Storage {
  const store = new Map<string, string>();

  return {
    get length() {
      return store.size;
    },
    clear() {
      store.clear();
    },
    getItem(key: string) {
      return store.get(key) ?? null;
    },
    key(index: number) {
      return [...store.keys()][index] ?? null;
    },
    removeItem(key: string) {
      store.delete(key);
    },
    setItem(key: string, value: string) {
      store.set(key, value);
    },
  };
}

describe('registration step storage', () => {
  beforeEach(() => {
    vi.stubGlobal('sessionStorage', createSessionStorageMock());
  });

  it('restores the OTP step after a failed final submission when server errors are present', () => {
    writeStoredRegistrationStep(REGISTRATION_OTP_STEP);

    expect(resolveInitialRegistrationStep(7, true)).toBe(REGISTRATION_OTP_STEP);
    expect(readStoredRegistrationStep(7)).toBe(REGISTRATION_OTP_STEP);
  });

  it('starts at step one on a fresh visit without server errors', () => {
    writeStoredRegistrationStep(REGISTRATION_OTP_STEP);

    expect(resolveInitialRegistrationStep(7, false)).toBe(1);
  });

  it('clears persisted step state after successful completion', () => {
    writeStoredRegistrationStep(REGISTRATION_OTP_STEP);
    clearStoredRegistrationStep();

    expect(sessionStorage.getItem(REGISTRATION_STEP_STORAGE_KEY)).toBeNull();
    expect(readStoredRegistrationStep(7)).toBeNull();
  });
});
