import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { StatusBadge } from '@/shared/components/ui';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import type { Order } from '@/shared/types/models';

export type HomeOrderRowProps = {
  order: Order;
  showSeparator: boolean;
};

export default function HomeOrderRow({ order, showSeparator }: HomeOrderRowProps) {
  const { t } = useTranslation();

  return (
    <>
      <Link href={OrderController.show(order.id as string).url} className="text-gray-800 text-hover-primary">
        <div className="d-flex align-items-sm-center mb-5">
          <div className="d-flex align-items-center flex-row-fluid flex-wrap gap-3">
            <div className="me-2 flex-grow-1 min-w-0">
              <span className="fw-bold d-block fs-5 text-gray-800 text-truncate">
                {order.title}
              </span>
              <div className="d-flex flex-wrap gap-3 gap-md-5 mt-1">
                <span className="fw-semibold fs-7 d-flex align-items-center text-gray-500">
                  <img
                    src="/media/svg/note.svg"
                    alt=""
                    aria-hidden="true"
                    className="me-1"
                    style={{ width: '18px' }}
                  />
                  <span className="visually-hidden">{t('date')}: </span>
                  {new Date(order.created_at).toLocaleDateString()}
                </span>
                <span className="fw-semibold fs-7 d-flex align-items-center text-gray-500">
                  <img
                    src="/media/icons/wallet.svg"
                    alt=""
                    aria-hidden="true"
                    className="me-1"
                    style={{ width: '18px' }}
                  />
                  {t('from')} {order.budget_start} {t('to')} {order.budget_end}
                </span>
                <span className="fw-semibold fs-7 d-flex align-items-center text-gray-500">
                  <img
                    src="/media/icons/clock.svg"
                    alt=""
                    aria-hidden="true"
                    className="me-1"
                    style={{ width: '18px' }}
                  />
                  {order.expected_time}
                </span>
              </div>
              {order.description ? (
                <span className="fw-semibold fs-7 text-gray-500 d-block text-truncate mt-1">
                  {order.description}
                </span>
              ) : null}
            </div>
            <StatusBadge status={order.status} className="badge-lg fs-7" />
          </div>
        </div>
      </Link>
      {showSeparator ? <div className="separator separator-dashed mt-5 mb-6" /> : null}
    </>
  );
}
