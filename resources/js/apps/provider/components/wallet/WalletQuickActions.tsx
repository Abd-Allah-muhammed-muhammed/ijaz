import {useState} from 'react'
import {Button} from 'react-bootstrap'
import {useTranslation} from 'react-i18next'
import {KTIcon} from '@/vendor/metronic/helpers'
import WithdrawModal from '@/apps/provider/components/wallet/WithdrawModal'
import RechargeModal from '@/apps/provider/components/wallet/RechargeModal'

type TriggerProps = {
  reloadOnly?: string[]
  className?: string
}

const DEFAULT_RELOAD_ONLY = ['provider', 'transactions']

export const WithdrawTrigger = ({reloadOnly = DEFAULT_RELOAD_ONLY, className}: TriggerProps) => {
  const [showWithdrawModal, setShowWithdrawModal] = useState(false)
  const {t} = useTranslation()

  return (
    <>
      <Button
        variant="light"
        size="sm"
        className={className ?? 'me-2'}
        onClick={() => setShowWithdrawModal(true)}
      >
        <KTIcon iconName='check' className='fs-3 d-none'/>
        <span className='indicator-label'>{t('withdraw')}</span>
      </Button>

      <WithdrawModal
        show={showWithdrawModal}
        onHide={() => setShowWithdrawModal(false)}
        reloadOnly={reloadOnly}
      />
    </>
  )
}

export const RechargeTrigger = ({reloadOnly = DEFAULT_RELOAD_ONLY, className}: TriggerProps) => {
  const [showRechargeModal, setShowRechargeModal] = useState(false)
  const {t} = useTranslation()

  return (
    <>
      <Button
        variant="primary"
        size="sm"
        className={className ?? 'me-3'}
        onClick={() => setShowRechargeModal(true)}
      >
        {t('recharge')}
      </Button>

      <RechargeModal
        show={showRechargeModal}
        onHide={() => setShowRechargeModal(false)}
        reloadOnly={reloadOnly}
      />
    </>
  )
}

type Props = TriggerProps & {
  className?: string
}

const WalletQuickActions = ({reloadOnly = DEFAULT_RELOAD_ONLY, className = 'd-flex my-4'}: Props) => {
  return (
    <div className={className}>
      <WithdrawTrigger reloadOnly={reloadOnly} />
      <RechargeTrigger reloadOnly={reloadOnly} />
    </div>
  )
}

export default WalletQuickActions
