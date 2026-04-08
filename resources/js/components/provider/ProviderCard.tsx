import { KTIcon } from '@/_metronic/helpers'
import { FC } from 'react'
import ProviderMenu from "@/components/provider/ProviderMenu";
import { Provider } from "@/types/models";
import { useTranslation } from 'react-i18next';


type Props = {
  provider: Provider

}

const ProviderCard: FC<Props> = ({ provider }: Props) => {
  const { t } = useTranslation();

  return (
    <div className='card h-100 border-0 shadow-sm'>
      {/* Background Cover */}
      <div className='card-header border-0 h-100px min-h-100px p-0 position-relative bg-light-primary'>
        <div className='position-absolute top-0 end-0 p-4'>
          <button
            type='button'
            className='btn btn-icon btn-white btn-active-light-primary btn-sm shadow-sm'
            data-kt-menu-trigger='click'
            data-kt-menu-placement='bottom-end'
            data-kt-menu-flip='top-end'
          >
            <KTIcon iconName='category' className='fs-3 text-primary' />
          </button>
          <ProviderMenu provider={provider} />
        </div>
      </div>

      <div className='card-body d-flex flex-column align-items-center pt-0 px-9 pb-8'>
        {/* Avatar/Logo */}
        <div className='symbol symbol-100px symbol-circle mb-5 mt-n10 p-1 bg-white shadow-sm'>
          <img src={provider.logo} alt={provider.name} className='p-1 rounded-circle' />
        </div>

        {/* Name and Status */}
        <div className='text-center mb-4'>
          <a href='#' className='fs-3 fw-bold text-gray-900 text-hover-primary d-block mb-1'>
            {provider.name}
          </a>
          <div className='d-flex align-items-center justify-content-center gap-2'>
            <span className={`badge badge-light-${provider.status?.color || 'primary'} fs-8 fw-bold px-3 py-1`}>
              {provider.status?.label}
            </span>
            {provider.provider_type && (
              <span className='text-muted fs-7 fw-semibold'>
                {provider.provider_type.name}
              </span>
            )}
          </div>
        </div>

        {/* Rating */}
        <div className='d-flex align-items-center mb-5 font-size-lg'>
          {[1, 2, 3, 4, 5].map((star) => (
            <KTIcon
              key={star}
              iconName='star'
              className={`fs-6 ${star <= Math.round(provider.average_rating || 0) ? 'text-warning' : 'text-gray-300'}`}
            />
          ))}
          <span className='text-muted fs-7 fw-semibold ms-2'>
            ({provider.reviews_count || 0})
          </span>
        </div>

        {/* Stats */}
        <div className='d-flex flex-stack w-100 mb-6 bg-light rounded p-4'>
          <div className='d-flex flex-column align-items-center'>
            <span className='fs-5 fw-bold text-gray-900'>{provider.orders_count || 0}</span>
            <span className='fs-8 text-muted fw-bold text-uppercase'>{t('orders')}</span>
          </div>
          <div className='bullet bg-gray-300 h-25px w-1px mx-2'></div>
          <div className='d-flex flex-column align-items-center'>
            <span className='fs-5 fw-bold text-gray-900'>{provider.wallet?.balance || '0.00'}</span>
            <span className='fs-8 text-muted fw-bold text-uppercase'>{t('balance')}</span>
          </div>
          <div className='bullet bg-gray-300 h-25px w-1px mx-2'></div>
          <div className='d-flex flex-column align-items-center'>
            <span className='fs-5 fw-bold text-gray-900'>{provider.reviews_count || 0}</span>
            <span className='fs-8 text-muted fw-bold text-uppercase'>{t('reviews')}</span>
          </div>
        </div>

        {/* About */}
        <div className='text-center text-gray-500 fs-7 fw-semibold mb-6 line-clamp-2 min-h-40px' style={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
          {provider.about || t('no_description_available')}
        </div>

        {/* Location Info */}
        <div className='d-flex align-items-center justify-content-center gap-4 mt-auto w-100'>
          {provider.region && (
            <div className='d-flex align-items-center gap-1'>
              <KTIcon iconName='geolocation' className='fs-5 text-primary' />
              <span className='text-muted fs-7 fw-bold'>{provider.region.title}</span>
            </div>
          )}
          {provider.city && (
            <div className='d-flex align-items-center gap-1'>
              <span className='text-muted fs-7 fw-bold'>{provider.city.title}</span>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default ProviderCard
