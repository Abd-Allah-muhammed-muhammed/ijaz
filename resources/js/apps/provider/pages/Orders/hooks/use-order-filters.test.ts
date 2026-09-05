import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
  commitOrderFilterChange,
  useOrderFilters,
} from './use-order-filters';

vi.mock('@/shared/lib/filters', () => ({
  applyFilterParam: vi.fn(
    <T extends Record<string, unknown>>(
      params: T,
      key: keyof T & string,
      value: string | number | undefined | null,
    ): T => {
      const next = { ...params };
      if (value === '' || value == null) {
        delete next[key];
      } else {
        next[key] = value as T[keyof T & string];
      }
      return next;
    },
  ),
  visitWithFilters: vi.fn(),
}));

import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';

type IndexFilters = {
  per_page: number;
  search: string;
  status?: string;
  date_from?: string;
  date_to?: string;
};

describe('commitOrderFilterChange', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('merges a select/date filter and visits with the updated query params', () => {
    const current: IndexFilters = {
      per_page: 10,
      search: '',
      status: 'new',
    };

    const next = commitOrderFilterChange(
      current,
      'date_from',
      '2026-01-01',
      '/provider/orders',
    );

    expect(applyFilterParam).toHaveBeenCalledWith(
      expect.objectContaining({ per_page: 10, search: '', status: 'new' }),
      'date_from',
      '2026-01-01',
    );
    expect(visitWithFilters).toHaveBeenCalledWith(
      '/provider/orders',
      expect.objectContaining({
        per_page: 10,
        search: '',
        status: 'new',
        date_from: '2026-01-01',
      }),
      { only: ['rows', 'prams'] },
    );
    expect(next.date_from).toBe('2026-01-01');
  });

  it('applies search the same way PageFilterBar does on Enter (name=search)', () => {
    const current: IndexFilters = { per_page: 10, search: '' };

    commitOrderFilterChange(current, 'search', 'plumbing', '/provider/orders');

    expect(applyFilterParam).toHaveBeenCalledWith(
      expect.objectContaining({ per_page: 10, search: '' }),
      'search',
      'plumbing',
    );
    expect(visitWithFilters).toHaveBeenCalledWith(
      '/provider/orders',
      expect.objectContaining({ search: 'plumbing' }),
      { only: ['rows', 'prams'] },
    );
  });

  it('removes a filter key when the value is cleared', () => {
    const current: IndexFilters = {
      per_page: 10,
      search: 'x',
      status: 'new',
    };

    const next = commitOrderFilterChange(
      current,
      'status',
      '',
      '/provider/orders',
    );

    expect(next.status).toBeUndefined();
    expect(visitWithFilters).toHaveBeenCalledWith(
      '/provider/orders',
      expect.not.objectContaining({ status: expect.anything() }),
      { only: ['rows', 'prams'] },
    );
  });

  it('forwards a custom only partial-reload list', () => {
    commitOrderFilterChange(
      { per_page: 10, search: '' },
      'search',
      'a',
      '/provider/orders/new',
      ['rows'],
    );

    expect(visitWithFilters).toHaveBeenCalledWith(
      '/provider/orders/new',
      expect.any(Object),
      { only: ['rows'] },
    );
  });
});

describe('useOrderFilters', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('falls back to defaults when prams is null and exposes onFilterChange', () => {
    const defaults: IndexFilters = { per_page: 10, search: '' };
    const { filters, onFilterChange } = useOrderFilters({
      prams: null,
      defaults,
      url: '/provider/orders',
    });

    expect(filters).toEqual(defaults);

    onFilterChange('status', 'in_progress');

    expect(visitWithFilters).toHaveBeenCalledWith(
      '/provider/orders',
      expect.objectContaining({ status: 'in_progress' }),
      { only: ['rows', 'prams'] },
    );
  });

  it('prefers server prams over defaults', () => {
    const { filters } = useOrderFilters({
      prams: { per_page: 25, search: 'ac', period: '90' },
      defaults: { per_page: 10, search: '', period: '30' },
      url: '/provider/orders/new',
    });

    expect(filters).toEqual({ per_page: 25, search: 'ac', period: '90' });
  });
});
