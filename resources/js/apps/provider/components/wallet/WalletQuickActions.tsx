import {useState} from 'react'
import {Button} from 'react-bootstrap'
import {useTranslation} from 'react-i18next'
import {KTIcon} from '@/vendor/metronic/helpers'
import WithdrawModal from '@/apps/provider/components/wallet/WithdrawModal'
import RechargeModal from '@/apps/provider/components/wallet/RechargeModal'

type Props = {
  reloadOnly?: string[]
  className?: string
}

const DEFAULT_RELOAD_ONLY = ['provider', 'transactions']

const WalletQuickActions = ({reloadOnly = DEFAULT_RELOAD_ONLY, className = 'd-flex my-4'}: Props) => {
  const [showWithdrawModal, setShowWithdrawModal] = useState(false)
  const [showRechargeModal, setShowRechargeModal] = useState(false)
  const {t} = useTranslation()

  return (
    <>
      <div className={className}>
        <Button
          variant="light"
          size="sm"
          className="me-2"
          onClick={() => setShowWithdrawModal(true)}
        >
          <KTIcon iconName='check' className='fs-3 d-none'/>
          <span className='indicator-label'>{t('withdraw')}</span>
        </Button>
        <Button
          variant="primary"
          size="sm"
          className="me-3"
          onClick={() => setShowRechargeModal(true)}
        >
          {t('recharge')}
        </Button>
      </div>

      <WithdrawModal
        show={showWithdrawModal}
        onHide={() => setShowWithdrawModal(false)}
        reloadOnly={reloadOnly}
      />
      <RechargeModal
        show={showRechargeModal}
        onHide={() => setShowRechargeModal(false)}
        reloadOnly={reloadOnly}
      />
    </>
  )
}

export default WalletQuickActions
