import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

describe('OrderTabsSection', () => {
  const src = readFileSync(join(__dirname, 'OrderTabsSection.tsx'), 'utf8');

  it('reuses shared OrderCard instead of a one-off Home order row', () => {
    expect(src).toContain("from '@/shared/components/order/order-card'");
    expect(src).toContain('<OrderCard');
    expect(src).not.toContain('HomeOrderRow');
  });

  it('shows EmptyState with the Orders Index basket pattern when a tab has no orders', () => {
    expect(src).toContain('EmptyState');
    expect(src).toContain('no_orders_found');
    expect(src).toContain('iconName="basket"');
    expect(src).toContain('tab.orders.length === 0');
  });
});
