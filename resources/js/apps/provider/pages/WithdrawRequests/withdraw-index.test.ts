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

  it('splits status and transfer_status into separate columns without inline labels', () => {
    expect(src).toContain("id: 'status'");
    expect(src).toContain("id: 'transfer_status'");
    expect(src).toContain("header: t('status')");
    expect(src).toContain("header: t('transfer_status')");
    expect(src).toContain('status={row.status}');
    expect(src).toContain('status={row.transfer_status}');
    expect(src).not.toContain('WithdrawStatusBadges');
    expect(src).not.toMatch(/t\('status'\):/);
    expect(src).not.toMatch(/t\('transfer_status'\):/);
    expect(src).toContain('formatShortReference');
    expect(src).toContain('STATEMENT_PAGE_SIZE');
  });
});
