import { describe, expect, it } from 'vitest';
import { GuarantorStatusEnum } from '@/Enums/Guarantor';
import {
  ADMIN_CANCEL_DURING_DISPUTE_REASON,
  isDisputeResolutionHistory,
} from './dispute-tab-utils';

describe('isDisputeResolutionHistory', () => {
  it.each([
    GuarantorStatusEnum.EndedViaDispute,
    GuarantorStatusEnum.CancelledViaDispute,
    GuarantorStatusEnum.Escalated,
    GuarantorStatusEnum.Settled,
  ])('a history row with to_status %s is identified as a resolution row via status alone, no reason string matching', (status) => {
    expect(isDisputeResolutionHistory({ to_status: { value: status }, reason: null })).toBe(true);
  });

  it('a history row with plain Cancelled status and reason.value === dispute_closed_by_admin_cancel is identified as a resolution row (the one exception)', () => {
    expect(isDisputeResolutionHistory({
      to_status: { value: GuarantorStatusEnum.Cancelled },
      reason: {
        value: ADMIN_CANCEL_DURING_DISPUTE_REASON,
        label: 'Closed by admin cancellation during dispute',
      },
    })).toBe(true);
  });

  it('a history row with plain Cancelled status and any other/no reason is NOT identified as a dispute resolution row', () => {
    expect(isDisputeResolutionHistory({
      to_status: { value: GuarantorStatusEnum.Cancelled },
      reason: {
        value: 'user requested cancel',
        label: 'user requested cancel',
      },
    })).toBe(false);

    expect(isDisputeResolutionHistory({
      to_status: { value: GuarantorStatusEnum.Cancelled },
      reason: null,
    })).toBe(false);
  });
});
