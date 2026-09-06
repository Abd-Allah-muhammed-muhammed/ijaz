import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { SECONDARY_BUTTON_CLASS } from '@/shared/components/ui/types';

const src = readFileSync(join(__dirname, 'action-cell.tsx'), 'utf8');

describe('ActionCell dark-mode trigger', () => {
  it('uses SECONDARY_BUTTON_CLASS instead of btn-light washout', () => {
    expect(src).toContain('SECONDARY_BUTTON_CLASS');
    expect(src).not.toMatch(/btn-light btn-active-light-primary/);
    expect(SECONDARY_BUTTON_CLASS).toContain('btn-bg-light');
    expect(SECONDARY_BUTTON_CLASS).toContain('btn-color-gray-900');
  });

  it('exposes an accessible actions label on the trigger button', () => {
    expect(src).toContain('aria-label');
    expect(src).toContain("t('actions')");
    expect(src).toContain('type="button"');
  });

  it('supports a compact icon-only mobile trigger', () => {
    expect(src).toContain('compact');
    expect(src).toContain('btn-icon');
    expect(src).toContain('dots-vertical');
  });
});
