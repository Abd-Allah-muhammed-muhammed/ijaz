import { useEffect, useState } from 'react';
import { AxiosError } from 'axios';
import { toast } from 'sonner';
import axios from '@/shared/helpers/axios';
import AuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';
import type { Inputs } from '../providerSchema';

const OTP_COUNTDOWN_MINUTES = 2;
const SECONDS_PER_MINUTE = 60;
const OTP_COUNTDOWN_SECONDS = OTP_COUNTDOWN_MINUTES * SECONDS_PER_MINUTE;

/** Fields the registration form can display errors for from the OTP endpoint. */
const OTP_FORM_ERROR_FIELDS = ['phone', 'otp'] as const satisfies ReadonlyArray<keyof Inputs>;

type OtpFormErrorField = (typeof OTP_FORM_ERROR_FIELDS)[number];

export type OtpFieldErrors = Partial<Record<string, string[]>>;

export type UseOtpOptions = {
  phone: string | null;
  processing: boolean;
  onValidationErrors: (errors: Partial<Record<keyof Inputs, string>>) => void;
};

export function formatOtpSeconds(totalSeconds: number): string {
  const minutes = Math.floor(totalSeconds / SECONDS_PER_MINUTE);
  const seconds = totalSeconds % SECONDS_PER_MINUTE;

  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function isOtpFormErrorField(field: string): field is OtpFormErrorField {
  return (OTP_FORM_ERROR_FIELDS as ReadonlyArray<string>).includes(field);
}

/**
 * Maps Laravel-style OTP validation errors (`string[]` per field) onto the
 * Inertia form error shape (`string` per Inputs key). Unknown keys are dropped.
 */
export function otpErrorsToFormErrors(
  errors: OtpFieldErrors,
): Partial<Record<keyof Inputs, string>> {
  const formErrors: Partial<Record<keyof Inputs, string>> = {};

  for (const [field, messages] of Object.entries(errors)) {
    if (! isOtpFormErrorField(field)) {
      continue;
    }

    const message = messages?.[0];

    if (message) {
      formErrors[field] = message;
    }
  }

  return formErrors;
}

/**
 * OTP send + resend countdown shared by Next-into-OTP and the OTP step Resend link.
 */
export function useOtp({ phone, processing, onValidationErrors }: UseOtpOptions) {
  const [seconds, setSeconds] = useState(0);

  useEffect(() => {
    const interval = setInterval(() => {
      if (seconds <= 0) {
        clearInterval(interval);
      }
      setSeconds((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);

    return () => {
      clearInterval(interval);
    };
  }, [seconds]);

  const sendOtp = async (): Promise<{ ok: true } | { ok: false; errors: OtpFieldErrors }> => {
    try {
      await axios.post(AuthController.otp().url, {
        phone,
      });

      return { ok: true };
    } catch (error: unknown) {
      if (error instanceof AxiosError && error.status === 422) {
        const errors = (error.response?.data.errors ?? {}) as OtpFieldErrors;

        return { ok: false, errors };
      }

      return {
        ok: false,
        errors: {
          otp: [String(error)],
        },
      };
    }
  };

  /**
   * Request a new OTP. Returns false when validation/network errors block advance.
   */
  const requestOtp = async (): Promise<boolean> => {
    const result = await sendOtp();

    if (! result.ok) {
      onValidationErrors(otpErrorsToFormErrors(result.errors));
      Object.values(result.errors).forEach((messages) => {
        if (messages?.[0]) {
          toast.error(messages[0]);
        }
      });

      return false;
    }

    setSeconds(OTP_COUNTDOWN_SECONDS);

    return true;
  };

  const resendOtp = async (): Promise<void> => {
    if (processing || seconds > 0) {
      return;
    }

    await requestOtp();
  };

  return {
    seconds,
    formatSeconds: formatOtpSeconds,
    requestOtp,
    resendOtp,
  };
}
