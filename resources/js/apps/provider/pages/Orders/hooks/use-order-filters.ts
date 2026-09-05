import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';

export type UseOrderFiltersOptions<TFilters extends Record<string, unknown>> = {
  prams: TFilters | null;
  defaults: TFilters;
  url: string;
  only?: string[];
};

/**
 * Core filter commit used by `useOrderFilters`.
 * PageFilterBar owns Enter-vs-onChange UX; this applies the resulting name/value
 * to query params and navigates via visitWithFilters.
 */
export function commitOrderFilterChange<TFilters extends Record<string, unknown>>(
  filters: TFilters,
  name: string,
  value: string,
  url: string,
  only: string[] = ['rows', 'prams'],
): TFilters {
  const next = applyFilterParam(
    { ...filters },
    name as keyof TFilters & string,
    value,
  );
  visitWithFilters(url, next as Record<string, unknown>, { only });
  return next;
}

export function useOrderFilters<TFilters extends Record<string, unknown>>({
  prams,
  defaults,
  url,
  only = ['rows', 'prams'],
}: UseOrderFiltersOptions<TFilters>): {
  filters: TFilters;
  onFilterChange: (name: string, value: string) => void;
} {
  const filters = prams ?? defaults;

  const onFilterChange = (name: string, value: string) => {
    commitOrderFilterChange(filters, name, value, url, only);
  };

  return { filters, onFilterChange };
}
