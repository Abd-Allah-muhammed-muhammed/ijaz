import { KTIcon } from '@/vendor/metronic/helpers';
import { useTranslation } from 'react-i18next';
import type { ReactNode } from 'react';
import { SECONDARY_BUTTON_CLASS } from '@/shared/components/ui';

type Props = {
  children: ReactNode;
};

export default function ActionCell({ children }: Props) {
  const { t } = useTranslation();

  return (
    <>
      <button
        type="button"
        className={SECONDARY_BUTTON_CLASS}
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end"
        aria-label={t('actions')}
      >
        {t('actions')}
        <KTIcon iconName="down" className="fs-5 m-0" aria-hidden="true" />
      </button>
      <div
        className="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-125px py-4"
        data-kt-menu="true"
      >
        {children}
      </div>
    </>
  );
}
