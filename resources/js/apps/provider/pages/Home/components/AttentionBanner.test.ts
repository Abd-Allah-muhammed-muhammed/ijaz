import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { shouldRenderAttentionBanner } from './attention-banner-utils';

describe('AttentionBanner', () => {
  it('does not render when count is 0', () => {
    expect(shouldRenderAttentionBanner(0)).toBe(false);
  });

  it('renders when count is greater than 0', () => {
    expect(shouldRenderAttentionBanner(1)).toBe(true);
    expect(shouldRenderAttentionBanner(12)).toBe(true);
  });

  it('gates the component on shouldRenderAttentionBanner', () => {
    const src = readFileSync(join(__dirname, 'AttentionBanner.tsx'), 'utf8');
    expect(src).toContain('shouldRenderAttentionBanner(count)');
    expect(src).toContain('return null');
  });
});
