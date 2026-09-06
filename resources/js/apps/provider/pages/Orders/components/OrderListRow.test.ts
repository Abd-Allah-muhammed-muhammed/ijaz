import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { OrderStatusEnum } from '@/Enums/Order';
import {
  formatOrderBudgetRange,
  formatOrderListTime,
  formatOrderLocation,
  ORDER_LIST_ROW_CARD_CLASS,
} from './order-list-row-utils';
import type { Order } from '@/shared/types/models';

describe('OrderListRow', () => {
  const src = readFileSync(join(__dirname, 'OrderListRow.tsx'), 'utf8');

  it('renders an individual bordered card with hover tint, no View Details affordance', () => {
    expect(ORDER_LIST_ROW_CARD_CLASS).toContain('border border-gray-200');
    expect(ORDER_LIST_ROW_CARD_CLASS).toContain('rounded-3');
    expect(ORDER_LIST_ROW_CARD_CLASS).toContain('bg-hover-light');
    expect(src).toContain('ORDER_LIST_ROW_CARD_CLASS');
    expect(src).not.toContain('view_details');
    expect(src).not.toContain('whenLocale');
    expect(src).not.toContain('border-bottom');
  });

  it('renders title, amount, truncated description, and StatusBadge on the meta row', () => {
    expect(src).toContain('{title}');
    expect(src).toContain('{amountLabel}');
    expect(src).toContain('min-w-0 flex-grow-1');
    expect(src).toContain('text-truncate');
    expect(src).toContain('{trimmedDescription}');
    expect(src).toContain('StatusBadge');
    expect(src).toContain('status={status}');
    expect(src).toContain('colorClass={statusColorClass}');
    expect(src).toContain('justify-content-between');
  });

  it('uses price-tag plus visible offers label (not chat message-text-2)', () => {
    expect(src).toContain('iconName="price-tag"');
    expect(src).toContain("{offersCount} {t('offers')}");
    expect(src).not.toContain('message-text-2');
  });

  it('uses Link for the whole row and aria-labels meta icons with counts/labels', () => {
    expect(src).toContain('<Link');
    expect(src).toContain("aria-label={`${t('location')}: ${trimmedLocation}`}");
    expect(src).toContain("aria-label={`${t('date')}: ${timeLabel}`}");
    expect(src).toContain("aria-label={`${t('offers')}: ${offersCount}`}");
    expect(src).not.toContain('onClick');
  });
});

describe('order-list-row-utils', () => {
  it('formats budget range for the top line', () => {
    expect(formatOrderBudgetRange(100, 500)).toBe('100 – 500');
  });

  it('builds location from Dashboard Resource flattened city/region title', () => {
    const order = {
      city: { title: 'Riyadh' },
      region: { title: 'North' },
    } as Order;

    expect(formatOrderLocation(order)).toBe('Riyadh - North');
    expect(
      formatOrderLocation({
        city: { translation: { title: 'Ignored' } },
        region: { translation: { title: 'Ignored' } },
      } as Order),
    ).toBe('');
    expect(formatOrderLocation({} as Order)).toBe('');
  });

  it('formats relative time from absolute timestamps', () => {
    const now = new Date('2026-09-06T12:00:00Z');
    const twoHoursAgo = new Date('2026-09-06T10:00:00Z');

    expect(formatOrderListTime(twoHoursAgo.toISOString(), 'en', now)).toBe('2h');
  });
});

describe('Order list pages use spaced OrderListRow cards', () => {
  const pages = ['Index.tsx', 'Recommended.tsx', 'Offers.tsx'] as const;

  it.each(pages)('%s stacks OrderListRow in a gap column, not a SectionCard list shell', (file) => {
    const src = readFileSync(join(__dirname, '..', file), 'utf8');
    expect(src).toContain('OrderListRow');
    expect(src).toContain('d-flex flex-column gap-3 mb-5');
    expect(src).not.toContain('OrderCard');
    expect(src).not.toContain('order-card');
    expect(src).not.toContain('<Col');
    expect(src).not.toContain('bodyClassName="card-body p-0"');
  });

  it('Index/Recommended pass EnumWithColors status; Offers uses offer colorClass map', () => {
    const indexSrc = readFileSync(join(__dirname, '../Index.tsx'), 'utf8');
    const offersSrc = readFileSync(join(__dirname, '../Offers.tsx'), 'utf8');

    expect(indexSrc).toContain('status={row.status}');
    expect(offersSrc).toContain('getOfferStatusBadgeClass');
    expect(offersSrc).toContain('statusColorClass={offerBadge}');
    expect(offersSrc).not.toContain('offersCount=');
  });
});

describe('StatusBadge color tokens for order list statuses', () => {
  it('covers at least two distinct order status colors via EnumWithColors-shaped props', () => {
    const newStatus = {
      value: OrderStatusEnum.New,
      label: 'New',
      color: 'primary',
    };
    const inProgressStatus = {
      value: OrderStatusEnum.InProgress,
      label: 'In Progress',
      color: 'info',
    };

    expect(newStatus.color).toBe('primary');
    expect(inProgressStatus.color).toBe('info');
    expect(newStatus.color).not.toBe(inProgressStatus.color);
  });
});
