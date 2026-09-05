import { useMemo } from 'react';
import { OrderStatusEnum } from '@/Enums/Order';

export type OrderTabKey =
  | 'pending'
  | 'approved'
  | 'in_progress'
  | 'ended_by_provider';

export type OrderTabCounts = Record<OrderTabKey, number>;

/** Backend `orderTabCounts` keys match OrderStatusEnum values. */
export type BackendOrderTabCounts = Partial<
  Record<(typeof OrderStatusEnum)[keyof typeof OrderStatusEnum], number>
>;

const EMPTY_COUNTS: OrderTabCounts = {
  pending: 0,
  approved: 0,
  in_progress: 0,
  ended_by_provider: 0,
};

/**
 * Maps live backend status counts onto Home tab keys.
 * Do not derive counts from the windowed order arrays (capped at 3 per status).
 */
export function mapBackendOrderTabCounts(
  backendCounts?: BackendOrderTabCounts | null,
): OrderTabCounts {
  if (!backendCounts) {
    return EMPTY_COUNTS;
  }

  return {
    pending: backendCounts[OrderStatusEnum.New] ?? 0,
    approved: backendCounts[OrderStatusEnum.OfferProvided] ?? 0,
    in_progress: backendCounts[OrderStatusEnum.InProgress] ?? 0,
    ended_by_provider: backendCounts[OrderStatusEnum.EndedByProvider] ?? 0,
  };
}

export function useOrderTabCounts(
  backendCounts?: BackendOrderTabCounts | null,
): OrderTabCounts {
  return useMemo(
    () => mapBackendOrderTabCounts(backendCounts),
    [backendCounts],
  );
}
