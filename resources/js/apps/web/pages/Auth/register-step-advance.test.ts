import { describe, expect, it, vi, beforeEach } from 'vitest';
import { availableSteps } from './providerSchema';

const { checkProviderRegistrationPhone, checkProviderRegistrationEmail } = vi.hoisted(() => ({
  checkProviderRegistrationPhone: vi.fn(),
  checkProviderRegistrationEmail: vi.fn(),
}));

vi.mock('./check-provider-phone', () => ({
  checkProviderRegistrationPhone,
}));

vi.mock('./check-provider-email', () => ({
  checkProviderRegistrationEmail,
}));

import { validateRegistrationStepAdvance } from './register-step-advance';

const accountStep = availableSteps.find((step) => step.titleKey === 'account_information');

if (!accountStep) {
  throw new Error('Account information step is missing from availableSteps');
}

const validAccountData = {
  name: 'Test Provider',
  about: 'About text that is long enough',
  email: 'test@example.com',
  phone: '512345678',
  address: 'Riyadh',
  region_id: 1,
  city_id: 1,
  iban: 'SA0380000000608010167519',
  password: 'secret12',
  password_confirmation: 'secret12',
};

describe('validateRegistrationStepAdvance phone duplicate guard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    checkProviderRegistrationEmail.mockResolvedValue({ status: 'available' });
  });

  it('blocks step advance when duplicate phone is returned (blur error then Next without changing value)', async () => {
    checkProviderRegistrationPhone.mockResolvedValue({
      status: 'invalid',
      message: 'Phone already taken',
    });

    const blurResult = await checkProviderRegistrationPhone('en', validAccountData.phone);
    expect(blurResult.status).toBe('invalid');

    const nextResult = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
      validAccountData.email,
    );

    expect(nextResult.success).toBe(false);
    if (!nextResult.success) {
      expect(nextResult.fieldErrors.phone).toBe('Phone already taken');
      expect(nextResult.blockedOnPhoneCheck).toBe(true);
    }

    expect(checkProviderRegistrationPhone).toHaveBeenCalledTimes(2);
  });

  it('re-runs duplicate check on every Next click', async () => {
    checkProviderRegistrationPhone.mockResolvedValue({
      status: 'invalid',
      message: 'Phone already taken',
    });

    const first = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
      validAccountData.email,
    );
    const second = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
      validAccountData.email,
    );

    expect(first.success).toBe(false);
    expect(second.success).toBe(false);
    if (!second.success) {
      expect(second.fieldErrors.phone).toBe('Phone already taken');
    }
    expect(checkProviderRegistrationPhone).toHaveBeenCalledTimes(2);
  });
});

describe('validateRegistrationStepAdvance email duplicate guard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    checkProviderRegistrationPhone.mockResolvedValue({ status: 'available' });
  });

  it('blocks step advance when duplicate email is returned (blur error then Next without changing value)', async () => {
    checkProviderRegistrationEmail.mockResolvedValue({
      status: 'invalid',
      message: 'Email already taken',
    });

    const blurResult = await checkProviderRegistrationEmail('en', validAccountData.email);
    expect(blurResult.status).toBe('invalid');

    const nextResult = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
      validAccountData.email,
    );

    expect(nextResult.success).toBe(false);
    if (!nextResult.success) {
      expect(nextResult.fieldErrors.email).toBe('Email already taken');
      expect(nextResult.blockedOnEmailCheck).toBe(true);
    }

    expect(checkProviderRegistrationEmail).toHaveBeenCalledTimes(2);
  });
});
