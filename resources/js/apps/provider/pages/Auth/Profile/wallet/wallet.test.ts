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
  });

  it('places WithdrawTrigger on the balance hero, not a profile header', () => {
    expect(heroSrc).toContain('WithdrawTrigger');
    expect(heroSrc).toContain('formatCurrency');
    expect(heroSrc).toContain("t('balance')");
  });
});
