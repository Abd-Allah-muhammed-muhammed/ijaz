/** Presentational gate for AttentionBanner — pure so Vitest can assert without DOM. */
export function shouldRenderAttentionBanner(count: number): boolean {
  return count > 0;
}
