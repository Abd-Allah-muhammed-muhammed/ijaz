import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import RatingStars from '@/shared/components/RatingStars';
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import WalletQuickActions from '@/apps/provider/components/wallet/WalletQuickActions';
import { ProviderStatusEnum } from '@/Enums/Providers';
import type { Provider } from '@/shared/types/models';
import { SECONDARY_BUTTON_CLASS } from '@/shared/components/ui';

export type AccountHeaderProps = {
  provider: Provider;
};

export default function AccountHeader({ provider }: AccountHeaderProps) {
  const { t } = useTranslation();
  const isVerified = provider.status?.value === ProviderStatusEnum.Approved;
  const profileUrl = AuthController.profile().url;

  return (
    <div className="d-flex flex-wrap flex-sm-nowrap mb-3 gap-4">
      <div className="me-md-7 mb-2 mb-sm-4">
        <div className="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
          {provider.logo ? (
            <img src={provider.logo} alt={provider.name} className="object-fit-contain" />
          ) : (
            <span className="symbol-label fs-2 fw-bold text-primary">
              {(provider.name ?? '').charAt(0)}
            </span>
          )}
        </div>
      </div>

      <div className="flex-grow-1 min-w-0">
        <div className="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-2">
          <div className="d-flex flex-column min-w-0">
            <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
              <Link
                href={profileUrl}
                className="text-gray-800 text-hover-primary fs-2 fw-bolder text-break"
              >
                {provider.name}
              </Link>
              {isVerified ? (
                <span
                  className="d-inline-flex align-items-center"
                  aria-label={t('verified_provider')}
                  title={t('verified_provider')}
                >
                  <KTIcon iconName="verify" className="fs-1 text-primary" aria-hidden="true" />
                  <span className="visually-hidden">{t('verified_provider')}</span>
                </span>
              ) : null}
              <span className="ms-1">
                <RatingStars rating={provider.average_rating || 0} />
              </span>
            </div>

            <div className="d-flex flex-wrap fw-bold fs-6 mb-4 pe-2">
              <span className="d-flex align-items-center text-gray-500 me-5 mb-2">
                <KTIcon iconName="profile-circle" className="fs-4 me-1" aria-hidden="true" />
                {provider.provider_type?.name || t('provider')}
              </span>
              {provider.address ? (
                <span className="d-flex align-items-center text-gray-500 me-5 mb-2">
                  <KTIcon iconName="geolocation" className="fs-4 me-1" aria-hidden="true" />
                  {provider.address}
                </span>
              ) : null}
              {provider.email ? (
                <a
                  href={`mailto:${provider.email}`}
                  className="d-flex align-items-center text-gray-500 text-hover-primary mb-2"
                >
                  <KTIcon iconName="sms" className="fs-4 me-1" aria-hidden="true" />
                  {provider.email}
                </a>
              ) : null}
            </div>

            <Link href={profileUrl} className={`${SECONDARY_BUTTON_CLASS} align-self-start mb-3`}>
              {t('edit_profile')}
            </Link>
          </div>

          <WalletQuickActions />
        </div>
      </div>
    </div>
  );
}
