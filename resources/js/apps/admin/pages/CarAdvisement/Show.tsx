import CarAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/CarAdvisementController';
import { AdvisementStatusEnum, OperationEnum, UsageStatusEnum } from '@/Enums/Advisements';
import usePermissions from '@/shared/hooks/use-permissions';
import { CarAdvisement, Media } from '@/shared/types/models';
import { KTCard, KTCardBody, KTIcon } from '@/vendor/metronic/helpers';
import { Content } from '@/vendor/metronic/layout/components/content';
import { PageTitle } from '@/vendor/metronic/layout/core';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
  row: CarAdvisement;
};

const operationBadgeClass: Record<string, string> = {
  [OperationEnum.SALE]: 'badge-light-primary',
  [OperationEnum.RENT]: 'badge-light-info',
  [OperationEnum.BUY]: 'badge-light-success',
};

const usageBadgeClass: Record<string, string> = {
  [UsageStatusEnum.NEW]: 'badge-light-success',
  [UsageStatusEnum.USED]: 'badge-light-warning',
};

const statusBadgeClass: Record<string, string> = {
  [AdvisementStatusEnum.PUBLISHED]: 'badge-light-success',
  [AdvisementStatusEnum.PENDING]: 'badge-light-warning',
  [AdvisementStatusEnum.REJECTED]: 'badge-light-danger',
  [AdvisementStatusEnum.CLOSED]: 'badge-light-secondary',
};

const Field = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div>
    <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{label}</div>
    <div className="fw-semibold text-gray-900 fs-6">{value || '—'}</div>
  </div>
);

const MediaCard = ({ item }: { item: Media }) => (
  <a
    href={item.url}
    target="_blank"
    rel="noreferrer"
    className="card border border-gray-200 shadow-xs rounded-4 text-decoration-none h-100 hover-elevate-up"
    style={{ width: 160, minHeight: 170 }}
  >
    <div className="card-body d-flex flex-column align-items-center justify-content-center p-4">
      <img
        src={item.url}
        alt=""
        className="rounded-3 border border-gray-100 mb-3 object-fit-cover"
        style={{ width: 96, height: 96 }}
      />
      <span className="fw-semibold text-gray-800 fs-8 text-center text-truncate w-100">
        #{item.id}
      </span>
    </div>
  </a>
);

