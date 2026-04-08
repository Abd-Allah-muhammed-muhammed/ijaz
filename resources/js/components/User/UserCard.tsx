import { KTIcon } from '@/_metronic/helpers'
import { FC } from 'react'
import UserMenu from "@/components/User/UserMenu";
import { User } from "@/types/models";
import { useTranslation } from 'react-i18next';

type Props = {
  user: User
}

const UserCard: FC<Props> = ({ user }: Props) => {
  const { t } = useTranslation();

  return (
    <div className='card h-100 border-0 shadow-sm'>
      {/* Background Cover */}
      <div className='card-header border-0 h-100px min-h-100px p-0 position-relative bg-light-info'>
        <div className='position-absolute top-0 end-0 p-4'>
          <button
            type='button'
            className='btn btn-icon btn-white btn-active-light-primary btn-sm shadow-sm'
            data-kt-menu-trigger='click'
            data-kt-menu-placement='bottom-end'
            data-kt-menu-flip='top-end'
          >
            <KTIcon iconName='category' className='fs-3 text-info' />
          </button>
          <UserMenu user={user} />
        </div>
      </div>

      <div className='card-body d-flex flex-column align-items-center pt-0 px-9 pb-8'>
        {/* Avatar */}
        <div className='symbol symbol-100px symbol-circle mb-5 mt-n10 p-1 bg-white shadow-sm'>
          <img src={user.image} alt={user.name} className='p-1 rounded-circle' />
        </div>

        {/* Name and Status */}
        <div className='text-center mb-4'>
          <a href='#' className='fs-3 fw-bold text-gray-900 text-hover-primary d-block mb-1'>
            {user.name}
          </a>
          <div className='d-flex align-items-center justify-content-center gap-2'>
            <span className={`badge badge-light-${user.status?.color || 'primary'} fs-8 fw-bold px-3 py-1`}>
              {user.status?.label}
            </span>
          </div>
        </div>

        {/* Basic Info */}
        <div className='d-flex flex-column align-items-center mb-5'>
          <div className='d-flex align-items-center gap-2 mb-1'>
            <KTIcon iconName='phone' className='fs-6 text-gray-500' />
            <span className='text-gray-600 fs-7 fw-semibold'>{user.phone}</span>
          </div>
          <div className='d-flex align-items-center gap-2'>
            <KTIcon iconName='sms' className='fs-6 text-gray-500' />
            <span className='text-gray-600 fs-7 fw-semibold'>{user.email}</span>
          </div>
        </div>

        {/* Stats */}
        <div className='d-flex flex-stack w-100 mb-6 bg-light rounded p-4'>
          <div className='d-flex flex-column align-items-center'>
            <span className='fs-5 fw-bold text-gray-900'>{user.orders_count || 0}</span>
            <span className='fs-8 text-muted fw-bold text-uppercase'>{t('orders')}</span>
          </div>
          <div className='bullet bg-gray-300 h-25px w-1px mx-2'></div>
          <div className='d-flex flex-column align-items-center'>
            <span className='fs-5 fw-bold text-gray-900'>{user.wallet?.balance || '0.00'}</span>
            <span className='fs-8 text-muted fw-bold text-uppercase'>{t('balance')}</span>
          </div>
          <div className='bullet bg-gray-300 h-25px w-1px mx-2'></div>
          <div className='d-flex flex-column align-items-center'>
            <span className='fs-5 fw-bold text-gray-900'>{(user.rating || 0).toFixed(1)}</span>
            <span className='fs-8 text-muted fw-bold text-uppercase'>{t('rating')}</span>
          </div>
        </div>

        {/* Footer Info */}
        <div className='mt-auto w-100'>
          <div className='d-flex flex-stack fs-7 fw-bold text-gray-500'>
            <span>{t('joined_at')}</span>
            <span className='text-gray-700'>{user.created_at ? new Date(user.created_at).toLocaleDateString() : '-'}</span>
          </div>
        </div>
      </div>
    </div>
  )
}

export default UserCard
