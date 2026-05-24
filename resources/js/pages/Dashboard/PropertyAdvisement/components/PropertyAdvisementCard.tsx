import { AdvisementStatusEnum, OperationEnum } from '@/Enums/Advisements';
import { KTIcon } from '@/_metronic/helpers';
import PropertyAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/PropertyAdvisementController';
import { PropertyAdvisement } from '@/types/models';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

type Props = {
  row: PropertyAdvisement;
};

const statusConfig: Record<string, { badge: string; icon: string; iconColor: string }> = {
  [AdvisementStatusEnum.PUBLISHED]: { badge: 'badge-light-success', icon: 'check-circle', iconColor: 'text-success' },
  [AdvisementStatusEnum.PENDING]: { badge: 'badge-light-warning', icon: 'time', iconColor: 'text-warning' },
  [AdvisementStatusEnum.REJECTED]: { badge: 'badge-light-danger', icon: 'cross-circle', iconColor: 'text-danger' },
  [AdvisementStatusEnum.CLOSED]: { badge: 'badge-light-secondary', icon: 'minus-circle', iconColor: 'text-secondary' },
};

const operationConfig: Record<string, { badge: string }> = {
  [OperationEnum.SALE]: { badge: 'badge-light-primary' },
  [OperationEnum.RENT]: { badge: 'badge-light-info' },
  [OperationEnum.BUY]: { badge: 'badge-light-success' },
};

