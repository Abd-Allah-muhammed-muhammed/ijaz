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
