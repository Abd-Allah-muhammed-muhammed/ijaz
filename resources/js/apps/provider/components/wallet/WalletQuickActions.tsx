import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import { SECONDARY_BUTTON_CLASS } from '@/shared/components/ui';
import WithdrawModal from '@/apps/provider/components/wallet/WithdrawModal';
// Paused (not removed) — chore/provider-topup-pause, 2026-09-04.
// import RechargeModal from '@/apps/provider/components/wallet/RechargeModal'

type TriggerProps = {
  reloadOnly?: string[];
  className?: string;
  /** Replaces the default secondary surface when a stronger CTA is needed. */
  buttonClassName?: string;
  availableBalance?: number | string | null;
};

const DEFAULT_RELOAD_ONLY = ['provider', 'transactions'];

export const WithdrawTrigger = ({
  reloadOnly = DEFAULT_RELOAD_ONLY,
  className,
  buttonClassName,
  availableBalance,
}: TriggerProps) => {
  const [showWithdrawModal, setShowWithdrawModal] = useState(false);
  const { t } = useTranslation();

  return (
    <>
      <button
        type="button"
        className={[buttonClassName ?? SECONDARY_BUTTON_CLASS, className ?? 'me-2']
          .filter(Boolean)
          .join(' ')}
        onClick={() => setShowWithdrawModal(true)}
      >
        <KTIcon iconName="check" className="fs-3 d-none" />
        <span className="indicator-label">{t('withdraw')}</span>
      </button>

      <WithdrawModal
        show={showWithdrawModal}
        onHide={() => setShowWithdrawModal(false)}
        reloadOnly={reloadOnly}
        availableBalance={availableBalance}
      />
    </>
  );
};

// Paused (not removed) — Provider dashboard top-up / recharge UI.
// Re-enable by uncommenting RechargeModal import + this export, and the
// <RechargeTrigger /> in WalletQuickActions below.
// Task: chore/provider-topup-pause (2026-09-04).
// export const RechargeTrigger = ({reloadOnly = DEFAULT_RELOAD_ONLY, className}: TriggerProps) => {
//   const [showRechargeModal, setShowRechargeModal] = useState(false)
//   const {t} = useTranslation()
//
//   return (
//     <>
//       <Button
//         variant="primary"
//         size="sm"
//         className={className ?? 'me-3'}
//         onClick={() => setShowRechargeModal(true)}
//       >
//         {t('recharge')}
//       </Button>
//
//       <RechargeModal
//         show={showRechargeModal}
//         onHide={() => setShowRechargeModal(false)}
//         reloadOnly={reloadOnly}
//       />
//     </>
//   )
// }

type Props = TriggerProps & {
  className?: string;
};

const WalletQuickActions = ({
  reloadOnly = DEFAULT_RELOAD_ONLY,
  className = 'd-flex my-4',
  availableBalance,
}: Props) => {
  return (
    <div className={className}>
      <WithdrawTrigger
        reloadOnly={reloadOnly}
        availableBalance={availableBalance}
      />
      {/* Paused (not removed) — chore/provider-topup-pause, 2026-09-04.
      <RechargeTrigger reloadOnly={reloadOnly} />
      */}
    </div>
  );
};

export default WalletQuickActions;
