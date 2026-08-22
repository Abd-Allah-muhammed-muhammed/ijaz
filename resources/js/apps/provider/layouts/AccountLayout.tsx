import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {KTIcon} from "@/vendor/metronic/helpers";
import {ReactElement, useState} from "react";
import {Provider} from "@/shared/types/models";
import {useTranslation} from "react-i18next";
import RatingStars from '@/shared/components/RatingStars';
import WalletQuickActions from '@/apps/provider/components/wallet/WalletQuickActions';
import {Collapse} from "react-bootstrap";


type Props = {
  children: ReactElement
  provider: Provider
}

const MetricTile = ({value, label}: { value?: string | number | null; label: string }) => (
  <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
    <div className='d-flex align-items-center'>
      <div className='fs-2 fw-bolder'>{value ?? '0.00'}</div>
    </div>
    <div className='fw-bold fs-6 text-gray-500'>{label}</div>
  </div>
)

const AccountLayout = ({children, provider}: Props) => {
  const {t} = useTranslation();
  const [showWalletDetails, setShowWalletDetails] = useState(false);

  return (
    <>
      <ToolbarWrapper/>
      <Content>
        <div className='card mb-5 mb-xl-10'>
          <div className='card-body pt-9 pb-0'>
            <div className='d-flex flex-wrap flex-sm-nowrap mb-3'>
              <div className='me-7 mb-4'>
                <div className='symbol symbol-100px symbol-lg-160px symbol-fixed position-relative'>
                  <img src={provider.logo} alt='Metronic' className='object-fit-contain'/>
                  <div
                    className='position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border-4 border-white h-20px w-20px'></div>
                </div>
              </div>

              <div className='flex-grow-1'>
                <div className='d-flex justify-content-between align-items-start flex-wrap mb-2'>
                  <div className='d-flex flex-column'>
                    <div className='d-flex align-items-center mb-2'>
                      <a href='#' className='text-gray-800 text-hover-primary fs-2 fw-bolder me-1'>
                        {provider.name}
                      </a>
                      <a href='#'>
                        <KTIcon iconName='verify' className='fs-1 text-primary'/>
                      </a>
                      <span className='ms-3'>
                          <RatingStars rating={provider.average_rating || 0}/>
                      </span>
                    </div>

                    <div className='d-flex flex-wrap fw-bold fs-6 mb-4 pe-2'>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2'
                      >
                        <KTIcon iconName='profile-circle' className='fs-4 me-1'/>
                        {provider.provider_type?.name || t('provider')}
                      </a>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2'
                      >
                        <KTIcon iconName='geolocation' className='fs-4 me-1'/>
                        {provider.address}
                      </a>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary mb-2'
                      >
                        <KTIcon iconName='sms' className='fs-4 me-1'/>
                        {provider.email}
                      </a>
                    </div>
                  </div>

                  <WalletQuickActions />
                </div>

                <div className='d-flex flex-wrap flex-stack'>
                  <div className='d-flex flex-column flex-grow-1 pe-8'>
                    <div className='d-flex flex-wrap'>
                      <MetricTile value={provider.wallet?.balance} label={t('balance')} />
                      <MetricTile value={provider.wallet?.pending_debit} label={t('wallet_on_hold')} />
                      <MetricTile value={provider.wallet?.amount_in_transfer} label={t('wallet_being_transferred')} />
                      <MetricTile value={provider.wallet?.total_earning} label={t('wallet_total_earned')} />
                    </div>
                    <button
                      type="button"
                      className="btn btn-sm btn-light mb-3"
                      onClick={() => setShowWalletDetails((open) => !open)}
                      aria-expanded={showWalletDetails}
                    >
                      {t('view_all_wallet_details')}
                    </button>
                    <Collapse in={showWalletDetails}>
                      <div className="d-flex flex-wrap">
                        <MetricTile value={provider.wallet?.total_spent} label={t('total_spent')} />
                        <MetricTile value={provider.wallet?.credit} label={t('credit')} />
                        <MetricTile value={provider.wallet?.pending_credit} label={t('pending_credit')} />
                        <MetricTile value={provider.wallet?.debit} label={t('debit')} />
                      </div>
                    </Collapse>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Content>
      {children}
    </>
  );
}

export default AccountLayout
