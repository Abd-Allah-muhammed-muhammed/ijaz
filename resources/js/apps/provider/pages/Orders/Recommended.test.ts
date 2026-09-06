import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

describe('Orders Recommended page', () => {
  const src = readFileSync(join(__dirname, 'Recommended.tsx'), 'utf8');

  it('titles the page New Orders to match the sidebar label', () => {
    expect(src).toContain("t('new_orders')");
    expect(src).toContain('<Head title={t(\'new_orders\')}/>');
    expect(src).not.toContain("t('providers')");
  });
});
