import { Badge, type BadgeProps } from '@/shared/components/ui/badge';
import { cn } from '@/shared/lib/utils';

/**
 * Maps Bootstrap/Metronic status color keys onto semantic design tokens
 * (--primary / --success / --warning / --info / --destructive).
 * Soft fills use token opacity so badges respond to light/dark + data-app primary.
 */
const colorToBadge: Record<
  string,
  { variant: NonNullable<BadgeProps['variant']>; className?: string }
> = {
  primary: { variant: 'default' },
  success: {
    variant: 'outline',
    className: 'border-transparent bg-success/15 text-success',
  },
  danger: { variant: 'destructive' },
  warning: {
    variant: 'outline',
    className: 'border-transparent bg-warning/20 text-warning-foreground',
  },
  info: {
    variant: 'outline',
    className: 'border-transparent bg-info/15 text-info',
  },
  secondary: { variant: 'secondary' },
};

type StatusBadgeCellProps = {
  label: string;
  /** Bootstrap-style color key from API (`success`, `danger`, …) or omit for default. */
  color?: string | null;
  variant?: BadgeProps['variant'];
  className?: string;
};

/**
 * Status / type badge cell — token-based (no hardcoded emerald/amber/sky palette).
 */
export function StatusBadgeCell({
  label,
  color,
  variant,
  className,
}: StatusBadgeCellProps) {
  const mapped = color ? colorToBadge[color] : undefined;

  return (
    <Badge
      variant={variant ?? mapped?.variant ?? 'secondary'}
      className={cn(mapped?.className, className)}
    >
      {label}
    </Badge>
  );
}
