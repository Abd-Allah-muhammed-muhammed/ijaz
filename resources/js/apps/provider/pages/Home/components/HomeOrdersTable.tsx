import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import {
  SectionCard,
  StatusBadge,
  SECONDARY_BUTTON_CLASS,
} from '@/shared/components/ui';
import { formatCurrency } from '@/shared/lib/formatters';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import type { Order } from '@/shared/types/models';
import type { OrderTabKey } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';
import {
  formatHomeOrderYourPrice,
  resolveHomeOrderRowStatus,
  resolveHomeOrderYourPrice,
} from '@/apps/provider/pages/Home/components/home-order-row-status';

export type HomeOrdersTableProps = {
  orders: Order[];
  tabKey: OrderTabKey;
};

export default function HomeOrdersTable({ orders, tabKey }: HomeOrdersTableProps) {
  const { t, i18n } = useTranslation();
  const currencyLabel = t('SAR');
  // Literal keys keep i18next's typed `t()` happy; mapping lives in HOME_ORDER_YOUR_PRICE_LABEL_KEY.
  const yourPriceColumnLabel =
    tabKey === 'pending' ? t('your_offer') : t('agreed_price');

  const formatPrice = (value: number) =>
    formatCurrency(value, {
      locale: i18n.language,
      currencyLabel,
      maximumFractionDigits: 2,
      minimumFractionDigits: 0,
    });

  return (
    <SectionCard
      className="shadow-none border border-gray-100"
      bodyClassName="card-body p-0"
      header={
        <div className="d-flex w-100 align-items-center gap-3 text-muted fs-8 text-uppercase fw-bold">
          <span className="flex-grow-1 min-w-0">{t('order')}</span>
          <span className="w-150px">{t('client')}</span>
          <span className="w-125px">{t('budget')}</span>
          <span className="w-125px">{yourPriceColumnLabel}</span>
          <span className="w-100px">{t('date')}</span>
          <span className="w-125px">{t('status')}</span>
          <span className="w-125px text-end">{t('actions')}</span>
        </div>
      }
      // Hide the entire header shell on mobile — inner `d-none` alone left an empty
      // `bg-light py-3` strip above the first row (~375px empty gray box).
      headerClassName="card-header border-0 bg-light bg-opacity-50 py-3 px-4 px-lg-6 d-none d-md-flex"
    >
      {orders.map((order, index) => {
        const status = resolveHomeOrderRowStatus(order, tabKey);
        const showUrl = OrderController.show(order.id as string).url;
        const clientName = order.user?.name ?? '—';
        const budgetLabel = `${order.budget_start} – ${order.budget_end}`;
        const dateLabel = new Date(order.created_at).toLocaleDateString();
        const yourPriceLabel = formatHomeOrderYourPrice(
          resolveHomeOrderYourPrice(order, tabKey),
          formatPrice,
        );

        return (
          <div key={order.id}>
            {/* Mobile: compact 2-line row (no field labels, chevron instead of full button). */}
            <Link
              href={showUrl}
              className="d-md-none d-flex align-items-center gap-3 py-3 px-4 text-decoration-none"
            >
              <div className="flex-grow-1 min-w-0">
                <div className="d-flex align-items-center justify-content-between gap-2 mb-1">
                  <span className="fw-semibold fs-6 text-gray-800 text-truncate">
                    {order.title}
                  </span>
                  <StatusBadge status={status} className="fs-8 flex-shrink-0" />
                </div>
                <div className="d-flex flex-wrap align-items-center column-gap-2 row-gap-1 fs-8 text-muted">
                  <span className="text-truncate text-gray-700 fw-semibold">{clientName}</span>
                  <span aria-hidden="true">·</span>
                  <span>{budgetLabel}</span>
                  <span aria-hidden="true">·</span>
                  <span className="fw-bold text-gray-800">{yourPriceLabel}</span>
                  <span aria-hidden="true">·</span>
                  <span>{dateLabel}</span>
                </div>
              </div>
              <KTIcon
                iconName="arrow-right"
                className="fs-2 text-gray-500 flex-shrink-0"
              />
            </Link>

            {/* Desktop: stacked table columns. */}
            <div className="d-none d-md-flex align-items-center gap-3 py-4 px-4 px-lg-6">
              <div className="flex-grow-1 min-w-0">
                <span className="fw-semibold fs-6 text-gray-800 text-break">
                  {order.title}
                </span>
              </div>
              <div className="w-150px min-w-0">
                <span className="fw-semibold fs-7 text-gray-700 text-truncate d-block">
                  {clientName}
                </span>
              </div>
              <div className="w-125px">
                <span className="fw-semibold fs-7 text-gray-600">{budgetLabel}</span>
              </div>
              <div className="w-125px">
                <span className="fw-bold fs-7 text-gray-800">{yourPriceLabel}</span>
              </div>
              <div className="w-100px">
                <span className="fw-semibold fs-7 text-gray-600">{dateLabel}</span>
              </div>
              <div className="w-125px">
                <StatusBadge status={status} className="fs-8" />
              </div>
              <div className="w-125px text-end">
                <Link
                  href={showUrl}
                  className={`${SECONDARY_BUTTON_CLASS} d-inline-flex align-items-center gap-1`}
                >
                  {t('view_details')}
                  <KTIcon iconName="arrow-right" className="fs-4" />
                </Link>
              </div>
            </div>

            {index !== orders.length - 1 ? (
              <div className="separator separator-dashed mx-4 mx-lg-6" />
            ) : null}
          </div>
        );
      })}
    </SectionCard>
  );
}
