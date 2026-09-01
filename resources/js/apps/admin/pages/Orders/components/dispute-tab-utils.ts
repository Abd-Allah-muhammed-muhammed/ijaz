export const OrderStatusEnum = {
  Disputed: 'disputed',
  EndedViaDispute: 'ended_via_dispute',
  CancelledViaDispute: 'cancelled_via_dispute',
  Escalated: 'escalated',
  Settled: 'settled',
} as const;

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

  return status !== undefined && DISPUTE_RESOLUTION_STATUSES.has(status);
}
