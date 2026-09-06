import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  SECONDARY_BUTTON_CLASS,
  SECONDARY_BUTTON_DEFAULT_CLASS,
} from '@/shared/components/ui/types';

const dir = __dirname;

describe('wallet modals dark-mode Close buttons', () => {
  it('SECONDARY_BUTTON_CLASS stays the dark-safe Metronic secondary surface', () => {
    expect(SECONDARY_BUTTON_CLASS).toContain('btn-bg-light');
    expect(SECONDARY_BUTTON_CLASS).toContain('btn-color-gray-900');
    expect(SECONDARY_BUTTON_CLASS).not.toMatch(/(?:^|\s)btn-light(?:\s|$)/);
  });

  it('SECONDARY_BUTTON_DEFAULT_CLASS matches dark-safe tokens without btn-sm', () => {
    expect(SECONDARY_BUTTON_DEFAULT_CLASS).toContain('btn-bg-light');
    expect(SECONDARY_BUTTON_DEFAULT_CLASS).toContain('btn-color-gray-900');
    expect(SECONDARY_BUTTON_DEFAULT_CLASS).not.toContain('btn-sm');
    expect(SECONDARY_BUTTON_DEFAULT_CLASS).not.toMatch(/(?:^|\s)btn-light(?:\s|$)/);
  });

  it('WithdrawModal Close uses dark-safe default-height secondary beside primary submit', () => {
    const src = readFileSync(join(dir, 'WithdrawModal.tsx'), 'utf8');
    expect(src).toContain('SECONDARY_BUTTON_DEFAULT_CLASS');
    expect(src).toContain("t('close')");
    expect(src).toContain('btn btn-primary');
    expect(src).not.toMatch(/variant=["']light["']/);
  });

  it('RechargeModal Close uses SECONDARY_BUTTON_CLASS instead of variant="light"', () => {
    const src = readFileSync(join(dir, 'RechargeModal.tsx'), 'utf8');
    expect(src).toContain('SECONDARY_BUTTON_CLASS');
    expect(src).not.toMatch(/variant=["']light["']/);
    expect(src).toContain("t('close')");
  });
});

describe('WithdrawModal polish', () => {
  const src = readFileSync(join(dir, 'WithdrawModal.tsx'), 'utf8');

  it('shows available balance and translated notes placeholder', () => {
    expect(src).toContain('availableBalance');
    expect(src).toContain("t('available_balance'");
    expect(src).toContain("t('withdraw_notes_placeholder')");
    expect(src).toContain('formatCurrency');
  });

  it('keeps footer buttons equally proportioned with shared min width', () => {
    expect(src).toContain('min-w-100px');
    expect(src).toContain('SECONDARY_BUTTON_DEFAULT_CLASS');
  });
});
