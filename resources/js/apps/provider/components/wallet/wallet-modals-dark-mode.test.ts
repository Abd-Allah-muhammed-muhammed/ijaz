import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { SECONDARY_BUTTON_CLASS } from '@/shared/components/ui/types';

const dir = __dirname;

describe('wallet modals dark-mode Close buttons', () => {
  it('SECONDARY_BUTTON_CLASS stays the dark-safe Metronic secondary surface', () => {
    expect(SECONDARY_BUTTON_CLASS).toContain('btn-bg-light');
    expect(SECONDARY_BUTTON_CLASS).toContain('btn-color-gray-900');
    expect(SECONDARY_BUTTON_CLASS).not.toMatch(/(?:^|\s)btn-light(?:\s|$)/);
  });

  it.each(['WithdrawModal.tsx', 'RechargeModal.tsx'] as const)(
    '%s Close uses SECONDARY_BUTTON_CLASS instead of variant="light"',
    (filename) => {
      const src = readFileSync(join(dir, filename), 'utf8');
      expect(src).toContain('SECONDARY_BUTTON_CLASS');
      expect(src).not.toMatch(/variant=["']light["']/);
      expect(src).toContain("t('close')");
    },
  );
});
