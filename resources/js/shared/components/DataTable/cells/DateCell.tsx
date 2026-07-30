import { formatDate } from '@/shared/lib/formatters';
import { cn } from '@/shared/lib/utils';

type DateCellProps = {
  value: string | Date | null | undefined;
  locale?: string;
  options?: Intl.DateTimeFormatOptions;
  className?: string;
};

/** Date-only cell via shared `formatDate`. */
export function DateCell({ value, locale, options, className }: DateCellProps) {
  const formatted = formatDate(value, locale, options);

  if (!formatted) {
    return <span className={cn('text-muted-foreground', className)}>—</span>;
  }

  return (
    <span className={cn('tabular-nums text-foreground', className)}>{formatted}</span>
  );
}
