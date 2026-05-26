import { KTIcon } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { PageTitle } from '@/_metronic/layout/core';
import InstituteAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/InstituteAdvisementController';
import { AdvisementStatusEnum } from '@/Enums/Advisements';
import { InstituteAdvisement, Media } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
  row: InstituteAdvisement;
};

const ShowInstituteAdvisement = ({ row }: Props) => {
  const { t } = useTranslation();

  const handleStatusChange = (newStatus: string) => {
    router.put(InstituteAdvisementController.update(row.id as number).url, { status: newStatus }, { preserveScroll: true });
  };

  const tCfg = row.type?.color
    ? { badge: `badge-light-${row.type.color}`, color: `text-${row.type.color}` }
    : { badge: 'badge-light-secondary', color: 'text-gray-500' };

  const hasDiscount = row.discounted_price != null && row.price != null && row.discounted_price < row.price;

  const formatDate = (d?: string | null): string => (d ? new Date(d).toLocaleDateString() : '-');

  return (
    <Content>
      <Head title={`${t('view_institute_advisement')} #${row.id}`} />
      <PageTitle breadcrumbs={[{ title: t('institute_advisements'), path: InstituteAdvisementController.index().url, isSeparator: false, isActive: false }]}>
        {t('view_institute_advisement')}
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
                    background: row.image_url ? `url(${row.image_url}) center/cover no-repeat` : 'linear-gradient(135deg, #f5f8fa 0%, #e4e6ef 100%)',
                    width: '160px',
                    height: '160px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  {!row.image_url && <KTIcon iconName="book" className="fs-5x text-gray-400" />}

                  <div className="position-absolute align-items-center justify-content-between d-flex inset-e-0 top-0 w-100 gap-2 p-2">
                    <span className="badge bg-body fw-bold fs-8 text-gray-800 shadow-sm">#{row.id}</span>
                  </div>
                </div>
              </div>

              <div className="grow">
                <div className="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                  <div className="d-flex flex-column">
                    <div className="d-flex align-items-center mb-2 gap-2 flex-wrap">
                      <h3 className="fs-2 fw-bolder mb-0 text-gray-900">{row.title}</h3>
                      {row.type && <span className={`badge ${tCfg.badge} fw-bolder fs-8`}>{row.type.label}</span>}
                      {row.study_type && <span className={`badge badge-light-${row.study_type.color} fw-bolder fs-8`}>{row.study_type.label}</span>}
                      {row.study_level && <span className={`badge badge-light-${row.study_level.color} fw-bolder fs-8`}>{row.study_level.label}</span>}
                      {row.status && <span className={`badge badge-light-${row.status.color} fw-bolder fs-8`}>{row.status.label}</span>}
                    </div>

                    <div className="d-flex fw-bold fs-6 mb-4 flex-wrap gap-4 pe-2">
                      {row.specialization && (
                        <div className="d-flex align-items-center hover-primary text-gray-500">
                          <KTIcon iconName="book" className="fs-6 text-primary me-1" />
                          {row.specialization.title}
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
                      {row.address && (
                        <div className="d-flex align-items-center hover-primary text-gray-500">
                          <KTIcon iconName="geolocation" className="fs-6 text-warning me-1" />
                          {row.address}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="d-flex my-4 gap-3">
                    <Link href={InstituteAdvisementController.index().url} className="btn btn-sm btn-light">
                      <KTIcon iconName="arrow-left" className="fs-6 px-1" />
                      {t('back')}
                    </Link>
                    <button
                      type="button"
                      aria-label={t('delete')}
                      className="btn btn-sm btn-icon btn-light-danger"
                      onClick={() => {
                        if (window.confirm(t('are_you_sure_delete'))) {
                          router.delete(InstituteAdvisementController.show(row.id as number).url);
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
                        <div className="d-flex align-items-center flex-wrap gap-2">
                          {hasDiscount ? (
                            <>
                              <span className="text-gray-500 text-decoration-line-through fs-5">
                                {Number(row.price).toLocaleString()}
                              </span>
                              <div className={`fs-2 fw-bolder text-danger`}>
                                {Number(row.discounted_price).toLocaleString()} <span className="fs-6 text-gray-600">{t('SAR')}</span>
                              </div>
                            </>
                          ) : row.price != null ? (
                            <div className={`fs-2 fw-bolder ${tCfg.color}`}>
                              {Number(row.price).toLocaleString()} <span className="fs-6 text-gray-600">{t('SAR')}</span>
                            </div>
                          ) : (
                            <span className="fs-5 fst-italic text-gray-600">{t('on_contact')}</span>
                          )}
                        </div>
                        <div className="fw-bold fs-6 text-gray-500">{t('price')}</div>
                      </div>

                      {(row.days_count != null || row.hours_count != null) && (
                        <div className="min-w-125px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                          <div className="d-flex align-items-center gap-2">
                            <KTIcon iconName="time" className="fs-3 text-primary" />
                            <div className="fs-2 fw-bolder text-gray-900">
                              {row.days_count != null && `${row.days_count} ${t('days')}`}
                              {row.days_count != null && row.hours_count != null && ' / '}
                              {row.hours_count != null && `${row.hours_count} ${t('hours')}`}
                            </div>
                          </div>
                          <div className="fw-bold fs-6 text-gray-500">{t('duration')}</div>
                        </div>
                      )}

                      {row.study_type && (
                        <div className="min-w-100px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                          <div className="d-flex align-items-center gap-2">
                            <KTIcon iconName="teacher" className={`fs-3 text-${row.study_type.color}`} />
                            <div className="fs-2 fw-bolder text-gray-900">{row.study_type.label}</div>
                          </div>
                          <div className="fw-bold fs-6 text-gray-500">{t('study_type')}</div>
                        </div>
                      )}

                      {row.type && (
                        <div className="min-w-100px me-3 mb-3 rounded border border-dashed border-gray-300 px-4 py-3">
                          <div className="d-flex align-items-center gap-2">
                            <KTIcon iconName="abstract-26" className="fs-3 text-info" />
                            <div className="fs-2 fw-bolder text-gray-900">{row.type.label}</div>
                          </div>
                          <div className="fw-bold fs-6 text-gray-500">{t('institute_type')}</div>
                        </div>
                      )}
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
                  <h3 className="fw-bolder m-0">{t('description')}</h3>
                </div>
              </div>
              <div className="card-body pt-4">
                <div className="fs-5 fw-semibold mb-8 whitespace-pre-wrap text-gray-700">
                  {row.description || <span className="text-muted fst-italic">{t('no_description')}</span>}
                </div>

                {row.goals && (
                  <>
                    <div className="separator separator-dashed my-8"></div>
                    <h3 className="fw-bolder m-0 mb-5">{t('goals')}</h3>
                    <div className="fs-5 fw-semibold mb-4 whitespace-pre-wrap text-gray-700">{row.goals}</div>
                  </>
                )}

                {row.payment_notes && (
                  <>
                    <div className="separator separator-dashed my-8"></div>
                    <h3 className="fw-bolder m-0 mb-5">{t('payment_notes')}</h3>
                    <div className="fs-5 fw-semibold mb-4 whitespace-pre-wrap text-gray-700">{row.payment_notes}</div>
                  </>
                )}

                <div className="separator separator-dashed my-8"></div>

                <h3 className="fw-bolder m-0 mb-5">{t('important_dates')}</h3>
                <div className="row g-4">
                  <div className="col-md-6">
                    <div className="bg-light-primary rounded-3 p-4">
                      <div className="text-muted fw-semibold fs-7 mb-1">{t('registration_start')}</div>
                      <div className="fw-bolder fs-5 text-gray-900">{formatDate(row.registration_start)}</div>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="bg-light-warning rounded-3 p-4">
                      <div className="text-muted fw-semibold fs-7 mb-1">{t('registration_end')}</div>
                      <div className="fw-bolder fs-5 text-gray-900">{formatDate(row.registration_end)}</div>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="bg-light-info rounded-3 p-4">
                      <div className="text-muted fw-semibold fs-7 mb-1">{t('study_start')}</div>
                      <div className="fw-bolder fs-5 text-gray-900">{formatDate(row.study_start)}</div>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="bg-light-success rounded-3 p-4">
                      <div className="text-muted fw-semibold fs-7 mb-1">{t('study_end')}</div>
                      <div className="fw-bolder fs-5 text-gray-900">{formatDate(row.study_end)}</div>
                    </div>
                  </div>
                </div>

                <div className="separator separator-dashed my-8"></div>

                <h3 className="fw-bolder m-0 mb-5">{t('links')}</h3>
                <div className="d-flex flex-column gap-3">
                  {row.website && (
                    <a href={row.website} target="_blank" rel="noopener noreferrer" className="d-flex align-items-center gap-2 text-primary fw-semibold">
                      <KTIcon iconName="external-link" className="fs-5" />
                      <span>{t('website')}: {row.website}</span>
                    </a>
                  )}
                  {row.registration_url && (
                    <a href={row.registration_url} target="_blank" rel="noopener noreferrer" className="d-flex align-items-center gap-2 text-primary fw-semibold">
                      <KTIcon iconName="external-link" className="fs-5" />
                      <span>{t('registration_url')}: {row.registration_url}</span>
                    </a>
                  )}
                  {row.course_url && (
                    <a href={row.course_url} target="_blank" rel="noopener noreferrer" className="d-flex align-items-center gap-2 text-primary fw-semibold">
                      <KTIcon iconName="external-link" className="fs-5" />
                      <span>{t('course_url')}: {row.course_url}</span>
                    </a>
                  )}
                  {row.quality_url && (
                    <a href={row.quality_url} target="_blank" rel="noopener noreferrer" className="d-flex align-items-center gap-2 text-primary fw-semibold">
                      <KTIcon iconName="external-link" className="fs-5" />
                      <span>{t('quality_url')}: {row.quality_url}</span>
                    </a>
                  )}
                  {!row.website && !row.registration_url && !row.course_url && !row.quality_url && (
                    <span className="text-muted fst-italic">{t('no_links_listed')}</span>
                  )}
                </div>
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
                  <h3 className="fw-bolder m-0">{t('details')}</h3>
                </div>
              </div>
              <div className="card-body pt-4">
                <div className="d-flex flex-column gap-5">
                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('specialization')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.specialization?.title ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('institute_type')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.type?.label ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('study_type')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.study_type?.label ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('study_level')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.study_level?.label ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('price')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.price != null ? Number(row.price).toLocaleString() : '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('discounted_price')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.discounted_price != null ? Number(row.discounted_price).toLocaleString() : '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('days_count')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.days_count ?? '-'}</span>
                  </div>
                  <div className="separator separator-dashed"></div>

                  <div className="d-flex align-items-center justify-content-between">
                    <span className="text-muted fw-semibold">{t('hours_count')}</span>
                    <span className="fs-6 fw-bold text-gray-900">{row.hours_count ?? '-'}</span>
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
                      aria-label={t('advisement_status')}
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

ShowInstituteAdvisement.layout = (page: ReactElement) => <MasterLayout children={page} />;
export default ShowInstituteAdvisement;
