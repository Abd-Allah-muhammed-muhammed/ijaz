import { OrderStatusEnum, OfferStatusEnum } from '@/Enums/Order';
import {
  mapBackendOrderTabCounts,
  type BackendOrderTabCounts,
  type OrderTabCounts,
  type OrderTabKey,
} from './use-order-tab-counts';

/**
 * Home "needs attention" maps to the pending tab: New orders where this provider
 * has a pending offer awaiting client approval (see providerHomeScopedOrdersQuery).
 */
export const NEEDS_ATTENTION_TAB: OrderTabKey = 'pending';

/** Order status bucket used for the Home tab / count (not the CTA page filter). */
export const NEEDS_ATTENTION_ORDER_STATUS = OrderStatusEnum.New;

/**
 * Destination filter for the attention CTA — My Offers, pending offer status.
 * Index `?status=new` is wrong: those orders usually have provider_id null and
 * only appear via the provider's pending offers.
 */
export const NEEDS_ATTENTION_OFFERS_STATUS = OfferStatusEnum.Pending;

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
