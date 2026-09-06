import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

describe('OrderCard', () => {
  const src = readFileSync(join(__dirname, 'order-card.tsx'), 'utf8');

  it('renders shared StatusBadge from order.status, not react-bootstrap Badge', () => {
    expect(src).toContain("import { StatusBadge } from '@/shared/components/ui'");
    expect(src).toContain('<StatusBadge status={order.status} />');
    expect(src).toContain("import { Card, OverlayTrigger, Tooltip } from 'react-bootstrap'");
    expect(src).not.toContain('<Badge');
    expect(src).not.toContain('bg={`light-');
  });

  it('keeps status.color usages for the border strip and avatar fallback', () => {
    expect(src).toContain('border-${order.status.color}');
    expect(src).toContain('bg-light-${order.status.color}');
    expect(src).toContain('text-${order.status.color}');
  });
});
