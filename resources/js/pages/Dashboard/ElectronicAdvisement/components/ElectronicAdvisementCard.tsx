import { KTIcon } from '@/_metronic/helpers';
import { ElectronicAdvisement } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import ElectronicAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/ElectronicAdvisementController';

type Props = {
  row: ElectronicAdvisement;
};

const ElectronicAdvisementCard = ({ row }: Props) => {
  const { t } = useTranslation();

  return (
    <div className="card h-100 border-0 shadow-sm overflow-hidden hover-elevate-up">
      {/* ── Image ── */}
      <div className="card-header border-0 p-0 position-relative min-h-200px">
        <Link href={ElectronicAdvisementController.show(row.id as number).url} className="d-block h-100 w-100">
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
        {/* Condition Badge */}
        {row.condition && (
          <div className="position-absolute top-0 inset-s-0 m-4">
            <span className={`badge badge-${row.condition.color} fw-bold px-4 py-2`}>
              {row.condition.label}
            </span>
          </div>
        )}
        {/* Price Tag */}
        <div className="position-absolute bottom-0 inset-s-0 m-4">
          <div className="bg-white rounded-2 px-3 py-1 shadow-sm border">
            <span className="fw-bolder text-gray-900 fs-5">
              {row.show_price ? `${row.price} ${t('currency')}` : t('on_contact')}
            </span>
          </div>
        </div>
      </div>

      {/* ── Content ── */}
      <div className="card-body p-6">
        <div className="mb-4">
          <Link
            href={ElectronicAdvisementController.show(row.id as number).url}
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
          {row.device_category && (
            <div className="col-6">
              <div className="bg-light-primary rounded-2 p-2 d-flex align-items-center">
                <KTIcon iconName="devices" className="fs-5 text-primary me-2" />
                <span className="text-gray-700 fw-bold fs-8 text-truncate">{row.device_category.title}</span>
              </div>
            </div>
          )}
          {row.electronic_brand && (
            <div className="col-6">
              <div className="bg-light-warning rounded-2 p-2 d-flex align-items-center">
                <KTIcon iconName="star" className="fs-5 text-warning me-2" />
                <span className="text-gray-700 fw-bold fs-8 text-truncate">{row.electronic_brand.name}</span>
              </div>
            </div>
          )}
          {row.model_name && (
            <div className="col-6">
              <div className="bg-light-success rounded-2 p-2 d-flex align-items-center">
                <KTIcon iconName="abstract-26" className="fs-5 text-success me-2" />
                <span className="text-gray-700 fw-bold fs-8 text-truncate">{row.model_name}</span>
              </div>
            </div>
          )}
          {row.color && (
            <div className="col-6">
              <div className="bg-light-info rounded-2 p-2 d-flex align-items-center">
                <KTIcon iconName="color-swatch" className="fs-5 text-info me-2" />
                <span className="text-gray-700 fw-bold fs-8 text-truncate">{row.color}</span>
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
              <span className="text-muted fw-semibold fs-9">{row.condition?.label}</span>
            </div>
          </div>
          <div className="d-flex gap-2">
            <Link
              href={ElectronicAdvisementController.show(row.id as number).url}
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
                  router.delete(ElectronicAdvisementController.show(row.id as number).url);
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

export default ElectronicAdvisementCard;
