import { url } from '@/shared/helpers/general';
import type { ApiResponse } from '@/shared/types/api';
import axios, {
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
} from 'axios';

/**
 * Shared HTTP client for XHR / react-query fetches (not Inertia visits).
 *
 * Existing hooks import via `@/shared/helpers/axios` (re-export). Prefer this module
 * for new code:
 *
 * ```ts
 * import apiClient, { apiGet, apiGetData, setApiLocale } from '@/shared/lib/api-client';
 *
 * const cities = await apiGetData<SelectOption>(path, { signal });
 * // or:
 * const { data } = await apiClient.get<ApiResponse<SelectOption>>(path, { signal });
 * ```
 *
 * Locale: call `setApiLocale(locale)` once at app boot (see `app.tsx`).
 * Do not set Accept-Language on the global `axios` defaults — query hooks use this instance.
 */

const apiClient: AxiosInstance = axios.create({
  timeout: 10_000,
  baseURL: url('/'),
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});

apiClient.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error: unknown) => Promise.reject(error),
);

/** Sets Accept-Language on the shared instance (used by all react-query hooks). */
export function setApiLocale(locale: string): void {
  apiClient.defaults.headers.common['Accept-Language'] = locale;
}

export async function apiGet<T>(path: string, config?: AxiosRequestConfig): Promise<T> {
  const { data } = await apiClient.get<T>(path, config);
  return data;
}

export async function apiPost<T>(
  path: string,
  body?: unknown,
  config?: AxiosRequestConfig,
): Promise<T> {
  const { data } = await apiClient.post<T>(path, body, config);
  return data;
}

export async function apiPut<T>(
  path: string,
  body?: unknown,
  config?: AxiosRequestConfig,
): Promise<T> {
  const { data } = await apiClient.put<T>(path, body, config);
  return data;
}

export async function apiPatch<T>(
  path: string,
  body?: unknown,
  config?: AxiosRequestConfig,
): Promise<T> {
  const { data } = await apiClient.patch<T>(path, body, config);
  return data;
}

export async function apiDelete<T>(path: string, config?: AxiosRequestConfig): Promise<T> {
  const { data } = await apiClient.delete<T>(path, config);
  return data;
}

/**
 * GET that unwraps `ApiResponse<T>.data` — matches current select-query hooks
 * (`const { data } = await axios.get<ApiResponse<T>>(...); return data.data`).
 */
export async function apiGetData<T>(
  path: string,
  config?: AxiosRequestConfig,
): Promise<T[]> {
  const payload = await apiGet<ApiResponse<T>>(path, config);
  return payload.data;
}

export type { AxiosError, AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

export default apiClient;
