import { useTranslation } from 'react-i18next';
import { DeactivateAccount } from '@/apps/provider/layouts/accounts/components/settings/cards/DeactivateAccount';
import { PROFILE_DANGER_ZONE_CARD_CLASS } from '@/apps/provider/pages/Profile/constants';

export default function DangerZoneCard() {
  const { t } = useTranslation();

  return (
    <div className={PROFILE_DANGER_ZONE_CARD_CLASS}>
      <div className="card-header border-0 pt-6 px-6 px-lg-8 bg-transparent">
        <h3 className="fw-bolder text-danger mb-0">{t('danger_zone')}</h3>
      </div>
      <div className="card-body pt-0 px-6 px-lg-8 pb-6">
        <DeactivateAccount embedded />
      </div>
    </div>
  );
}
