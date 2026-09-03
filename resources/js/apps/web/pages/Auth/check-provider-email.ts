import axios from '@/shared/helpers/axios';
import AjaxController from '@/actions/App/Http/Controllers/General/AjaxController';
import { AxiosError } from 'axios';

type ValidationErrors = Record<string, string[]>;

export type ProviderEmailCheckResult =
  | { status: 'available' }
  | { status: 'invalid'; message: string }
  | { status: 'failed' };

export async function checkProviderRegistrationEmail(
  locale: string,
  email: string,
): Promise<ProviderEmailCheckResult> {
  const trimmed = email.trim();

  if (trimmed === '') {
    return { status: 'available' };
  }

  try {
    await axios.post(`/${locale}${AjaxController.checkEmail().url}`, { email: trimmed });

    return { status: 'available' };
  } catch (error: unknown) {
    if (error instanceof AxiosError && error.response?.status === 422) {
      const errors = error.response.data?.errors as ValidationErrors | undefined;
      const message = errors?.email?.[0];

      if (message) {
        return { status: 'invalid', message };
      }
    }

    return { status: 'failed' };
  }
}
