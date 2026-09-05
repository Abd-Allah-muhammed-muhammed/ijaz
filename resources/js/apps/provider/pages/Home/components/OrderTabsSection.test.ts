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
});
