import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import { StatusBadge, type StatusBadgeStatus } from '@/shared/components/ui';
import { ORDER_LIST_ROW_CARD_CLASS } from '@/apps/provider/pages/Orders/components/order-list-row-utils';

export type OrderListRowProps = {
  href: string;
  title: string;
  /** Budget range (orders) or offer price (offers). */
  amountLabel: string;
  description?: string | null;
  locationLabel?: string | null;
  timeLabel: string;
  /** Omit on Offers — that list is about the provider's offer, not peer offer counts. */
  offersCount?: number | null;
  /** Prefer when API returned EnumWithColors (`label` + `color`). */
  status?: StatusBadgeStatus | null;
  /** Use with `statusColorClass` for offer domain maps. */
  statusLabel?: string;
  statusColorClass?: string | null;
};

export { ORDER_LIST_ROW_CARD_CLASS };

export default function OrderListRow({
  href,
  title,
  amountLabel,
  description,
  locationLabel,
  timeLabel,
  offersCount,
  status,
  statusLabel,
  statusColorClass,
}: OrderListRowProps) {
  const { t } = useTranslation();
  const trimmedDescription = description?.trim() ?? '';
  const trimmedLocation = locationLabel?.trim() ?? '';
  const showOffersCount = offersCount !== undefined && offersCount !== null;

  return (
    <Link href={href} className={ORDER_LIST_ROW_CARD_CLASS}>
      <div className="d-flex align-items-start justify-content-between gap-2 mb-1">
        <h5 className="fw-bolder fs-6 text-gray-900 mb-0 text-truncate lh-base" title={title}>
          {title}
        </h5>
        <span className="fw-bold fs-7 text-gray-800 text-nowrap flex-shrink-0">
          {amountLabel}
        </span>
      </div>

      {trimmedDescription !== '' ? (
        <p
          className="text-muted fs-7 mb-2 text-truncate"
          title={trimmedDescription}
        >
          {trimmedDescription}
        </p>
      ) : null}

      <div className="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div className="d-flex flex-wrap align-items-center column-gap-3 row-gap-1 fs-8 text-muted min-w-0">
          {trimmedLocation !== '' ? (
            <span
              className="d-inline-flex align-items-center gap-1 min-w-0"
              aria-label={`${t('location')}: ${trimmedLocation}`}
            >
              <KTIcon iconName="geolocation" className="fs-6 flex-shrink-0" />
              <span className="text-truncate" style={{ maxWidth: '12rem' }}>
                {trimmedLocation}
              </span>
            </span>
          ) : null}

          {timeLabel !== '' ? (
            <span
              className="d-inline-flex align-items-center gap-1 flex-shrink-0"
              aria-label={`${t('date')}: ${timeLabel}`}
            >
              <KTIcon iconName="time" className="fs-6" />
              <span>{timeLabel}</span>
            </span>
          ) : null}

          {showOffersCount ? (
            <span
              className="d-inline-flex align-items-center gap-1 flex-shrink-0"
              aria-label={`${t('offers')}: ${offersCount}`}
            >
              <KTIcon iconName="message-text-2" className="fs-6" />
              <span className="fw-semibold">{offersCount}</span>
            </span>
          ) : null}
        </div>

        <StatusBadge
          status={status}
          label={statusLabel}
          colorClass={statusColorClass}
          className="fs-8 flex-shrink-0 ms-auto"
        />
      </div>
    </Link>
  );
}
