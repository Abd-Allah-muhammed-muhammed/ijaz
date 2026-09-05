import type { Order, OrderOffer } from '@/shared/types/models';
import type { StatusBadgeStatus } from '@/shared/components/ui';
import type { OrderTabKey } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';

/**
 * Home windowed orders eager-load this provider's offers only.
 * Prefer a pending offer when present (matches the pending-tab population).
 */
export function getProviderOfferOnOrder(order: Order): OrderOffer | undefined {
  const offers = order.offers ?? [];
  if (offers.length === 0) {
    return undefined;
  }

  return (
    offers.find((offer) => offer.status?.value === 'pending') ?? offers[0]
  );
}

/**
 * Pending tab: show this provider's offer status (awaiting client approval).
 * Other tabs: order lifecycle status is the meaningful signal.
 */
export function resolveHomeOrderRowStatus(
  order: Order,
  tabKey: OrderTabKey,
): StatusBadgeStatus {
  if (tabKey === 'pending') {
    const offerStatus = getProviderOfferOnOrder(order)?.status;
    if (offerStatus) {
      return offerStatus;
    }
  }

  return order.status;
}
