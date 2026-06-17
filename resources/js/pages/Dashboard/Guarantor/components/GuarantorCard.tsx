import GuarantorDashboardController from '@/actions/Modules/Guarantor/Http/Controllers/Dashboard/GuarantorController';
import { KTIcon } from '@/_metronic/helpers';
import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export type GuarantorListItem = {
  id: string;
  title: string;
  type: { value: string; label: string; color: string };
  status: { value: string; label: string; color: string };
  amount: string | number;
  total: string | number;
  requester?: { id: string | number; name: string; phone?: string };
  counterparty?: { id: string | number; name: string; phone?: string };
  installments_count?: number;
  created_at: string;
};

type Props = {
  row: GuarantorListItem;
};

const statusBadgeClass: Record<string, string> = {
  new: 'badge-light-primary',
  approved: 'badge-light-info',
  rejected: 'badge-light-warning',
  in_progress: 'badge-light-success',
  overdue: 'badge-light-danger',
  ended: 'badge-light-success',
  cancelled: 'badge-light-danger',
  refunded: 'badge-light-secondary',
};

const typeBadgeClass: Record<string, string> = {
  individual: 'badge-light-primary',
  company: 'badge-light-info',
};

const GuarantorCard = ({ row }: Props) => {
  const { t } = useTranslation();
  const statusClass = statusBadgeClass[row.status?.value] ?? 'badge-light-secondary';
  const typeClass = typeBadgeClass[row.type?.value] ?? 'badge-light-secondary';

  return (
    <div className="card h-100 border-0 shadow-sm">
      <div className="card-body p-6 d-flex flex-column">
        <div className="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
          <span className={`badge ${statusClass} fw-bold px-3 py-2`}>{row.status?.label}</span>
          <span className={`badge ${typeClass} fw-bold px-3 py-2`}>{row.type?.label}</span>
        </div>

        <Link
          href={GuarantorDashboardController.show(row.id).url}
          className="text-gray-900 text-hover-primary fs-4 fw-bold d-block mb-2 text-truncate"
        >
          {row.title}
        </Link>

        {row.requester && (
          <div className="d-flex align-items-center mb-2">
            <KTIcon iconName="profile-circle" className="fs-6 me-2 text-muted" />
            <span className="text-muted fw-semibold fs-7">
              {t('guarantor.requester')}: {row.requester.name}
            </span>
          </div>
        )}

        {row.counterparty && (
          <div className="d-flex align-items-center mb-3">
            <KTIcon iconName="people" className="fs-6 me-2 text-muted" />
            <span className="text-muted fw-semibold fs-7">
              {t('guarantor.counterparty')}: {row.counterparty.name}
            </span>
          </div>
        )}

        <div className="row g-2 mb-4">
          <div className="col-6">
            <div className="bg-light-primary rounded-2 p-2">
              <span className="text-gray-700 fw-bold fs-8">{t('guarantor.total_amount')}</span>
              <div className="fw-bolder text-gray-900 fs-6">
                {Number(row.total ?? row.amount).toLocaleString()} {t('SAR')}
              </div>
            </div>
          </div>
          {row.type?.value === 'company' && (
            <div className="col-6">
              <div className="bg-light-info rounded-2 p-2">
                <span className="text-gray-700 fw-bold fs-8">{t('guarantor.installments')}</span>
                <div className="fw-bolder text-gray-900 fs-6">{row.installments_count ?? 0}</div>
              </div>
            </div>
          )}
          <div className="col-6">
            <div className="bg-light-success rounded-2 p-2">
              <span className="text-gray-700 fw-bold fs-8">{t('created_at')}</span>
              <div className="fw-bold text-gray-900 fs-8">{new Date(row.created_at).toLocaleDateString()}</div>
            </div>
          </div>
        </div>

        <div className="d-flex align-items-center justify-content-between pt-4 border-top mt-auto gap-2">
          <Link href={GuarantorDashboardController.show(row.id).url} className="btn btn-sm btn-light-primary">
            {t('guarantor.details')}
          </Link>
          <button
            type="button"
            className="btn btn-sm btn-light-danger"
            onClick={() => {
              if (window.confirm(t('are_you_sure_delete'))) {
                router.delete(GuarantorDashboardController.destroy(row.id).url);
              }
            }}
          >
            {t('delete')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default GuarantorCard;
