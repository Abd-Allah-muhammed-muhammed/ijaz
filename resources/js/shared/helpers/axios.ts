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
  default,
  setApiLocale,
} from '@/shared/lib/api-client';
