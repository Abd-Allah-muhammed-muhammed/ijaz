import { useTranslation } from 'react-i18next';
import { formatCurrency, parseAmount } from '@/shared/lib/formatters';
import { SectionCard } from '@/shared/components/ui';
import { WithdrawTrigger } from '@/apps/provider/components/wallet/WalletQuickActions';

export type WalletBalanceHeroProps = {
  balance: number | string | null | undefined;
  pendingDebit?: number | string | null;
  currencyLabel: string;
  locale: string;
};

export default function WalletBalanceHero({
  balance,
  pendingDebit = 0,
  currencyLabel,
  locale,
}: WalletBalanceHeroProps) {
  const { t } = useTranslation();
  const formattedBalance = formatCurrency(balance ?? 0, {
    locale,
    currencyLabel,
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  });
  const availableBalance = parseAmount(balance) - parseAmount(pendingDebit);

  return (
    <SectionCard variant="hero" className="mb-5">
      <div className="d-flex flex-wrap align-items-center justify-content-between gap-4">
        <div className="min-w-0">
          <div className="text-muted fs-8 text-uppercase fw-bold mb-2">
            {t('balance')}
          </div>
          <div className="fs-2x fw-bolder text-gray-900 text-break">
            {formattedBalance}
          </div>
        </div>
        <WithdrawTrigger
          reloadOnly={['provider', 'transactions']}
          className="me-0"
          availableBalance={availableBalance}
        />
      </div>
    </SectionCard>
  );
}
