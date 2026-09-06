import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import RatingStars from '@/shared/components/RatingStars';
import { ProviderStatusEnum } from '@/Enums/Providers';
import type { Provider } from '@/shared/types/models';
import { SectionCard } from '@/shared/components/ui';
import {
  PROFILE_AVATAR_CLASS,
  PROFILE_AVATAR_SIZE_PX,
} from '@/apps/provider/pages/Profile/constants';

export type ProfileIdentityHeaderProps = {
  provider: Provider;
};

export default function ProfileIdentityHeader({
  provider,
}: ProfileIdentityHeaderProps) {
  const { t } = useTranslation();
  const isVerified = provider.status?.value === ProviderStatusEnum.Approved;
  const avatarStyle = {
    width: PROFILE_AVATAR_SIZE_PX,
    height: PROFILE_AVATAR_SIZE_PX,
  } as const;

  return (
    <SectionCard variant="hero" className="mb-5">
      <div className="d-flex align-items-center flex-wrap gap-3">
        <div
          className={PROFILE_AVATAR_CLASS}
          style={avatarStyle}
          data-pan="profile-identity-avatar"
        >
          {provider.logo ? (
            <img
              src={provider.logo}
              alt={provider.name}
              data-pan="profile-identity-avatar-img"
              style={{
                width: PROFILE_AVATAR_SIZE_PX,
                height: PROFILE_AVATAR_SIZE_PX,
                objectFit: 'cover',
                display: 'block',
                borderRadius: '50%',
              }}
            />
          ) : (
            <span
              className="d-flex align-items-center justify-content-center w-100 h-100 fs-4 fw-bold text-primary"
              aria-hidden="true"
            >
              {(provider.name ?? '').charAt(0)}
            </span>
          )}
        </div>

        <div className="d-flex align-items-center flex-wrap gap-2 min-w-0">
          <h1 className="fs-2 fw-bolder text-gray-900 text-break mb-0">
            {provider.name}
          </h1>
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
          <RatingStars rating={provider.average_rating || 0} />
        </div>
      </div>
    </SectionCard>
  );
}
