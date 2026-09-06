import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  STACKED_DATA_TABLE_DESKTOP_ROW_CLASS,
  STACKED_DATA_TABLE_HEADER_CLASS,
  STACKED_DATA_TABLE_MOBILE_ROW_CLASS,
} from './types';

const dir = __dirname;
const src = readFileSync(join(dir, 'stacked-data-table.tsx'), 'utf8');

describe('StackedDataTable', () => {
  it('uses SectionCard with the HomeOrdersTable stacked visual shell', () => {
    expect(src).toContain('SectionCard');
    expect(src).toContain('STACKED_DATA_TABLE_HEADER_CLASS');
    expect(src).toContain('STACKED_DATA_TABLE_DESKTOP_ROW_CLASS');
    expect(src).toContain('STACKED_DATA_TABLE_MOBILE_ROW_CLASS');
  });

  it('hides the column-header shell on mobile to avoid an empty gray strip', () => {
    expect(STACKED_DATA_TABLE_HEADER_CLASS).toContain('d-none d-md-flex');
    expect(STACKED_DATA_TABLE_MOBILE_ROW_CLASS).toContain('d-md-none');
    expect(STACKED_DATA_TABLE_DESKTOP_ROW_CLASS).toContain('d-none d-md-flex');
  });

  it('supports mobile title / badge / meta column roles and optional row link', () => {
    expect(src).toContain("column.mobile === 'title'");
    expect(src).toContain("column.mobile === 'badge'");
    expect(src).toContain("column.mobile === 'meta'");
    expect(src).toContain('mobileHref');
    expect(src).toContain('mobileTrailing');
  });

  it('renders dashed separators between rows', () => {
    expect(src).toContain('STACKED_DATA_TABLE_SEPARATOR_CLASS');
  });
});
