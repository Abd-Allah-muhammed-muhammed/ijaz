import axios from '@/shared/helpers/axios';
import AjaxController from '@/actions/App/Http/Controllers/General/AjaxController';
import { AxiosError } from 'axios';

type ValidationErrors = Record<string, string[]>;

export type ProviderPhoneCheckResult =
  | { status: 'available' }
  | { status: 'invalid'; message: string }
  | { status: 'failed' };

export async function checkProviderRegistrationPhone(
  locale: string,
  phone: string,
): Promise<ProviderPhoneCheckResult> {
  const trimmed = phone.trim();

  if (trimmed === '') {
    return { status: 'available' };
  }

  try {
    await axios.post(`/${locale}${AjaxController.checkPhone().url}`, { phone: trimmed });

    return { status: 'available' };
  } catch (error: unknown) {
    if (error instanceof AxiosError && error.response?.status === 422) {
      const errors = error.response.data?.errors as ValidationErrors | undefined;
      const message = errors?.phone?.[0];

      if (message) {
        return { status: 'invalid', message };
      }
    }

    return { status: 'failed' };
  }
}
