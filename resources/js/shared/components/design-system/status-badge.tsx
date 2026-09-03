import {
  STATUS_BADGE_BASE_CLASS,
  STATUS_BADGE_FALLBACK_COLOR_CLASS,
  STATUS_BADGE_LIGHT_PREFIX,
  type StatusBadgeProps,
} from './types';

function resolveColorClass({
  colorClass,
  color,
  status,
}: Pick<StatusBadgeProps, 'colorClass' | 'color' | 'status'>): string {
  if (colorClass) {
    return colorClass;
  }

  const token = color ?? status?.color;
  if (token) {
    return token.startsWith(STATUS_BADGE_LIGHT_PREFIX)
      ? token
      : `${STATUS_BADGE_LIGHT_PREFIX}${token}`;
  }

  return STATUS_BADGE_FALLBACK_COLOR_CLASS;
}

export default function StatusBadge({
  status,
  label,
  color,
  colorClass,
  className,
}: StatusBadgeProps) {
  const resolvedLabel = label ?? status?.label ?? '';
  const resolvedColorClass = resolveColorClass({ colorClass, color, status });
  const classes = [STATUS_BADGE_BASE_CLASS, resolvedColorClass, className]
    .filter(Boolean)
    .join(' ');

  return <span className={classes}>{resolvedLabel}</span>;
}
