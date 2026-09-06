import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

describe('OrderTabsSection', () => {
  const src = readFileSync(join(__dirname, 'OrderTabsSection.tsx'), 'utf8');

  it('renders orders via the stacked HomeOrdersTable, not OrderCard grid', () => {
    expect(src).toContain('HomeOrdersTable');
    expect(src).not.toContain('OrderCard');
    expect(src).not.toContain('order-card');
  });

  it('labels the pending tab as offers awaiting client approval', () => {
    expect(src).toContain('offers_awaiting_client_approval');
  });

  it('shows EmptyState with the Orders Index basket pattern when a tab has no orders', () => {
    expect(src).toContain('EmptyState');
    expect(src).toContain('no_orders_found');
    expect(src).toContain('iconName="basket"');
    expect(src).toContain('tab.orders.length === 0');
  });

  it('scrolls order tabs horizontally on mobile with a peek/fade affordance', () => {
    expect(src).toContain('flex-nowrap');
    expect(src).toContain('overflow-auto');
    expect(src).toContain('home-order-tabs-scroll');
    expect(src).toContain('home-order-tabs-fade');
    expect(src).toContain('pe-10 pe-md-0');
    expect(src).toContain('d-md-none');
  });
});

describe('HomeOrdersTable', () => {
  const src = readFileSync(join(__dirname, 'HomeOrdersTable.tsx'), 'utf8');

  it('links View Details to the individual order Show page', () => {
    expect(src).toContain('OrderController.show');
    expect(src).toContain('view_details');
    expect(src).toContain('arrow-right');
  });

  it('resolves status via resolveHomeOrderRowStatus (offer status on pending tab)', () => {
    expect(src).toContain('resolveHomeOrderRowStatus');
    expect(src).toContain('StatusBadge');
  });

  it('hides the column-header shell on mobile to avoid an empty gray strip', () => {
    expect(src).toContain('d-none d-md-flex');
    expect(src).toContain('headerClassName');
    expect(src).toMatch(/headerClassName="[^"]*d-none d-md-flex/);
  });

  it('uses a compact mobile row without repeated field labels', () => {
    expect(src).toContain('d-md-none d-flex');
    expect(src).not.toMatch(/d-md-none text-muted fs-8 text-uppercase/);
    expect(src).toContain('d-none d-md-flex align-items-center gap-3');
  });

  it('shows a consistent your-price column across tabs via resolveHomeOrderYourPrice', () => {
    expect(src).toContain('resolveHomeOrderYourPrice');
    expect(src).toContain('formatHomeOrderYourPrice');
    expect(src).toContain('your_offer');
    expect(src).toContain('agreed_price');
    expect(src).toContain("tabKey === 'pending'");
  });
});
