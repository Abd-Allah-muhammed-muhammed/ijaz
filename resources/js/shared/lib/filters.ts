import { router } from '@inertiajs/react';

/**
 * Immutably set or remove a filter param.
 * Empty string / null / undefined removes the key so it does not linger in the query string.
 */
export function applyFilterParam<T extends Record<string, unknown>>(
  params: T,
  key: keyof T & string,
  value: string | number | undefined | null,
): T {
  const next = { ...params };

  if (value === '' || value == null) {
    delete next[key];
  } else {
    next[key] = value as T[keyof T & string];
  }

  return next;
}

type VisitFilterOptions = {
  only?: string[];
  preserveState?: boolean;
  preserveScroll?: boolean;
};

/**
 * Navigate with a fully rebuilt query string from a clean base URL.
 * Prefer this over router.reload({ data }) — reload merges into the current URL
 * and cannot drop keys that were deleted from the data object.
 */
export function visitWithFilters(
  url: string,
  params: Record<string, unknown>,
  options: VisitFilterOptions = {},
): void {
  const cleaned = Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== '' && value != null),
  );

  router.get(url, cleaned, {
    preserveState: options.preserveState ?? true,
    preserveScroll: options.preserveScroll ?? true,
    ...(options.only ? { only: options.only } : {}),
  });
}
