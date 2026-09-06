import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const walletDir = __dirname;
const pageSrc = readFileSync(join(walletDir, 'Wallet.tsx'), 'utf8');
const heroSrc = readFileSync(
  join(walletDir, 'components', 'WalletBalanceHero.tsx'),
  'utf8',
);

describe('Wallet page redesign', () => {
  it('does not wrap Wallet in AccountLayout identity chrome', () => {
    expect(pageSrc).not.toContain('AccountLayout');
    expect(pageSrc).toContain('ProviderLayout');
    expect(pageSrc).toContain('WalletBalanceHero');
    expect(pageSrc).toContain('WalletMetrics');
  });

  it('lists transactions via StackedDataTable with Operation / Amount / Status / Date', () => {
    expect(pageSrc).toContain('StackedDataTable');
    expect(pageSrc).toContain("id: 'operation'");
    expect(pageSrc).toContain("id: 'amount'");
    expect(pageSrc).toContain("id: 'status'");
    expect(pageSrc).toContain("id: 'date'");
    expect(pageSrc).toContain('formatListDate');
    expect(pageSrc).not.toContain('reference_short');
    expect(pageSrc).not.toContain('balance_after');
    // Withdraw-only compact trailing — Wallet stays on the lighter mobile path.
    expect(pageSrc).not.toContain('mobileTrailing');
  });

  it('wraps page content in min-w-0 so long titles cannot force page overflow', () => {
    expect(pageSrc).toContain('className="min-w-0"');
  });

  it('renders search above the table card, not inside StackedDataTable toolbar', () => {
    expect(pageSrc).toContain('PageFilterBar');
    expect(pageSrc).not.toContain('toolbar=');
    const filterIndex = pageSrc.indexOf('PageFilterBar');
    const tableIndex = pageSrc.indexOf('<StackedDataTable');
    expect(filterIndex).toBeLessThan(tableIndex);
  });

  it('uses the shared statement page size default', () => {
    expect(pageSrc).toContain('STATEMENT_PAGE_SIZE');
  });

  it('places WithdrawTrigger on the balance hero, not a profile header', () => {
    expect(heroSrc).toContain('WithdrawTrigger');
    expect(heroSrc).toContain('formatCurrency');
    expect(heroSrc).toContain("t('balance')");
  });
});
