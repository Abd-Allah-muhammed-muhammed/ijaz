import {
  formatCurrency,
  type FormatCurrencyOptions,
} from '@/shared/lib/formatters';
import { cn } from '@/shared/lib/utils';

type CurrencyCellProps = {
  value: number | string | null | undefined;
  /** Optional second bound for ranges (Orders budget_start–budget_end). */
  endValue?: number | string | null | undefined;
  options?: FormatCurrencyOptions;
  className?: string;
};

/**
 * Currency / budget cell using shared `formatCurrency` (SAR-style label).
 */
export function CurrencyCell({
  value,
  endValue,
  options,
  className,
}: CurrencyCellProps) {
  if (endValue !== undefined && endValue !== null && endValue !== '') {
    const start = formatCurrency(value, { ...options, currencyLabel: '' });
    const end = formatCurrency(endValue, { ...options, currencyLabel: '' });
    const label = options?.currencyLabel ?? 'SAR';

    if (!start && !end) {
      return <span className={cn('text-muted-foreground', className)}>—</span>;
    }

    return (
      <span className={cn('font-medium tabular-nums text-foreground', className)}>
        {start}
        {end ? ` – ${end}` : ''}
        {label ? ` ${label}` : ''}
      </span>
    );
  }

  const formatted = formatCurrency(value, options);

  if (!formatted) {
    return <span className={cn('text-muted-foreground', className)}>—</span>;
  }

  return (
    <span className={cn('font-medium tabular-nums text-foreground', className)}>
      {formatted}
    </span>
  );
}
