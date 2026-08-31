import { OfferStatusEnum, OrderStatusEnum } from '@/Enums/Order';

/** Genuine terminal order statuses — excludes InProgress and other active states. */
export const ORDER_TERMINAL_STATUSES: readonly string[] = [
  OrderStatusEnum.CancelledByProvider,
  OrderStatusEnum.CancelledByClient,
  OrderStatusEnum.EndedByProvider,
  OrderStatusEnum.EndedByClient,
] as const;

export const orderStatusBadgeClass: Record<string, string> = {
  [OrderStatusEnum.New]: 'badge-light-primary',
  [OrderStatusEnum.Hold]: 'badge-light-secondary',
  [OrderStatusEnum.OfferProvided]: 'badge-light-info',
  [OrderStatusEnum.PaymentCompleted]: 'badge-light-warning',
  [OrderStatusEnum.InProgress]: 'badge-light-success',
  [OrderStatusEnum.CancelledByProvider]: 'badge-light-danger',
  [OrderStatusEnum.CancelledByClient]: 'badge-light-danger',
  [OrderStatusEnum.EndedByProvider]: 'badge-light-success',
  [OrderStatusEnum.EndedByClient]: 'badge-light-success',
};

export const offerStatusBadgeClass: Record<string, string> = {
  [OfferStatusEnum.Pending]: 'badge-light-primary',
  [OfferStatusEnum.Accepted]: 'badge-light-success',
  [OfferStatusEnum.Rejected]: 'badge-light-danger',
  [OfferStatusEnum.Cancelled]: 'badge-light-secondary',
  [OfferStatusEnum.Paid]: 'badge-light-success',
};

export function isOrderTerminalStatus(status: string | undefined | null): boolean {
  if (!status) {
    return false;
  }

  return ORDER_TERMINAL_STATUSES.includes(status);
}

/** "Order has been ended" alert — only for genuine terminal statuses, never InProgress. */
export function shouldShowOrderEndedAlert(status: string | undefined | null): boolean {
  return isOrderTerminalStatus(status);
}

export function canShowChatCta(
  orderStatus: string | undefined | null,
  orderProviderSocketId: string | number | undefined | null,
  authSocketId: string | number | undefined | null,
): boolean {
  return (
    orderStatus === OrderStatusEnum.OfferProvided
    && orderProviderSocketId != null
    && authSocketId != null
    && String(orderProviderSocketId) === String(authSocketId)
  );
}

export function canEndOrder(orderStatus: string | undefined | null): boolean {
  return orderStatus === OrderStatusEnum.InProgress;
}

export function canAddOffer(
  orderStatus: string | undefined | null,
  offers: Array<{ status?: { value?: string } }> | undefined | null,
): boolean {
  if (orderStatus !== OrderStatusEnum.New) {
    return false;
  }

  const hasPending = (offers ?? []).some(
    (offer) => offer.status?.value === OfferStatusEnum.Pending,
  );

  return !hasPending;
}

export function canEditOffer(
  offerStatus: string | undefined | null,
  orderStatus: string | undefined | null,
): boolean {
  const offerOk =
    offerStatus === OfferStatusEnum.Pending
    || offerStatus === OfferStatusEnum.Accepted;
  const orderOk =
    orderStatus === OrderStatusEnum.New
    || orderStatus === OrderStatusEnum.OfferProvided;

  return offerOk && orderOk;
}

/** Matches DeleteProviderOfferAction — Pending only. */
export function canDeleteOffer(
  offerStatus: string | undefined | null,
  orderStatus: string | undefined | null,
): boolean {
  const orderOk =
    orderStatus === OrderStatusEnum.New
    || orderStatus === OrderStatusEnum.OfferProvided;

  return offerStatus === OfferStatusEnum.Pending && orderOk;
}

export function getOrderStatusBadgeClass(status: string | undefined | null): string {
  if (!status) {
    return 'badge-light-secondary';
  }

  return orderStatusBadgeClass[status] ?? 'badge-light-secondary';
}

export function getOfferStatusBadgeClass(status: string | undefined | null): string {
  if (!status) {
    return 'badge-light-secondary';
  }

  return offerStatusBadgeClass[status] ?? 'badge-light-secondary';
}

export function truncateText(value: string | undefined | null, maxLength = 80): string {
  if (!value) {
    return '';
  }

  if (value.length <= maxLength) {
    return value;
  }

  return `${value.slice(0, maxLength).trimEnd()}…`;
}

/** Provider review form visibility — keep EndedByClient-only until product expands it. */
export function shouldShowProviderReviewForm(orderStatus: string | undefined | null): boolean {
  return orderStatus === OrderStatusEnum.EndedByClient;
}

export const ORDER_SHOW_REVIEW_SUBMIT_KEY = 'save_review';
export const ORDER_SHOW_END_ORDER_KEY = 'end_order';
export const ORDER_SHOW_EMPTY_OFFERS_TITLE_KEY = 'no_offers_submitted_yet';
export const ORDER_SHOW_EMPTY_OFFERS_HINT_KEY = 'no_offers_submitted_yet_hint';
export const ORDER_SHOW_ADD_OFFER_KEY = 'add_offer';
