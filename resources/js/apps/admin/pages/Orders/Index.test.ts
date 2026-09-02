import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

describe('Orders admin Index discoverability wiring', () => {
  it('status dropdown is populated from server-provided selects.statuses (translated labels), matching the Guarantor Index pattern, not a stale client-side enum import', () => {
    const ordersSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8');
    const guarantorSrc = readFileSync(join(__dirname, '../Guarantor/Index.tsx'), 'utf8');

    expect(ordersSrc).toContain('selects: { statuses: StatusOption[] }');
    expect(ordersSrc).toContain('selects.statuses.map');
    expect(ordersSrc).toContain('{status.label}');
    expect(ordersSrc).not.toContain('@/Enums/Order');
    expect(ordersSrc).not.toContain('Object.values(OrderStatusEnum)');

    expect(guarantorSrc).toContain('selects.statuses.map');
    expect(guarantorSrc).toContain('{status.label}');
  });

  it('search input is debounced', () => {
    const ordersSrc = readFileSync(join(__dirname, 'Index.tsx'), 'utf8');
    const guarantorSrc = readFileSync(join(__dirname, '../Guarantor/Index.tsx'), 'utf8');

    expect(ordersSrc).toContain('debounceRef');
    expect(ordersSrc).toContain('setTimeout');
    expect(ordersSrc).toContain(', 400)');
    expect(ordersSrc).toContain('value={searchValue}');
    expect(ordersSrc).toContain("onChange={(e) => setSearchValue(e.target.value)}");

    expect(guarantorSrc).toContain(', 400)');
  });
});
