import { KTIcon } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { PageTitle } from '@/_metronic/layout/core';
import CarAdvisementController from '@/actions/App/Http/Controllers/Dashboard/CarAdvisementController';
import { AdvisementStatusEnum, OperationEnum, UsageStatusEnum } from '@/Enums/Advisements';
import { Media, CarAdvisement } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
  row: CarAdvisement;
};



const operationConfig: Record<string, { badge: string; color: string }> = {
  [OperationEnum.SALE]: { badge: 'badge-light-primary', color: 'text-primary' },
  [OperationEnum.RENT]: { badge: 'badge-light-info', color: 'text-info' },
  [OperationEnum.BUY]: { badge: 'badge-light-success', color: 'text-success' },
};

const usageStatusConfig: Record<string, { badge: string; color: string }> = {
    [UsageStatusEnum.NEW]: { badge: 'badge-light-success', color: 'text-success' },
    [UsageStatusEnum.USED]: { badge: 'badge-light-warning', color: 'text-warning' },
};

const ShowCarAdvisement = ({ row }: Props) => {
  const { t } = useTranslation();

  const handleStatusChange = (newStatus: string) => {
    router.put(CarAdvisementController.update(row.id as number).url, { status: newStatus }, { preserveScroll: true });
  };

  const oCfg = operationConfig[row.operation?.value as string] ?? { badge: 'badge-light-secondary', color: 'text-gray-500' };
  const uCfg = usageStatusConfig[row.usage_status?.value as string] ?? { badge: 'badge-light-secondary', color: 'text-gray-500' };

  return (
    <Content>
      <Head title={`${t('view_car_advisement')} #${row.id}`} />
      <PageTitle breadcrumbs={[{ title: t('car_advisements'), path: '/admin/car-advisements', isSeparator: false, isActive: false }]}>
        {t('view_car_advisement')}
      </PageTitle>

      <div className="d-flex flex-column gap-lg-10 gap-7">
        {/* Header Section */}
        <div className="card border-0 shadow-sm">
          <div className="card-body p-9">
            <div className="d-flex flex-sm-nowrap flex-wrap">
              {/* Media Wrap */}
              <div className="me-7 mb-4">
                <div
                  className="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative rounded-3"
                  style={{
                    background: row.image ? `url(${row.image}) center/cover no-repeat` : 'linear-gradient(135deg, #f5f8fa 0%, #e4e6ef 100%)',
                    width: '160px',
                    height: '160px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  {!row.image && <KTIcon iconName="car-2" className="fs-5x text-gray-400" />}

                  <div className="position-absolute align-items-center justify-content-between d-flex inset-e-0 top-0 w-100 gap-2 p-2">
                    <span className="badge bg-body fw-bold fs-8 text-gray-800 shadow-sm">#{row.id}</span>
                  </div>
                </div>
              </div>

              <div className="grow">
                <div className="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                  <div className="d-flex flex-column">
                    <div className="d-flex align-items-center mb-2 gap-2">
                      <h3 className="fs-2 fw-bolder mb-0 text-gray-900">{row.title}</h3>
                      <span className={`badge ${oCfg.badge} fw-bolder fs-8`}>{row.operation?.label ?? row.operation?.value}</span>
                      <span className={`badge ${uCfg.badge} fw-bolder fs-8`}>{row.usage_status?.label ?? row.usage_status?.value}</span>
                    </div>

                    <div className="d-flex fw-bold fs-6 mb-4 flex-wrap gap-4 pe-2">
                      {row.car_brand && (
                        <div className="d-flex align-items-center hover-primary text-gray-500">
                          <KTIcon iconName="car-2" className="fs-6 text-primary me-1" />
                          {row.car_brand.name}
                        </div>
                      )}
                      {row.car_type && (
                        <div className="d-flex align-items-center hover-primary text-gray-500">
                          <KTIcon iconName="car" className="fs-6 text-info me-1" />
                          {row.car_type.name}
                        </div>
                      )}
                      {row.city && (
                        <div className="d-flex align-items-center hover-primary text-gray-500">
                          <KTIcon iconName="map" className="fs-6 text-info me-1" />
                          {row.city.title}
                        </div>
                      )}
                      {row.phone && (
                        <div className="d-flex align-items-center hover-primary text-gray-500">
                          <KTIcon iconName="phone" className="fs-6 text-success me-1" />
                          {row.phone}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="d-flex my-4 gap-3">
                    <Link href={CarAdvisementController.index().url} className="btn btn-sm btn-light">
                      <KTIcon iconName="arrow-left" className="fs-6 px-1" />
                      {t('back')}
                    </Link>
                    <button
                      type="button"
                      className="btn btn-sm btn-icon btn-light-danger"
                      onClick={() => {
                        if (window.confirm(t('are_you_sure_delete'))) {
                          router.delete(CarAdvisementController.show(row.id as number).url);
                        }
                      }}
                    >
                      <KTIcon iconName="trash" className="fs-3" />
                    </button>
                  </div>
                </div>

                {/* Info Stats */}
                <div className="d-flex flex-stack flex-wrap">
                  <div className="d-flex flex-column grow pe-8">
                    <div className="d-flex flex-wrap gap-6">
                      <div className="min-w-125px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                        <div className="d-flex align-items-center">
                          <div className={`fs-2 fw-bolder ${oCfg.color}`}>
                            {row.show_price ? (
                              <>
                                {Number(row.price).toLocaleString()} <span className="fs-6 text-gray-600">{t('SAR')}</span>
                              </>
                            ) : (
                              <span className="fs-5 fst-italic text-gray-600">{t('not_available')}</span>
                            )}
                          </div>
                        </div>
                        <div className="fw-bold fs-6 text-gray-500">{t('price')}</div>
                      </div>

                      <div className="min-w-100px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                        <div className="d-flex align-items-center gap-2">
                          <KTIcon iconName="speedometer-2" className="fs-3 text-success" />
                          <div className="fs-2 fw-bolder text-gray-900">
                            {row.mileage ?? 0} <span className="fs-6 d-none d-xxl-inline">{t('km')}</span>
                          </div>
                        </div>
                        <div className="fw-bold fs-6 text-gray-500">{t('mileage')}</div>
                      </div>

                      <div className="min-w-100px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                        <div className="d-flex align-items-center gap-2">
                          <KTIcon iconName="calendar-8" className="fs-3 text-primary" />
                          <div className="fs-2 fw-bolder text-gray-900">{row.year ?? '-'}</div>
                        </div>
                        <div className="fw-bold fs-6 text-gray-500">{t('year')}</div>
                      </div>

                      <div className="min-w-100px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                        <div className="d-flex align-items-center gap-2">
                          <KTIcon iconName="gear" className="fs-3 text-info" />
                          <div className="fs-2 fw-bolder text-gray-900">{row.transmission ?? '-'}</div>
                        </div>
                        <div className="fw-bold fs-6 text-gray-500">{t('transmission')}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="row g-7 mx-0">
          {/* Details / Desc */}
          <div className="col-xl-8 ps-0">
            <div className="card mb-xl-8 mb-5 border-0 shadow-sm">
              <div className="card-header border-0 pt-6">
                <div className="card-title">
                  <h3 className="fw-bolder m-0">{t('car_description')}</h3>
                </div>
              </div>
              <div className="card-body pt-4">
                <div className="fs-5 fw-semibold mb-8 whitespace-pre-wrap text-gray-700">
                  {row.description || <span className="text-muted fst-italic">{t('no_description')}</span>}
                </div>

                <div className="separator separator-dashed my-8"></div>

                <h3 className="fw-bolder m-0 mb-5">{t('features_and_options')}</h3>
                {row.options && Array.isArray(row.options) && row.options.length > 0 ? (
                  <div className="d-flex flex-wrap gap-2">
                    {row.options.map((opt, i) => (
                      <span key={i} className="badge badge-light-primary fs-7 fw-bold px-4 py-3">
                        <KTIcon iconName="check" className="fs-6 text-primary me-1" /> {opt}
                      </span>
                    ))}
                  </div>
                ) : (
                  <span className="text-muted fst-italic">{t('no_features_listed')}</span>
                )}
              </div>
            </div>

            <div className="card border-0 shadow-sm">
              <div className="card-header border-0 pt-6">
                <div className="card-title">
                  <h3 className="fw-bolder m-0">{t('media')}</h3>
                </div>
              </div>
              <div className="card-body pt-4 pb-8">
                <div className="row g-4">
                  {row.media?.map((med: Media) => (
                    <div className="col-md-4 col-sm-6" key={med.id}>
                      <div
                        className="rounded-3"
                        style={{
                          height: '150px',
                          background: `url(${med.url}) center/cover no-repeat`,
                          border: '1px solid #e4e6ef',
                        }}
                      ></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Meta Info Sidebar */}
          <div className="col-xl-4 pe-0">
            <div className="card mb-5 border-0 shadow-sm">
              <div className="card-header border-0 pt-6">
                <div className="card-title">
                  <h3 className="fw-bolder m-0">{t('advertiser')}</h3>
                </div>
              </div>
              <div className="card-body pt-4">
                <div className="d-flex align-items-center gap-4">
                  <div className="symbol symbol-50px symbol-circle">
                    {row.user?.image ? (
                      <img src={row.user.image} alt="Avatar" />
                    ) : (
                      <span className="symbol-label bg-light-primary text-primary fs-3 fw-bold">{row.user?.name?.charAt(0).toUpperCase()}</span>
                    )}
                  </div>
                  <div className="d-flex flex-column">
                    <span className="fw-bold fs-5 text-gray-900">{row.user?.name}</span>
                    {row.user?.phone && <span className="text-muted fs-7">{row.user.phone}</span>}
                    {row.user?.email && <span className="text-muted fs-7">{row.user.email}</span>}
                  </div>
                </div>
              </div>
            </div>
            <div className="card border-0 shadow-sm">
              <div className="card-header border-0 pt-6">
                <div className="card-title">
                  <h3 className="fw-bolder m-0">{t('car_details')}</h3>
                </div>
              </div>
              <div className="card-body pt-4">
                <div className="d-flex flex-column gap-5">
                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('brand')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.car_brand?.name ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('type')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.car_type?.name ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('fuel_type')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.fuel_type ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('engine_size')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.engine_size ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('color')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.color ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('region')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.region?.title ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('city')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.city?.title ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('advisement_status')}</span>
                    <select
                      className={`form-select form-select-sm form-select-solid fw-bold fs-7 w-150px`}
                      value={row.status?.value}
                      onChange={(e) => handleStatusChange(e.target.value)}
                    >
                      <option value={AdvisementStatusEnum.PUBLISHED}>{t('advisement.status.published')}</option>
                      <option value={AdvisementStatusEnum.PENDING}>{t('advisement.status.pending')}</option>
                      <option value={AdvisementStatusEnum.REJECTED}>{t('advisement.status.rejected')}</option>
                      <option value={AdvisementStatusEnum.CLOSED}>{t('advisement.status.closed')}</option>
                    </select>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('created_at')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.created_at ? new Date(row.created_at).toLocaleDateString() : '-'}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Content>
  );
};

ShowCarAdvisement.layout = (page: ReactElement) => <MasterLayout children={page} />;
export default ShowCarAdvisement;
