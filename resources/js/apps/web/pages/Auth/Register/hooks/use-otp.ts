import { useEffect, useState } from 'react';
import { AxiosError } from 'axios';
import { toast } from 'sonner';
import axios from '@/shared/helpers/axios';
import AuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';

const OTP_COUNTDOWN_MINUTES = 2;
const SECONDS_PER_MINUTE = 60;
const OTP_COUNTDOWN_SECONDS = OTP_COUNTDOWN_MINUTES * SECONDS_PER_MINUTE;

export type OtpFieldErrors = Record<string, string[]>;

export type UseOtpOptions = {
  phone: string | null;
  processing: boolean;
  onValidationErrors: (errors: OtpFieldErrors) => void;
};

export function formatOtpSeconds(totalSeconds: number): string {
  const minutes = Math.floor(totalSeconds / SECONDS_PER_MINUTE);
  const seconds = totalSeconds % SECONDS_PER_MINUTE;

  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
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
          error: [String(error)],
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
      onValidationErrors(result.errors);
      Object.values(result.errors).forEach((messages) => {
        toast.error(messages[0]);
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
