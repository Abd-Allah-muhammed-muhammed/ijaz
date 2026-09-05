import { describe, expect, it } from 'vitest';
import { OfferStatusEnum, OrderStatusEnum } from '@/Enums/Order';
import {
  getNeedsAttentionCount,
  NEEDS_ATTENTION_OFFERS_STATUS,
  NEEDS_ATTENTION_ORDER_STATUS,
  NEEDS_ATTENTION_TAB,
  useNeedsAttentionCount,
} from './use-needs-attention-count';
import { mapBackendOrderTabCounts } from './use-order-tab-counts';

describe('needs attention count', () => {
  it('uses the pending / New status bucket for the Home tab count', () => {
    expect(NEEDS_ATTENTION_TAB).toBe('pending');
    expect(NEEDS_ATTENTION_ORDER_STATUS).toBe(OrderStatusEnum.New);
  });

  it('points the review CTA at My Offers filtered by pending offer status', () => {
    expect(NEEDS_ATTENTION_OFFERS_STATUS).toBe(OfferStatusEnum.Pending);
  });

  it('reads the pending tab count from mapped tab counts', () => {
    const counts = mapBackendOrderTabCounts({
      [OrderStatusEnum.New]: 5,
      [OrderStatusEnum.OfferProvided]: 2,
    });

    expect(getNeedsAttentionCount(counts)).toBe(5);
  });

  it('returns zero when the pending bucket is empty', () => {
    expect(
      getNeedsAttentionCount({
        pending: 0,
        approved: 3,
        in_progress: 1,
        ended_by_provider: 0,
      }),
    ).toBe(0);
  });

  it('accepts backend counts via the convenience wrapper', () => {
    expect(
      useNeedsAttentionCount({
        [OrderStatusEnum.New]: 9,
        [OrderStatusEnum.InProgress]: 1,
      }),
    ).toBe(9);
  });
});
