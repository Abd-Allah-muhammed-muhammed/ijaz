import { describe, expect, it } from 'vitest';
import { OrderStatusEnum } from '@/Enums/Order';
import { mapBackendOrderTabCounts } from './use-order-tab-counts';

describe('mapBackendOrderTabCounts', () => {
  it('returns zeros when backend counts are missing', () => {
    expect(mapBackendOrderTabCounts(undefined)).toEqual({
      pending: 0,
      approved: 0,
      in_progress: 0,
      ended_by_provider: 0,
    });
  });

  it('maps OrderStatusEnum values onto Home tab keys', () => {
    expect(
      mapBackendOrderTabCounts({
        [OrderStatusEnum.New]: 4,
        [OrderStatusEnum.OfferProvided]: 2,
        [OrderStatusEnum.InProgress]: 7,
        [OrderStatusEnum.EndedByProvider]: 1,
      }),
    ).toEqual({
      pending: 4,
      approved: 2,
      in_progress: 7,
      ended_by_provider: 1,
    });
  });

  it('defaults missing status buckets to zero', () => {
    expect(
      mapBackendOrderTabCounts({
        [OrderStatusEnum.New]: 3,
      }),
    ).toEqual({
      pending: 3,
      approved: 0,
      in_progress: 0,
      ended_by_provider: 0,
    });
  });
});
