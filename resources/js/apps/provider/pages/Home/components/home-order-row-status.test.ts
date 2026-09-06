import { describe, expect, it } from 'vitest';
import { OfferStatusEnum, OrderStatusEnum } from '@/Enums/Order';
import type { Order, OrderOffer } from '@/shared/types/models';
import { EMPTY_VALUE_FALLBACK } from '@/shared/components/ui/types';
import {
  formatHomeOrderYourPrice,
  getProviderOfferOnOrder,
  HOME_ORDER_YOUR_PRICE_LABEL_KEY,
  homeOrderYourPriceLabelKey,
  resolveHomeOrderRowStatus,
  resolveHomeOrderYourPrice,
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

describe('resolveHomeOrderYourPrice', () => {
  it('uses the provider offer price on the pending tab', () => {
    const order = makeOrder({
      price: 0,
      offers: [
        makeOffer({
          price: 175,
          status: {
            value: OfferStatusEnum.Pending,
            label: 'Pending',
            color: 'primary',
          },
        }),
      ],
    });

    expect(resolveHomeOrderYourPrice(order, 'pending')).toBe(175);
  });

  it('prefers order.price on accepted-stage tabs', () => {
    const order = makeOrder({
      price: 320,
      offers: [
        makeOffer({
          price: 300,
          status: {
            value: OfferStatusEnum.Accepted,
            label: 'Accepted',
            color: 'success',
          },
        }),
      ],
    });

    expect(resolveHomeOrderYourPrice(order, 'approved')).toBe(320);
    expect(resolveHomeOrderYourPrice(order, 'in_progress')).toBe(320);
    expect(resolveHomeOrderYourPrice(order, 'ended_by_provider')).toBe(320);
  });

  it('falls back to the accepted offer price when order.price is unset', () => {
    const order = makeOrder({
      price: 0,
      offers: [
        makeOffer({
          price: 410,
          status: {
            value: OfferStatusEnum.Accepted,
            label: 'Accepted',
            color: 'success',
          },
        }),
      ],
    });

    expect(resolveHomeOrderYourPrice(order, 'approved')).toBe(410);
  });

  it('returns null when no offer amount is available', () => {
    const order = makeOrder({ price: 0, offers: [] });
    expect(resolveHomeOrderYourPrice(order, 'pending')).toBeNull();
    expect(resolveHomeOrderYourPrice(order, 'in_progress')).toBeNull();
  });
});

describe('homeOrderYourPriceLabelKey', () => {
  it('labels pending as your_offer and accepted-stage tabs as agreed_price', () => {
    expect(homeOrderYourPriceLabelKey('pending')).toBe(
      HOME_ORDER_YOUR_PRICE_LABEL_KEY.pending,
    );
    expect(homeOrderYourPriceLabelKey('approved')).toBe('agreed_price');
    expect(homeOrderYourPriceLabelKey('in_progress')).toBe('agreed_price');
    expect(homeOrderYourPriceLabelKey('ended_by_provider')).toBe('agreed_price');
  });
});

describe('formatHomeOrderYourPrice', () => {
  it('uses the shared empty-value fallback when price is missing', () => {
    expect(formatHomeOrderYourPrice(null, (value) => `${value}`)).toBe(
      EMPTY_VALUE_FALLBACK,
    );
  });

  it('formats present prices via the provided formatter', () => {
    expect(formatHomeOrderYourPrice(99, (value) => `${value} SAR`)).toBe(
      '99 SAR',
    );
  });
});
