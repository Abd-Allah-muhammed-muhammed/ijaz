import {
  EMPTY_VALUE_FALLBACK,
  STAT_TILE_LABEL_CLASS,
  STAT_TILE_SHELL_CLASS,
  STAT_TILE_VALUE_CLASS,
  type StatTileProps,
} from './types';

export default function StatTile({
  label,
  value,
  icon,
  className,
}: StatTileProps) {
  const shellClass = [STAT_TILE_SHELL_CLASS, className].filter(Boolean).join(' ');
  const displayValue =
    value === null || value === undefined || value === ''
      ? EMPTY_VALUE_FALLBACK
      : value;

  return (
    <div className={shellClass}>
      {icon ? <div className="mb-2">{icon}</div> : null}
      <div className={STAT_TILE_LABEL_CLASS}>{label}</div>
      <div className={STAT_TILE_VALUE_CLASS}>{displayValue}</div>
    </div>
  );
}
