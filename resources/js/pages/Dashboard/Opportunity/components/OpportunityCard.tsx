import OpportunityController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OpportunityController';
import { KTIcon } from '@/_metronic/helpers';
import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export type OpportunityListItem = {
  id: string;
  title: string;
  budget: string | number;
  status: { value: string; label: string; color: string };
  author?: { id: string | number; name: string; type: 'user' | 'provider' };
  region?: { title?: string };
  city?: { title?: string };
  offers_count?: number;
  comments_count?: number;
  created_at: string;
};

type Props = {
  row: OpportunityListItem;
};

const statusBadgeClass: Record<string, string> = {
  new: 'badge-light-primary',
  offer_accepted: 'badge-light-warning',
  in_progress: 'badge-light-info',
  ended: 'badge-light-success',
  cancelled: 'badge-light-danger',
};

const OpportunityCard = ({ row }: Props) => {
  const { t } = useTranslation();
  const badgeClass = statusBadgeClass[row.status?.value] ?? 'badge-light-secondary';

  return (
    <div className="card h-100 border-0 shadow-sm">
      <div className="card-body p-6 d-flex flex-column">
        <div className="d-flex justify-content-between align-items-start mb-4">
          <span className={`badge ${badgeClass} fw-bold px-3 py-2`}>{row.status?.label}</span>
          {row.author && (
            <span className={`badge badge-light-${row.author.type === 'user' ? 'info' : 'success'} fw-bold fs-8`}>
              {row.author.type === 'user' ? t('user') : t('provider')}
            </span>
          )}
        </div>

        <Link
          href={OpportunityController.show(row.id).url}
          className="text-gray-900 text-hover-primary fs-4 fw-bold d-block mb-2 text-truncate"
        >
          {row.title}
        </Link>

        {row.author && (
          <div className="d-flex align-items-center mb-3">
            <KTIcon iconName="profile-circle" className="fs-6 me-2 text-muted" />
            <span className="text-muted fw-semibold fs-7">{row.author.name}</span>
          </div>
        )}

        <div className="row g-2 mb-4">
          <div className="col-6">
            <div className="bg-light-primary rounded-2 p-2">
              <span className="text-gray-700 fw-bold fs-8">{t('budget')}</span>
              <div className="fw-bolder text-gray-900 fs-6">
                {Number(row.budget).toLocaleString()} {t('SAR')}
              </div>
            </div>
          </div>
          <div className="col-6">
            <div className="bg-light-info rounded-2 p-2">
              <span className="text-gray-700 fw-bold fs-8">{t('offers')}</span>
              <div className="fw-bolder text-gray-900 fs-6">{row.offers_count ?? 0}</div>
            </div>
          </div>
          <div className="col-6">
            <div className="bg-light-warning rounded-2 p-2">
              <span className="text-gray-700 fw-bold fs-8">{t('comments')}</span>
              <div className="fw-bolder text-gray-900 fs-6">{row.comments_count ?? 0}</div>
            </div>
          </div>
          <div className="col-6">
            <div className="bg-light-success rounded-2 p-2">
              <span className="text-gray-700 fw-bold fs-8">{t('created_at')}</span>
              <div className="fw-bold text-gray-900 fs-8">{new Date(row.created_at).toLocaleDateString()}</div>
            </div>
          </div>
        </div>

        <div className="d-flex align-items-center justify-content-between pt-4 border-top mt-auto gap-2">
          <Link href={OpportunityController.show(row.id).url} className="btn btn-sm btn-light-primary">
            {t('details')}
          </Link>
          <button
            type="button"
            className="btn btn-sm btn-light-danger"
            onClick={() => {
              if (window.confirm(t('are_you_sure_delete'))) {
                router.delete(OpportunityController.destroy(row.id).url);
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

export default OpportunityCard;
