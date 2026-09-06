import { describe, expect, it } from 'vitest';
import { formatListDate, LIST_DATE_FORMAT } from './formatters';

describe('formatListDate', () => {
  it('formats a compact date-only list label without a time component', () => {
    const label = formatListDate('2026-08-15T20:53:41Z', 'en-GB');

    expect(label).toMatch(/15/);
    expect(label).toMatch(/Aug/i);
    expect(label).toMatch(/2026/);
    expect(label).not.toMatch(/\d{1,2}:\d{2}/);
    expect(label).not.toMatch(/AM|PM/i);
  });

  it('returns an empty string for empty values', () => {
    expect(formatListDate(null)).toBe('');
    expect(formatListDate(undefined)).toBe('');
    expect(formatListDate('')).toBe('');
  });

  it('uses the shared LIST_DATE_FORMAT options', () => {
    expect(LIST_DATE_FORMAT).toEqual({
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  });
});
