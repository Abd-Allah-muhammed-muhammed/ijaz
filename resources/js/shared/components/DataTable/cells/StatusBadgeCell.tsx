import { Badge, type BadgeProps } from '@/shared/components/ui/badge';
import { cn } from '@/shared/lib/utils';

/**
 * Maps common Bootstrap/Metronic status color tokens used across Orders/Guarantor
 * onto shadcn Badge variants + optional tint classes.
 */
const colorToBadge: Record<
  string,
  { variant: NonNullable<BadgeProps['variant']>; className?: string }
> = {
  primary: { variant: 'default' },
  success: {
    variant: 'outline',
    className: 'border-transparent bg-emerald-100 text-emerald-800',
  },
  danger: { variant: 'destructive' },
  warning: {
    variant: 'outline',
    className: 'border-transparent bg-amber-100 text-amber-900',
  },
  info: {
    variant: 'outline',
    className: 'border-transparent bg-sky-100 text-sky-900',
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
 * Status / type badge cell — Guarantor status+type and Order status strip condensed.
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
