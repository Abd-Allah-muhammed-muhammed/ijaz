import { describe, expect, it } from 'vitest';
import { OfferStatusEnum, OrderStatusEnum } from '@/Enums/Order';
import type { Order, OrderOffer } from '@/shared/types/models';
import {
  getProviderOfferOnOrder,
  resolveHomeOrderRowStatus,
} from './home-order-row-status';

function makeOffer(
  overrides: Partial<OrderOffer> & Pick<OrderOffer, 'status'>,
): OrderOffer {
  return {
    id: 'o1',
    order_id: '1',
    provider_id: 23,
    ...overrides,
  } as OrderOffer;
}

function makeOrder(overrides: Partial<Order> = {}): Order {
  return {
    id: '1',
    title: 'Test',
    description: '',
    status: {
      value: OrderStatusEnum.New,
      label: 'New',
      color: 'primary',
    },
    budget_start: 10,
    budget_end: 20,
    expected_time: '1d',
    provider_id: 0,
    category_id: 1,
    region_id: 1,
    city_id: 1,
    city: {} as Order['city'],
    accepted_offer_id: 0,
    price: 0,
    created_at: new Date('2026-01-01'),
    user_id: 1,
    reviews: [],
    ...overrides,
  };
}

describe('resolveHomeOrderRowStatus', () => {
  it('uses the provider offer status on the pending tab', () => {
    const offerStatus = {
      value: OfferStatusEnum.Pending,
      label: 'Pending',
      color: 'primary',
    };
    const order = makeOrder({
      offers: [makeOffer({ status: offerStatus })],
    });

    expect(resolveHomeOrderRowStatus(order, 'pending')).toEqual(offerStatus);
  });

  it('falls back to order status on the pending tab when no offer is loaded', () => {
    const order = makeOrder({ offers: [] });
    expect(resolveHomeOrderRowStatus(order, 'pending')).toEqual(order.status);
  });

  it('keeps order status on non-pending tabs even when an offer exists', () => {
    const orderStatus = {
      value: OrderStatusEnum.InProgress,
      label: 'In Progress',
      color: 'info',
    };
    const order = makeOrder({
      status: orderStatus,
      offers: [
        makeOffer({
          status: {
            value: OfferStatusEnum.Accepted,
            label: 'Accepted',
            color: 'success',
          },
        }),
      ],
    });

    expect(resolveHomeOrderRowStatus(order, 'in_progress')).toEqual(orderStatus);
  });

  it('prefers a pending offer when multiple offers are present', () => {
    const order = makeOrder({
      offers: [
        makeOffer({
          id: 'o1',
          status: {
            value: OfferStatusEnum.Rejected,
            label: 'Rejected',
            color: 'danger',
          },
        }),
        makeOffer({
          id: 'o2',
          status: {
            value: OfferStatusEnum.Pending,
            label: 'Pending',
            color: 'primary',
          },
        }),
      ],
    });

    expect(getProviderOfferOnOrder(order)?.status?.value).toBe(
      OfferStatusEnum.Pending,
    );
  });
});
