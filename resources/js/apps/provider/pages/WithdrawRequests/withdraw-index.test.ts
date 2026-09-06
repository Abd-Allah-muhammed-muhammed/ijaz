import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const src = readFileSync(join(__dirname, 'Index.tsx'), 'utf8');

describe('Withdraw Index redesign', () => {
  it('uses StackedDataTable instead of KTCard + legacy Table', () => {
    expect(src).toContain('StackedDataTable');
    expect(src).not.toContain('KTCard');
    expect(src).not.toMatch(/<Table[\s>]/);
  });

  it('keeps Show / Delete row actions via LinkAction and ConfirmAction', () => {
    expect(src).toContain('LinkAction');
    expect(src).toContain('ConfirmAction');
    expect(src).toContain('WithdrawController.show');
    expect(src).toContain('WithdrawController.destroy');
    expect(src).toContain('ActionCell');
  });

  it('renders search above the table card, not inside StackedDataTable toolbar', () => {
    expect(src).toContain('PageFilterBar');
    expect(src).not.toContain('toolbar=');
    const filterIndex = src.indexOf('PageFilterBar');
    const tableIndex = src.indexOf('<StackedDataTable');
    expect(filterIndex).toBeGreaterThan(-1);
    expect(tableIndex).toBeGreaterThan(-1);
    expect(filterIndex).toBeLessThan(tableIndex);
  });

  it('labels both status and transfer_status badges and shortens the reference', () => {
    expect(src).toContain("t('status')");
    expect(src).toContain("t('transfer_status')");
    expect(src).toContain('formatShortReference');
    expect(src).toContain('title={fullId}');
    expect(src).toContain('formatListDate');
    expect(src).toContain('STATEMENT_PAGE_SIZE');
  });
});
