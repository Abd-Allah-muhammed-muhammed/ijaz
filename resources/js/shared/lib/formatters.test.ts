import { describe, expect, it } from 'vitest';
import {
  STATEMENT_PAGE_SIZE,
  SHORT_REFERENCE_LENGTH,
  formatListDate,
  formatShortReference,
  LIST_DATE_FORMAT,
} from './formatters';

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

describe('formatShortReference', () => {
  it('uppercases the last 8 characters to match WalletTransactionDisplay::operationReference', () => {
    expect(SHORT_REFERENCE_LENGTH).toBe(8);
    expect(formatShortReference('01a04be2-af9e-711c-b309-b0d02e1e6792')).toBe(
      '2E1E6792',
    );
    expect(formatShortReference('abcdefgh')).toBe('ABCDEFGH');
    expect(formatShortReference('short')).toBe('SHORT');
  });

  it('returns an empty string for empty values', () => {
    expect(formatShortReference(null)).toBe('');
    expect(formatShortReference(undefined)).toBe('');
    expect(formatShortReference('')).toBe('');
  });
});

describe('STATEMENT_PAGE_SIZE', () => {
  it('keeps statement tables at a scannable default page size', () => {
    expect(STATEMENT_PAGE_SIZE).toBe(10);
  });
});
