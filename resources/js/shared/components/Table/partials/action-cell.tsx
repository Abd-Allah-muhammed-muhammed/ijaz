import { KTIcon } from '@/vendor/metronic/helpers';
import { useTranslation } from 'react-i18next';
import type { ReactNode } from 'react';
import { SECONDARY_BUTTON_CLASS } from '@/shared/components/ui';

type Props = {
  children: ReactNode;
  /** Icon-only trigger for dense mobile rows (keeps the same Metronic menu). */
  compact?: boolean;
};

export default function ActionCell({ children, compact = false }: Props) {
  const { t } = useTranslation();
  const triggerClass = compact
    ? `${SECONDARY_BUTTON_CLASS} btn-icon`
    : SECONDARY_BUTTON_CLASS;

  return (
    <>
      <button
        type="button"
        className={triggerClass}
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end"
        aria-label={t('actions')}
      >
        {compact ? (
          <KTIcon iconName="dots-vertical" className="fs-2" aria-hidden="true" />
        ) : (
          <>
            {t('actions')}
            <KTIcon iconName="down" className="fs-5 m-0" aria-hidden="true" />
          </>
        )}
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
