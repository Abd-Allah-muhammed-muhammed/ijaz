import { OrderStatusEnum } from '@/Enums/Order';
import {
  mapBackendOrderTabCounts,
  type BackendOrderTabCounts,
  type OrderTabCounts,
  type OrderTabKey,
} from './use-order-tab-counts';

/**
 * Home "needs response" attention maps to orders in New status where the
 * provider has a pending offer awaiting client approval (pending tab).
 */
export const NEEDS_ATTENTION_TAB: OrderTabKey = 'pending';

export const NEEDS_ATTENTION_STATUS = OrderStatusEnum.New;

export function getNeedsAttentionCount(counts: OrderTabCounts): number {
  return counts[NEEDS_ATTENTION_TAB];
}

/**
 * Prefer passing already-mapped tab counts so counting logic stays single-source.
 * Accepts backend counts as a convenience wrapper.
 */
export function useNeedsAttentionCount(
  countsOrBackend: OrderTabCounts | BackendOrderTabCounts | null | undefined,
): number {
  const counts =
    countsOrBackend != null &&
    'pending' in countsOrBackend &&
    typeof countsOrBackend.pending === 'number'
      ? (countsOrBackend as OrderTabCounts)
      : mapBackendOrderTabCounts(countsOrBackend as BackendOrderTabCounts | null | undefined);

  return getNeedsAttentionCount(counts);
}
