import { OfferStatusEnum } from '@/Enums/Order';
import type { Order, OrderOffer } from '@/shared/types/models';
import type { StatusBadgeStatus } from '@/shared/components/ui';
import { EMPTY_VALUE_FALLBACK } from '@/shared/components/ui/types';
import type { OrderTabKey } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';

/**
 * Column header i18n keys for the Home "your price" column.
 * Pending = submitted offer not yet accepted; other tabs = accepted/agreed amount.
 * Column position stays fixed; only the label clarifies lifecycle context.
 */
export const HOME_ORDER_YOUR_PRICE_LABEL_KEY = {
  pending: 'your_offer',
  approved: 'agreed_price',
  in_progress: 'agreed_price',
  ended_by_provider: 'agreed_price',
} as const satisfies Record<OrderTabKey, 'your_offer' | 'agreed_price'>;

export type HomeOrderYourPriceLabelKey =
  (typeof HOME_ORDER_YOUR_PRICE_LABEL_KEY)[OrderTabKey];

const ACCEPTED_OFFER_STATUS_VALUES = new Set<string>([
  OfferStatusEnum.Accepted,
  OfferStatusEnum.Paid,
]);

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
    offers.find((offer) => offer.status?.value === OfferStatusEnum.Pending) ??
    offers[0]
  );
}

export function homeOrderYourPriceLabelKey(
  tabKey: OrderTabKey,
): HomeOrderYourPriceLabelKey {
  return HOME_ORDER_YOUR_PRICE_LABEL_KEY[tabKey];
}

/**
 * Order/Offer `price` is uncast in Eloquent and arrives over Inertia/JSON as a
 * string (e.g. `"179.19"`). Show uses `formatCurrency`, which accepts strings;
 * this helper must coerce the same way or the Home column falls back to `—`.
 */
export function coerceHomeOrderPrice(value: unknown): number | null {
  if (value === null || value === undefined || value === '') {
    return null;
  }

  if (typeof value === 'number') {
    return Number.isNaN(value) ? null : value;
  }

  if (typeof value === 'string') {
    const amount = Number(value);
    return Number.isNaN(amount) ? null : amount;
  }

  return null;
}

/**
 * Provider's own price for the Home table.
 *
 * Data already available from `listWindowedForProviderHome` (no extra eager-load):
 * - pending: provider-scoped `offers[].price` (submitted, awaiting approval)
 * - other tabs: `order.price` (set on accept) and/or accepted offer in `offers`
 */
export function resolveHomeOrderYourPrice(
  order: Order,
  tabKey: OrderTabKey,
): number | null {
  if (tabKey === 'pending') {
    return coerceHomeOrderPrice(getProviderOfferOnOrder(order)?.price);
  }

  const orderPrice = coerceHomeOrderPrice(order.price);
  if (orderPrice !== null && orderPrice > 0) {
    return orderPrice;
  }

  const acceptedOffer =
    order.offers?.find((offer) =>
      ACCEPTED_OFFER_STATUS_VALUES.has(offer.status?.value ?? ''),
    ) ?? order.accepted_offer;

  const acceptedPrice = coerceHomeOrderPrice(acceptedOffer?.price);
  if (acceptedPrice !== null) {
    return acceptedPrice;
  }

  return coerceHomeOrderPrice(getProviderOfferOnOrder(order)?.price);
}

export function formatHomeOrderYourPrice(
  price: number | null,
  format: (value: number) => string,
): string {
  if (price === null) {
    return EMPTY_VALUE_FALLBACK;
  }

  return format(price);
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