const PropertyAdvisementCard = ({ row }: Props) => {
  const { t } = useTranslation();

  const sCfg = statusConfig[row.status?.value] ?? statusConfig[AdvisementStatusEnum.CLOSED];
  const oCfg = operationConfig[row.operation?.value as string] ?? { badge: 'badge-light-secondary' };
  const hasImage = !!row.image;

  return (
    <div
      className="card hover-elevate-up h-100 cursor-pointer border-0 shadow-sm transition-all"
      onClick={() => router.visit(PropertyAdvisementController.show(row.id as number).url)}
    >
      <div className="app-content flex-column-fluid">
        <div
          className="rounded-top position-relative overflow-hidden"
          style={{
            height: 200,
            background: hasImage ? `url(${row.image}) center/cover no-repeat` : 'linear-gradient(135deg, #f5f8fa 0%, #e4e6ef 100%)',
          }}
        >
          {!hasImage && (
            <div className="d-flex align-items-center justify-content-center h-100">
              <KTIcon iconName="home-2" className="fs-5x text-gray-400" />
            </div>
          )}

          <div
            className="position-absolute d-flex align-items-center justify-content-between inset-e-0 top-0 m-3 gap-2"
            style={{
              width: '-webkit-fill-available',
            }}
          >
            <span className="badge bg-body fw-bold fs-8 px-3 py-2 text-gray-800 shadow-sm">#{row.id}</span>
            <span className={`badge ${sCfg.badge} fw-bold fs-8 px-3 py-2 shadow-sm`}>
              <KTIcon iconName={sCfg.icon} className={`fs-8 me-1 ${sCfg.iconColor}`} />
              {row.status?.label ?? row.status?.value}
            </span>
          </div>
        </div>
      </div>

      <div className="app-content flex-column-fluid">
        <div className="card-body d-flex flex-column p-6">
          <div className="d-flex align-items-start justify-content-between mb-1 gap-2">
            <h5 className="fw-bold fs-5 text-truncate mb-0 text-gray-900" title={row.title}>
              {row.title}
            </h5>
            <div className="d-flex align-items-center gap-2">
              <span className={`badge ${oCfg.badge} fw-bold fs-8 px-2 py-1`}>{row.operation?.label ?? row.operation?.value}</span>
              <button
                className="btn btn-icon btn-sm btn-light"
                onClick={(e) => {
                  e.stopPropagation();
                  window.history.back();
                }}
                title={t('back')}
              >
                <KTIcon iconName="arrow-left" className="fs-6" />
              </button>
              <button
                className="btn btn-icon btn-sm btn-light-danger"
                onClick={(e) => {
                  e.stopPropagation();
                  if (window.confirm(t('are_you_sure'))) {
                    router.delete(PropertyAdvisementController.show(row.id as number).url);
                  }
                }}
                title={t('delete')}
              >
                <KTIcon iconName="trash" className="fs-6" />
              </button>
            </div>
          </div>
          {row.address && (
            <div className="d-flex align-items-center text-muted fs-7 mb-4 gap-1">
              <KTIcon iconName="geolocation" className="fs-7 text-primary" />
              <span className="text-truncate">{row.address}</span>
            </div>
          )}
          <div className="mb-4">
            {row.show_price ? (
              <span className="fs-3 fw-bolder text-primary">
                {Number(row.price).toLocaleString()}
                <span className="fs-7 fw-semibold text-muted ms-1">{t('SAR')}</span>
              </span>
            ) : (
              <span className="fs-6 fw-semibold text-muted fst-italic">{t('not_available')}</span>
            )}
          </div>
          <div className="d-flex align-items-center bg-light rounded-2 mb-4 gap-4 px-4 py-3">
            <div className="d-flex flex-column align-items-center">
              <KTIcon iconName="home-2" className="fs-4 text-primary mb-1" />
              <span className="fw-bold fs-6 text-gray-800">{row.bedrooms_count ?? 0}</span>
              <span className="text-muted fs-9">{t('bedrooms')}</span>
            </div>
            <div className="separator separator-dashed h-30px"></div>
            <div className="d-flex flex-column align-items-center">
              <KTIcon iconName="drop" className="fs-4 text-info mb-1" />
              <span className="fw-bold fs-6 text-gray-800">{row.bathrooms_count ?? 0}</span>
              <span className="text-muted fs-9">{t('bathrooms')}</span>
            </div>
            <div className="separator separator-dashed h-30px"></div>
            <div className="d-flex flex-column align-items-center">
              <KTIcon iconName="element-7" className="fs-4 text-warning mb-1" />
              <span className="fw-bold fs-6 text-gray-800">{row.halls_count ?? 0}</span>
              <span className="text-muted fs-9">{t('halls')}</span>
            </div>
            <div className="separator separator-dashed h-30px"></div>
            <div className="d-flex flex-column align-items-center">
              <KTIcon iconName="size" className="fs-4 text-success mb-1" />
              <span className="fw-bold fs-6 text-gray-800">{row.area ?? 0}</span>
              <span className="text-muted fs-9">{t('area')}</span>
            </div>
          </div>
          <div className="d-flex flex-column mb-5 gap-2">
            {row.phone && (
              <div className="d-flex align-items-center text-muted fs-7 gap-2">
                <KTIcon iconName="phone" className="fs-6 text-gray-500" />
                <span>{row.phone}</span>
              </div>
            )}
            {row.age != null && (
              <div className="d-flex align-items-center text-muted fs-7 gap-2">
                <KTIcon iconName="calendar" className="fs-6 text-gray-500" />
                <span>{row.age} yr</span>
              </div>
            )}
            {row.created_at && (
              <div className="d-flex align-items-center text-muted fs-7 gap-2">
                <KTIcon iconName="time" className="fs-6 text-gray-500" />
                <span>{new Date(row.created_at).toLocaleDateString()}</span>
              </div>
            )}
          </div>
          <div className="d-flex mt-auto gap-3" onClick={(e) => e.stopPropagation()}>
            <select
              className="form-select form-select-sm form-select-solid flex-fill"
              defaultValue={row.status?.value}
              onChange={(e) => {
                console.log(e);
              }}
            >
              <option value={AdvisementStatusEnum.PUBLISHED}>{t('advisement.status.published')}</option>
              <option value={AdvisementStatusEnum.PENDING}>{t('advisement.status.pending')}</option>
              <option value={AdvisementStatusEnum.REJECTED}>{t('advisement.status.rejected')}</option>
              <option value={AdvisementStatusEnum.CLOSED}>{t('advisement.status.closed')}</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PropertyAdvisementCard;
