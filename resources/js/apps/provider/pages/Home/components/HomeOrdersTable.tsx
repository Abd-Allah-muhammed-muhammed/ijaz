import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import { SectionCard, StatusBadge } from '@/shared/components/ui';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import type { Order } from '@/shared/types/models';
import type { OrderTabKey } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';
import { resolveHomeOrderRowStatus } from '@/apps/provider/pages/Home/components/home-order-row-status';

export type HomeOrdersTableProps = {
  orders: Order[];
  tabKey: OrderTabKey;
};

export default function HomeOrdersTable({ orders, tabKey }: HomeOrdersTableProps) {
  const { t } = useTranslation();

  return (
    <SectionCard
      className="shadow-none border border-gray-100"
      bodyClassName="card-body p-0"
      header={
        <div className="d-none d-md-flex w-100 align-items-center gap-3 text-muted fs-8 text-uppercase fw-bold">
          <span className="flex-grow-1 min-w-0">{t('order')}</span>
          <span className="w-150px">{t('client')}</span>
          <span className="w-125px">{t('budget')}</span>
          <span className="w-100px">{t('date')}</span>
          <span className="w-125px">{t('status')}</span>
          <span className="w-125px text-end">{t('actions')}</span>
        </div>
      }
      headerClassName="card-header border-0 bg-light bg-opacity-50 py-3 px-4 px-lg-6"
    >
      {orders.map((order, index) => {
        const status = resolveHomeOrderRowStatus(order, tabKey);
        const showUrl = OrderController.show(order.id as string).url;

        return (
          <div key={order.id}>
            <div className="d-flex flex-column flex-md-row align-items-md-center gap-2 gap-md-3 py-4 px-4 px-lg-6">
              <div className="flex-grow-1 min-w-0">
                <span className="d-md-none text-muted fs-8 text-uppercase fw-bold d-block mb-1">
                  {t('order')}
                </span>
                <span className="fw-semibold fs-6 text-gray-800 text-break">
                  {order.title}
                </span>
              </div>
              <div className="w-md-150px min-w-0">
                <span className="d-md-none text-muted fs-8 text-uppercase fw-bold d-block mb-1">
                  {t('client')}
                </span>
                <span className="fw-semibold fs-7 text-gray-700 text-truncate d-block">
                  {order.user?.name ?? '—'}
                </span>
              </div>
              <div className="w-md-125px">
                <span className="d-md-none text-muted fs-8 text-uppercase fw-bold d-block mb-1">
                  {t('budget')}
                </span>
                <span className="fw-bold fs-7 text-gray-800">
                  {order.budget_start} – {order.budget_end}
                </span>
              </div>
              <div className="w-md-100px">
                <span className="d-md-none text-muted fs-8 text-uppercase fw-bold d-block mb-1">
                  {t('date')}
                </span>
                <span className="fw-semibold fs-7 text-gray-600">
                  {new Date(order.created_at).toLocaleDateString()}
                </span>
              </div>
              <div className="w-md-125px">
                <span className="d-md-none text-muted fs-8 text-uppercase fw-bold d-block mb-1">
                  {t('status')}
                </span>
                <StatusBadge status={status} className="fs-8" />
              </div>
              <div className="w-md-125px text-md-end">
                <Link
                  href={showUrl}
                  className="btn btn-sm btn-light d-inline-flex align-items-center gap-1"
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