const ShowCarAdvisement = ({ row }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canEdit = hasPermission('edit carAdvisements');
  const canDelete = hasPermission('delete carAdvisements');

  const createdDate = row.created_at ? new Date(row.created_at).toLocaleDateString() : '—';
  const typeLabel = row.car_type?.name ?? row.car_brand?.name ?? t('car_advisement');
  const subtitle = `${typeLabel} · ${createdDate}`;

  const operationBadge =
    operationBadgeClass[row.operation?.value as string] ?? 'badge-light-secondary';
  const usageBadge = usageBadgeClass[row.usage_status?.value as string] ?? 'badge-light-secondary';
  const statusBadge = statusBadgeClass[row.status?.value as string] ?? 'badge-light-secondary';

  const options = Array.isArray(row.options) ? row.options : [];
  const media = row.media ?? [];

  const handleStatusChange = (newStatus: string) => {
    router.put(CarAdvisementController.update(row.id as number).url, { status: newStatus }, { preserveScroll: true });
  };

  const confirmDelete = () => {
    if (window.confirm(t('are_you_sure_delete'))) {
      router.delete(CarAdvisementController.destroy(row.id as number).url);
    }
  };

  return (
    <Content>
      <Head title={`${t('view_car_advisement')} #${row.id}`} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('car_advisements'),
            path: CarAdvisementController.index().url,
            isSeparator: false,
            isActive: false,
          },
        ]}
      >
        {t('view_car_advisement')}
      </PageTitle>

      <div className="d-flex flex-column gap-7 gap-lg-10">
        {/* Header card — mirrors Guarantor Show */}
        <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div className="card-body p-6 p-lg-8 bg-light-primary bg-opacity-10">
            <div className="d-flex justify-content-between align-items-start flex-wrap gap-5 mb-6">
              <div className="d-flex align-items-start gap-4 min-w-0">
                <div className="symbol symbol-55px symbol-circle flex-shrink-0">
                  <span className="symbol-label bg-white text-primary shadow-sm">
                    <KTIcon iconName="car-2" className="fs-2x" />
                  </span>
                </div>
                <div className="min-w-0">
                  <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <h1 className="fs-2 fw-bolder text-gray-900 mb-0 text-truncate">{row.title}</h1>
                    <span className={`badge ${statusBadge} rounded-pill fw-bold px-3 py-2`}>
                      {row.status?.label ?? row.status?.value}
                    </span>
                    <span className={`badge ${operationBadge} rounded-pill fw-bold px-3 py-2`}>
                      {row.operation?.label ?? row.operation?.value}
                    </span>
                    <span className={`badge ${usageBadge} rounded-pill fw-bold px-3 py-2`}>
                      {row.usage_status?.label ?? row.usage_status?.value}
                    </span>
                  </div>
                  <div className="text-muted fw-semibold fs-6">{subtitle}</div>
                </div>
              </div>

              {/* Action group: Back (ghost) then Delete (danger). Same flex pattern as Guarantor — logical end alignment works in LTR and RTL. */}
              <div className="d-flex gap-2 flex-wrap align-items-center">
                <Link
                  href={CarAdvisementController.index().url}
                  className="btn btn-sm btn-light btn-active-light-primary rounded-pill"
                >
                  <KTIcon iconName="arrow-left" className="fs-6" />
                  {t('back')}
                </Link>
                {canDelete && (
                  <button
                    type="button"
                    className="btn btn-sm btn-light-danger rounded-pill"
                    onClick={confirmDelete}
                  >
                    <KTIcon iconName="trash" className="fs-5" />
                    {t('delete')}
                  </button>
                )}
              </div>
            </div>

            <div className="row g-4">
              <div className="col-6 col-md-3">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('price')}</div>
                  <div className="fs-3 fw-bolder text-gray-900">
                    {row.show_price ? (
                      <>
                        {Number(row.price).toLocaleString()}{' '}
                        <span className="fs-6 text-muted fw-semibold">{t('SAR')}</span>
                      </>
                    ) : (
                      <span className="fs-5 fst-italic text-muted">{t('not_available')}</span>
                    )}
                  </div>
                </div>
              </div>
              <div className="col-6 col-md-3">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('mileage')}</div>
                  <div className="fs-3 fw-bolder text-gray-900">
                    {row.mileage ?? 0}{' '}
                    <span className="fs-6 text-muted fw-semibold">{t('km')}</span>
                  </div>
                </div>
              </div>
              <div className="col-6 col-md-3">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('year')}</div>
                  <div className="fs-3 fw-bolder text-gray-900">{row.year ?? '—'}</div>
                </div>
              </div>
              <div className="col-6 col-md-3">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('transmission')}</div>
                  <div className="fs-3 fw-bolder text-gray-900">
                    {row.transmission?.label ?? row.transmission?.value ?? '—'}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="row g-7">
          <div className="col-xl-8">
            <KTCard className="border-0 shadow-sm rounded-4 mb-7">
              <KTCardBody className="p-6 p-lg-9">
                <div className="d-flex flex-column gap-6">
                  <div>
                    <div className="text-muted fs-8 text-uppercase fw-bold mb-2">{t('car_description')}</div>
                    <p className={`fs-6 mb-0 lh-lg ${row.description ? 'text-gray-800' : 'text-muted'}`}>
                      {row.description || t('no_description')}
                    </p>
                  </div>

                  <div className="separator separator-dashed my-1" />

                  <div>
                    <div className="text-muted fs-8 text-uppercase fw-bold mb-3">{t('features_and_options')}</div>
                    {options.length > 0 ? (
                      <div className="d-flex flex-wrap gap-2">
                        {options.map((opt, i) => (
                          <span
                            key={`${opt}-${i}`}
                            className="badge badge-light-primary rounded-pill fw-bold px-3 py-2"
                          >
                            {String(opt)}
                          </span>
                        ))}
                      </div>
                    ) : (
                      <span className="text-muted fst-italic fs-6">{t('no_features_listed')}</span>
                    )}
                  </div>
                </div>
              </KTCardBody>
            </KTCard>

            <KTCard className="border-0 shadow-sm rounded-4">
              <KTCardBody className="p-6 p-lg-9">
                <h3 className="fw-bolder text-gray-900 mb-5">{t('media')}</h3>
                {media.length > 0 ? (
                  <div className="d-flex flex-wrap gap-4">
                    {media.map((med: Media) => (
                      <MediaCard key={med.id} item={med} />
                    ))}
                  </div>
                ) : (
                  <div
                    className="rounded-4 border border-dashed border-gray-300 bg-light d-flex flex-column align-items-center justify-content-center text-center px-4 py-8"
                  >
                    <KTIcon iconName="picture" className="fs-2x text-gray-400 mb-2" />
                    <span className="text-muted fs-8 fw-semibold">{t('no_media', { defaultValue: 'No media uploaded' })}</span>
                  </div>
                )}
              </KTCardBody>
            </KTCard>
          </div>

          <div className="col-xl-4">
            <div className="card border-0 shadow-xs rounded-4 h-auto bg-light mb-7">
              <div className="card-body p-5">
                <div className="text-muted fs-8 text-uppercase fw-bold mb-3">{t('advertiser')}</div>
                <div className="d-flex align-items-center gap-3">
                  <div className="symbol symbol-50px symbol-circle">
                    {row.user?.image ? (
                      <img src={row.user.image} alt="" />
                    ) : (
                      <span className="symbol-label bg-light-primary text-primary fw-bolder fs-4">
                        {row.user?.name?.charAt(0)?.toUpperCase() ?? '?'}
                      </span>
                    )}
                  </div>
                  <div className="min-w-0">
                    <div className="fw-bolder text-gray-900 fs-5 text-truncate">{row.user?.name ?? '—'}</div>
                    {row.user?.phone ? (
                      <div className="text-muted fs-7" dir="ltr">
                        {row.user.phone}
                      </div>
                    ) : (
                      <div className="text-muted fs-7">—</div>
                    )}
                    {row.user?.email && <div className="text-muted fs-7 text-truncate">{row.user.email}</div>}
                  </div>
                </div>
              </div>
            </div>

            <div className="card border-0 bg-light rounded-4 shadow-xs mb-7">
              <div className="card-body p-5">
                <h4 className="fw-bolder text-gray-900 mb-5">{t('car_details')}</h4>
                <div className="row g-5">
                  <div className="col-12">
                    <Field label={t('brand')} value={row.car_brand?.name} />
                  </div>
                  <div className="col-12">
                    <Field label={t('type')} value={row.car_type?.name} />
                  </div>
                  <div className="col-12">
                    <Field
                      label={t('fuel_type')}
                      value={row.fuel_type?.label ?? row.fuel_type?.value}
                    />
                  </div>
                  <div className="col-12">
                    <Field label={t('engine_size')} value={row.engine_size} />
                  </div>
                  <div className="col-12">
                    <Field label={t('color')} value={row.color} />
                  </div>
                  <div className="col-12">
                    <Field
                      label={t('region')}
                      value={[row.city?.title, row.region?.title].filter(Boolean).join(', ')}
                    />
                  </div>
                  <div className="col-12">
                    <Field label={t('created_at')} value={createdDate} />
                  </div>
                  <div className="col-12">
                    <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('advisement_status')}</div>
                    <select
                      className="form-select form-select-sm form-select-solid fw-bold fs-7"
                      value={row.status?.value}
                      disabled={!canEdit}
                      onChange={(e) => {
                        if (!canEdit) return;
                        handleStatusChange(e.target.value);
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

            <div className="card border border-dashed border-gray-300 rounded-4 bg-white shadow-xs">
              <div className="card-body p-5">
                <div className="d-flex align-items-center gap-2 mb-4">
                  <KTIcon iconName="bank" className="fs-2 text-primary" />
                  <h4 className="fw-bolder text-gray-900 mb-0">{t('financing_bank')}</h4>
                </div>
                {row.bank ? (
                  <div className="d-flex align-items-center gap-3">
                    {row.bank.logo ? (
                      <img
                        src={row.bank.logo}
                        alt=""
                        className="rounded border border-gray-100"
                        style={{ width: 40, height: 40, objectFit: 'contain' }}
                      />
                    ) : (
                      <div className="symbol symbol-40px symbol-circle">
                        <span className="symbol-label bg-light-primary text-primary">
                          <KTIcon iconName="bank" className="fs-3" />
                        </span>
                      </div>
                    )}
                    <div className="fw-bolder text-gray-900 fs-5">{row.bank.name}</div>
                  </div>
                ) : (
                  <div className="text-muted fs-6 fst-italic">{t('no_financing_bank_selected')}</div>
                )}
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
