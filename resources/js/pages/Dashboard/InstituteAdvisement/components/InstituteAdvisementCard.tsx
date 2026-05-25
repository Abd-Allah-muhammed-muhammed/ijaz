import { KTIcon } from '@/_metronic/helpers';
import { InstituteAdvisement } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import InstituteAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/InstituteAdvisementController';

type Props = {
  row: InstituteAdvisement;
};

const InstituteAdvisementCard = ({ row }: Props) => {
  const { t } = useTranslation();

  const formatFees = (): string => {
    if (!row.show_fees) {
      return t('on_contact');
    }
    if (row.fees_from != null && row.fees_to != null) {
      return `${Number(row.fees_from).toLocaleString()} - ${Number(row.fees_to).toLocaleString()} ${t('currency')}`;
    }
    if (row.fees_from != null) {
      return `${Number(row.fees_from).toLocaleString()} ${t('currency')}`;
    }
    if (row.fees_to != null) {
      return `${Number(row.fees_to).toLocaleString()} ${t('currency')}`;
    }
    return t('not_available');
  };

  return (
    <div className="card h-100 border-0 shadow-sm overflow-hidden hover-elevate-up">
      {/* ── Image ── */}
      <div className="card-header border-0 p-0 position-relative min-h-200px">
        <Link href={InstituteAdvisementController.show(row.id as number).url} className="d-block h-100 w-100">
          <img
            src={row.image_url}
            alt={row.title}
            className="w-100 h-100 object-fit-cover transition-all"
            onError={(e) => {
              e.currentTarget.src = '/media/avatars/blank.png';
            }}
          />
        </Link>
        {/* Status Badge */}
        <div className="position-absolute top-0 inset-e-0 m-4">
          <span className={`badge badge-${row.status?.color} fw-bold px-4 py-2`}>
            {row.status?.label}
          </span>
        </div>
        {/* Type Badge */}
        {row.type && (
          <div className="position-absolute top-0 inset-s-0 m-4">
            <span className={`badge badge-${row.type.color} fw-bold px-4 py-2`}>
              {row.type.label}
            </span>
          </div>
        )}
        {/* Fees Tag */}
        <div className="position-absolute bottom-0 inset-s-0 m-4">
          <div className="bg-white rounded-2 px-3 py-1 shadow-sm border">
            <span className="fw-bolder text-gray-900 fs-5">{formatFees()}</span>
          </div>
        </div>
      </div>

      {/* ── Content ── */}
      <div className="card-body p-6">
        <div className="mb-4">
          <Link
            href={InstituteAdvisementController.show(row.id as number).url}
            className="text-gray-900 text-hover-primary fs-4 fw-bold d-block mb-1 text-truncate"
          >
            {row.title}
          </Link>
          <div className="d-flex align-items-center">
            <KTIcon iconName="geolocation" className="fs-6 me-1 text-muted" />
            <span className="text-muted fw-semibold fs-7 text-truncate">
              {row.city?.title} - {row.region?.title}
            </span>
          </div>
        </div>

        {/* Info Grid */}
        <div className="row g-2 mb-5">
          {row.specialization && (
            <div className="col-6">
              <div className="bg-light-primary rounded-2 p-2 d-flex align-items-center">
                <KTIcon iconName="book" className="fs-5 text-primary me-2" />
                <span className="text-gray-700 fw-bold fs-8 text-truncate">{row.specialization.title}</span>
              </div>
            </div>
          )}
          {row.study_type && (
            <div className="col-6">
              <div className={`bg-light-${row.study_type.color} rounded-2 p-2 d-flex align-items-center`}>
                <KTIcon iconName="teacher" className={`fs-5 text-${row.study_type.color} me-2`} />
                <span className="text-gray-700 fw-bold fs-8 text-truncate">{row.study_type.label}</span>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="d-flex align-items-center justify-content-between pt-4 border-top">
          <div className="d-flex align-items-center">
            <div className="symbol symbol-30px symbol-circle me-3">
              <img
                src={row.user?.image || '/media/avatars/300-1.jpg'}
                alt={row.user?.name}
                onError={(e) => {
                  e.currentTarget.src = '/media/avatars/blank.png';
                }}
              />
            </div>
            <div className="d-flex flex-column">
              <span className="text-gray-800 fw-bold fs-8">{row.user?.name}</span>
              <span className="text-muted fw-semibold fs-9">{row.type?.label}</span>
            </div>
          </div>
          <div className="d-flex gap-2">
            <Link
              href={InstituteAdvisementController.show(row.id as number).url}
              className="btn btn-icon btn-light-primary btn-sm rounded-circle"
            >
              <KTIcon iconName="arrow-right" className="fs-4" />
            </Link>
            <button
              type="button"
              aria-label={t('delete')}
              className="btn btn-icon btn-light-danger btn-sm rounded-circle"
              onClick={() => {
                if (window.confirm(t('are_you_sure_delete'))) {
                  router.delete(InstituteAdvisementController.show(row.id as number).url);
                }
              }}
            >
              <KTIcon iconName="trash" className="fs-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default InstituteAdvisementCard;
