import { describe, expect, it, vi } from 'vitest';
import {
  runPrecognitiveValidation,
  validateRegistrationStepClient,
  type PrecognitiveValidateConfig,
} from './register-step-advance';
import { availableSteps } from './providerSchema';

const accountStep = availableSteps.find((step) => step.titleKey === 'account_information');

if (! accountStep) {
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

describe('validateRegistrationStepClient', () => {
  it('blocks advance when Zod format validation fails', () => {
    const result = validateRegistrationStepClient(accountStep, {
      ...validAccountData,
      phone: 'not-a-phone',
    });

    expect(result.success).toBe(false);
    if (! result.success) {
      expect(result.fieldErrors.phone).toBeDefined();
    }
  });

  it('passes client validation for well-formed account data', () => {
    expect(validateRegistrationStepClient(accountStep, validAccountData).success).toBe(true);
  });
});

describe('runPrecognitiveValidation', () => {
  it('resolves success when onPrecognitionSuccess fires (blur then Next duplicate-guard path)', async () => {
    const validate = vi.fn((config: PrecognitiveValidateConfig) => {
      config.onPrecognitionSuccess?.();
      config.onFinish?.();
    });

    await expect(runPrecognitiveValidation(validate, ['phone', 'email', 'iban'])).resolves.toBe('success');
    expect(validate).toHaveBeenCalledWith(expect.objectContaining({
      only: ['phone', 'email', 'iban'],
    }));
  });

  it('blocks Next when duplicate/invalid fields return validation_error', async () => {
    const validate = vi.fn((config: PrecognitiveValidateConfig) => {
      config.onValidationError?.();
      config.onFinish?.();
    });

    await expect(runPrecognitiveValidation(validate, ['phone'])).resolves.toBe('validation_error');
  });

  it('returns failed on network/non-422 outcomes without treating them as success', async () => {
    const validate = vi.fn((config: PrecognitiveValidateConfig) => {
      config.onFinish?.();
    });

    await expect(runPrecognitiveValidation(validate, ['email'])).resolves.toBe('failed');
  });

  it('short-circuits to success when no fields need server validation', async () => {
    const validate = vi.fn();

    await expect(runPrecognitiveValidation(validate, [])).resolves.toBe('success');
    expect(validate).not.toHaveBeenCalled();
  });
});
