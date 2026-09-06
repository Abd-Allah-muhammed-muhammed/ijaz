import type { Order } from '@/shared/types/models';
import { formatShortAbsoluteDiff, parseChatAbsoluteTime } from '@/shared/chat/utils/format-short-absolute-diff';

/** City – region label for list meta; uses Dashboard Resource flattened `title`. */
export function formatOrderLocation(
  order: Pick<Order, 'city' | 'region'>,
): string {
  return [order.city?.title, order.region?.title]
    .filter(Boolean)
    .join(' - ');
}

/** Compact relative time for list meta (e.g. `2h`), falling back to locale date. */
export function formatOrderListTime(
  value: string | Date | null | undefined,
  locale?: string,
  now: Date = new Date(),
): string {
  const parsed = parseChatAbsoluteTime(value ?? null);
  if (parsed) {
    return formatShortAbsoluteDiff(parsed, now);
  }

  if (value == null || value === '') {
    return '';
  }

  return new Date(value).toLocaleDateString(locale);
}

export function formatOrderBudgetRange(
  budgetStart: number | string | null | undefined,
  budgetEnd: number | string | null | undefined,
): string {
  return `${budgetStart ?? '—'} – ${budgetEnd ?? '—'}`;
}
