import { describe, expect, it } from 'vitest';
import { OrderStatusEnum } from '@/Enums/Orders';
import {
  ADMIN_CANCEL_DURING_DISPUTE_REASON,
  isDisputeResolutionHistory,
} from './dispute-tab-utils';

describe('isDisputeResolutionHistory', () => {
  it.each([
    OrderStatusEnum.EndedViaDispute,
    OrderStatusEnum.CancelledViaDispute,
    OrderStatusEnum.Escalated,
    OrderStatusEnum.Settled,
  ])('a history row with to_status %s is identified as a resolution row via status alone', (status) => {
    expect(isDisputeResolutionHistory({ to_status: { value: status }, reason: null })).toBe(true);
  });

  it('a history row with plain Cancelled status and reason.value === dispute_closed_by_admin_cancel is identified as a resolution row', () => {
    expect(isDisputeResolutionHistory({
      to_status: { value: OrderStatusEnum.Cancelled },
      reason: {
        value: ADMIN_CANCEL_DURING_DISPUTE_REASON,
        label: 'Closed by admin cancellation during dispute',
      },
    })).toBe(true);
  });

  it('a history row with plain Cancelled status and any other/no reason is NOT identified as a dispute resolution row', () => {
    expect(isDisputeResolutionHistory({
      to_status: { value: OrderStatusEnum.Cancelled },
      reason: {
        value: 'user requested cancel',
        label: 'user requested cancel',
      },
    })).toBe(false);

    expect(isDisputeResolutionHistory({
      to_status: { value: OrderStatusEnum.Cancelled },
      reason: null,
    })).toBe(false);
  });
});
