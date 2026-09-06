/**
 * Shared display formatters (Intl only — no date library in this project).
 *
 * Consolidates patterns used across Dashboard/Provider pages:
 * - `Number(x).toLocaleString()` + `t('SAR')` / `t('currency')`
 * - `new Date(x).toLocaleString()` / `toLocaleDateString()`
 * - legacy `build_date` → `date : time`
 *
 * New UI should import from here. Existing call sites may migrate later.
 */

export type FormatNumberOptions = Intl.NumberFormatOptions;

export function formatNumber(
  value: number | string | null | undefined,
  locale?: string,
  options?: FormatNumberOptions,
): string {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  const amount = typeof value === 'number' ? value : Number(value);

  if (Number.isNaN(amount)) {
    return '';
  }

  return amount.toLocaleString(locale, options);
}

export type FormatCurrencyOptions = {
  locale?: string;
  /**
   * Label appended after the amount (app convention: `"SAR"`, `"ر.س"`, `t('currency')`).
   * Pass an empty string for a bare formatted number.
   */
  currencyLabel?: string;
  /** Fraction digits; dashboard prices are usually whole riyals (0). */
  maximumFractionDigits?: number;
  minimumFractionDigits?: number;
};

/**
 * SAR-style amounts matching screens: `1,850 SAR` / `1,850 ر.س`.
 * Uses `toLocaleString` + label (not Intl `style: 'currency'`) so the UI keeps
 * the same "number + translated label" look already used with `t('SAR')`.
 */
export function formatCurrency(
  value: number | string | null | undefined,
  options: FormatCurrencyOptions = {},
): string {
  const {
    locale,
    currencyLabel = 'SAR',
    maximumFractionDigits = 0,
    minimumFractionDigits = 0,
  } = options;

  const formatted = formatNumber(value, locale, {
    maximumFractionDigits,
    minimumFractionDigits,
  });

  if (formatted === '') {
    return '';
  }

  if (currencyLabel === '') {
    return formatted;
  }

  return `${formatted} ${currencyLabel}`;
}

/** Parse API/wallet amounts that may include thousands separators. */
export function parseAmount(value: number | string | null | undefined): number {
  if (value === null || value === undefined || value === '') {
    return 0;
  }

  if (typeof value === 'number') {
    return Number.isNaN(value) ? 0 : value;
  }

  const normalized = value.replace(/,/g, '').trim();
  const amount = Number(normalized);

  return Number.isNaN(amount) ? 0 : amount;
}

export function formatDate(
  value: string | Date | null | undefined,
  locale?: string,
  options?: Intl.DateTimeFormatOptions,
): string {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  return new Date(value).toLocaleDateString(locale, options);
}

/**
 * Compact date-only label for stacked list rows (Wallet / Withdraw Index).
 * Example (en-GB): `15 Aug 2026`. Detail views keep `build_date` (date + time).
 */
export const LIST_DATE_FORMAT: Intl.DateTimeFormatOptions = {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
};

export function formatListDate(
  value: string | Date | null | undefined,
  locale?: string,
): string {
  return formatDate(value, locale, LIST_DATE_FORMAT);
}

/**
 * Matches PHP `WalletTransactionDisplay::operationReference` —
 * last 8 characters of an operation / withdraw id, uppercased.
 */
export const SHORT_REFERENCE_LENGTH = 8;

export function formatShortReference(
  value: string | number | null | undefined,
): string {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  return String(value).slice(-SHORT_REFERENCE_LENGTH).toUpperCase();
}

/** Default page size for provider Wallet / Withdraw statement tables. */
export const STATEMENT_PAGE_SIZE = 10;

export function formatDateTime(
  value: string | Date | null | undefined,
  locale?: string,
  options?: Intl.DateTimeFormatOptions,
): string {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  return new Date(value).toLocaleString(locale, options);
}

/**
 * Legacy `build_date` shape: `"M/D/YYYY : H:MM:SS AM/PM"` via browser locale.
 * Prefer `formatDateTime` for new UI.
 */
export function build_date(date: string | Date): string {
  const d = new Date(date);
  return `${d.toLocaleDateString()} : ${d.toLocaleTimeString()}`;
}
