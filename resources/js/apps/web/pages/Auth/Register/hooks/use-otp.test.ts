import { describe, expect, it } from 'vitest';
import { otpErrorsToFormErrors } from './use-otp';

describe('otpErrorsToFormErrors', () => {
  it('flattens phone and otp message arrays into form error strings', () => {
    expect(
      otpErrorsToFormErrors({
        phone: ['The phone field is required.'],
        otp: ['Invalid code'],
      }),
    ).toEqual({
      phone: 'The phone field is required.',
      otp: 'Invalid code',
    });
  });

  it('drops unknown keys and empty message lists', () => {
    expect(
      otpErrorsToFormErrors({
        error: ['Network failed'],
        phone: [],
        name: ['ignored'],
      }),
    ).toEqual({});
  });
});
