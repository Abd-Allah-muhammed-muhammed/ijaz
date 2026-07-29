/**
 * @deprecated Import from `@/lib/api-client` for new code.
 * Re-exports the shared instance so existing `@/helpers/axios` imports keep working.
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
} from '@/lib/api-client';
