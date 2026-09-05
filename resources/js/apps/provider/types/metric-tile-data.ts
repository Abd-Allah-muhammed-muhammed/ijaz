/**
 * Shared metric tile payload used by Home and AccountLayout.
 * Both surfaces render these via the shared `StatTile` component.
 */
export type MetricTileData = {
  label: string;
  value: string | number | null | undefined;
};
