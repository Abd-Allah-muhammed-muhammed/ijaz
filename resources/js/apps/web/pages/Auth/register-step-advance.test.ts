import { describe, expect, it, vi, beforeEach } from 'vitest';
import { validateRegistrationStepAdvance } from './register-step-advance';
import { availableSteps } from './providerSchema';
import * as checkPhoneModule from './check-provider-phone';

vi.mock('./check-provider-phone');

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
  password: 'secret',
  password_confirmation: 'secret',
};

describe('validateRegistrationStepAdvance phone duplicate guard', () => {
  beforeEach(() => {
    vi.resetAllMocks();
  });

  it('blocks step advance when duplicate phone is returned (blur error then Next without changing value)', async () => {
    vi.mocked(checkPhoneModule.checkProviderRegistrationPhone).mockResolvedValue({
      status: 'invalid',
      message: 'Phone already taken',
    });

    const blurResult = await checkPhoneModule.checkProviderRegistrationPhone('en', validAccountData.phone);
    expect(blurResult.status).toBe('invalid');

    const nextResult = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
    );

    expect(nextResult.success).toBe(false);
    if (!nextResult.success) {
      expect(nextResult.fieldErrors.phone).toBe('Phone already taken');
      expect(nextResult.blockedOnPhoneCheck).toBe(true);
    }

    expect(checkPhoneModule.checkProviderRegistrationPhone).toHaveBeenCalledTimes(2);
  });

  it('re-runs duplicate check on every Next click', async () => {
    vi.mocked(checkPhoneModule.checkProviderRegistrationPhone).mockResolvedValue({
      status: 'invalid',
      message: 'Phone already taken',
    });

    const first = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
    );
    const second = await validateRegistrationStepAdvance(
      accountStep,
      validAccountData,
      'en',
      validAccountData.phone,
    );

    expect(first.success).toBe(false);
    expect(second.success).toBe(false);
    if (!second.success) {
      expect(second.fieldErrors.phone).toBe('Phone already taken');
    }
    expect(checkPhoneModule.checkProviderRegistrationPhone).toHaveBeenCalledTimes(2);
  });
});
