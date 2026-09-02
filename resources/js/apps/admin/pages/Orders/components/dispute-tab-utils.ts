import { OrderStatusEnum } from '@/Enums/Orders';

export const ADMIN_CANCEL_DURING_DISPUTE_REASON = 'dispute_closed_by_admin_cancel';

export type HistoryReason = {
  value: string;
  label: string;
};

export type DisputeHistoryItem = {
  to_status?: { value?: string };
  reason?: HistoryReason | null;
};

const DISPUTE_RESOLUTION_STATUSES = new Set<string>([
  OrderStatusEnum.EndedViaDispute,
  OrderStatusEnum.CancelledViaDispute,
  OrderStatusEnum.Escalated,
  OrderStatusEnum.Settled,
]);

export function isDisputeResolutionHistory(history: DisputeHistoryItem): boolean {
  const status = history.to_status?.value;

  if (status && DISPUTE_RESOLUTION_STATUSES.has(status)) {
    return true;
  }

  return status === OrderStatusEnum.Cancelled
    && history.reason?.value === ADMIN_CANCEL_DURING_DISPUTE_REASON;
}
