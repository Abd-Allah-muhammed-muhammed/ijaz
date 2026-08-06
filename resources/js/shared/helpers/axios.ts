/**
 * @deprecated Import from `@/shared/lib/api-client` for new code.
 * Re-exports the shared instance so existing `@/shared/helpers/axios` imports keep working.
 */
export {
  apiDelete,
  apiGet,
  apiGetData,
  apiPatch,
  apiPost,
  apiPut,
  FORM_DATA_TIMEOUT_MS,
  default,
  setApiLocale,
} from '@/shared/lib/api-client';
