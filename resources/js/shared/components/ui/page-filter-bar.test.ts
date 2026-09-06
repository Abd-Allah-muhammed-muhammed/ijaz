import { describe, expect, it, vi } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  clearSearchFilterValue,
  shouldShowSearchClearButton,
} from './page-filter-bar-utils';

describe('page-filter-bar-utils search clear', () => {
  it('hides the clear button when the input is empty and shows it after typing', () => {
    expect(shouldShowSearchClearButton('')).toBe(false);
    expect(shouldShowSearchClearButton('a')).toBe(true);
    expect(shouldShowSearchClearButton('villa')).toBe(true);
  });

  it('clicking clear empties the value and calls onFilterChange with empty string immediately (not Enter)', () => {
    const onFilterChange = vi.fn();
    const nextValue = clearSearchFilterValue('search', onFilterChange);

    expect(nextValue).toBe('');
    expect(onFilterChange).toHaveBeenCalledTimes(1);
    expect(onFilterChange).toHaveBeenCalledWith('search', '');
  });
});

describe('PageFilterBar search clear + date labels wiring', () => {
  const src = readFileSync(join(__dirname, 'page-filter-bar.tsx'), 'utf8');
  const indexSrc = readFileSync(
    join(__dirname, '../../../apps/provider/pages/Orders/Index.tsx'),
    'utf8',
  );
  const recommendedSrc = readFileSync(
    join(__dirname, '../../../apps/provider/pages/Orders/Recommended.tsx'),
    'utf8',
  );
  const offersSrc = readFileSync(
    join(__dirname, '../../../apps/provider/pages/Orders/Offers.tsx'),
    'utf8',
  );

  it('renders a Keenicons cross clear button that applies on click, bypassing Enter-only search', () => {
    expect(src).toContain('shouldShowSearchClearButton');
    expect(src).toContain('clearSearchFilterValue');
    expect(src).toContain('iconName="cross"');
    expect(src).toContain('data-kt-search-element="clear"');
    expect(src).toContain('setValue(clearSearchFilterValue(field.name, onFilterChange))');
    expect(src).toContain("if (event.key === 'Enter')");
  });

  it('renders optional date field labels above type=date inputs', () => {
    expect(src).toContain('PAGE_FILTER_DATE_LABEL_CLASS');
    expect(src).toContain('field.label');
    expect(src).toContain('htmlFor={`page-filter-${field.name}`}');
  });

  it('Orders Index labels date_from/date_to via existing from/to i18n keys; Recommended/Offers have no date fields', () => {
    expect(indexSrc).toContain("label: t('from')");
    expect(indexSrc).toContain("label: t('to')");
    expect(indexSrc).not.toContain("placeholder: 'Date From'");
    expect(recommendedSrc).not.toContain("type: 'date'");
    expect(offersSrc).not.toContain("type: 'date'");
  });

  it('bottom-aligns the filter row so unlabeled search/select share the date inputs baseline', () => {
    const typesSrc = readFileSync(join(__dirname, 'types.ts'), 'utf8');
    expect(typesSrc).toContain('align-items-lg-end');
    expect(typesSrc).toContain('align-items-sm-end');
    expect(typesSrc).not.toContain('align-items-lg-center');
  });
});
