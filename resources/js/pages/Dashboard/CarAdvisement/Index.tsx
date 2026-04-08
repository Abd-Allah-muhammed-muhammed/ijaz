import { AdvisementStatusEnum, OperationEnum, UsageStatusEnum } from '@/Enums/Advisements';
import { KTIcon } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import Pagination from '@/components/Table/partials/Pagination';
import { CitiesSelect, RegionsSelect, CarBrandsSelect, CarTypesSelect, CarCategoriesSelect } from '@/components/selects';
import { PaginationResource, SelectOption } from '@/types';
import { CarAdvisement } from '@/types/models';
import { Head, router } from '@inertiajs/react';
import { ReactElement, useState } from 'react';
import { Col, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import CarAdvisementCard from './components/CarAdvisementCard';

type Props = {
  rows: PaginationResource<CarAdvisement>;
  prams: SearchPrams | null;
  selects: Selects;
};

type SearchPrams = {
  per_page: number;
  search?: string;
  status?: string;
  operation?: string;
  usage_status?: string;
  car_brand_id?: string | number;
  car_type_id?: string | number;
  car_category_id?: string | number;
  region_id?: string | number;
  city_id?: string | number;
};

type Selects = {
  car_brand: SelectOption | null;
  car_type: SelectOption | null;
  category: SelectOption | null;
  region: SelectOption | null;
  city: SelectOption | null;
};

const Index = ({ rows, prams, selects }: Props) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || { per_page: 10 };
  const [selectsData, setSelectsData] = useState<Selects>(selects);

  const searchPramsChanged = (name: keyof SearchPrams, value: string | number | undefined) => {
    const updatedPrams = { ...searchPrams };
    if (value) {
      updatedPrams[name] = value as never;
    } else {
      delete updatedPrams[name];
    }
    router.reload({
      only: ['rows'],
      data: updatedPrams,
    });
  };

  const publishedCount = rows.data.filter((r) => r.status?.value === AdvisementStatusEnum.PUBLISHED).length;
  const pendingCount = rows.data.filter((r) => r.status?.value === AdvisementStatusEnum.PENDING).length;
  const rejectedCount = rows.data.filter((r) => r.status?.value === AdvisementStatusEnum.REJECTED).length;

  return (
    <>
      <Head title={t('car_advisements')} />
      <PageTitle breadcrumbs={[{ title: '', path: '', isSeparator: true, isActive: false }]}>{t('car_advisements')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        {/* ── Stats ── */}
        <div className="row g-5 g-xl-8 mb-8">
          {[
            { label: t('total_requests'), value: rows.meta.total ?? 0, icon: 'car-2', bg: 'bg-light-primary', color: 'text-primary' },
            { label: t('active_requests'), value: publishedCount, icon: 'check-circle', bg: 'bg-light-success', color: 'text-success' },
            { label: t('pending_requests'), value: pendingCount, icon: 'time', bg: 'bg-light-warning', color: 'text-warning' },
            { label: t('cancelled_requests'), value: rejectedCount, icon: 'cross-circle', bg: 'bg-light-danger', color: 'text-danger' },
          ].map((stat) => (
            <div className="col-xl-3 col-md-6" key={stat.label}>
              <div className="card h-100 border-0 shadow-sm">
                <div className="card-body d-flex align-items-center p-6">
                  <div className="symbol symbol-55px me-5">
                    <span className={`symbol-label ${stat.bg} rounded-3`}>
                      <KTIcon iconName={stat.icon} className={`fs-2hx ${stat.color}`} />
                    </span>
                  </div>
                  <div className="d-flex flex-column">
                    <span className="fs-2hx fw-bolder text-gray-900">{stat.value}</span>
                    <span className="text-muted fw-semibold fs-6">{stat.label}</span>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* ── Filters ── */}
        <div className="card mb-6 border-0 shadow-sm">
          <div className="card-body p-5">
            <div className="d-flex flex-column flex-wrap gap-4">
              <Row>
                <Col md={4}>
                  <div className="d-flex align-items-center position-relative">
                    <KTIcon iconName="magnifier" className="fs-3 position-absolute ms-4 text-gray-500" />
                    <input
                      type="text"
                      defaultValue={searchPrams.search}
                      className="form-control form-control-solid w-100 ps-12"
                      placeholder={t('search')}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                          searchPramsChanged('search', e.currentTarget.value);
                        }
                      }}
                    />
                  </div>
                </Col>
                <Col md={2}>
                  <select
                    className="form-select form-select-solid"
                    defaultValue={searchPrams.status ?? ''}
                    onChange={(e) => searchPramsChanged('status', e.target.value)}
                  >
                    <option value="">{t('status')}</option>
                    <option value={AdvisementStatusEnum.PUBLISHED}>{t('advisement.status.published')}</option>
                    <option value={AdvisementStatusEnum.PENDING}>{t('advisement.status.pending')}</option>
                    <option value={AdvisementStatusEnum.REJECTED}>{t('advisement.status.rejected')}</option>
                    <option value={AdvisementStatusEnum.CLOSED}>{t('advisement.status.closed')}</option>
                  </select>
                </Col>
                <Col md={2}>
                  <select
                    className="form-select form-select-solid"
                    defaultValue={searchPrams.operation ?? ''}
                    onChange={(e) => searchPramsChanged('operation', e.target.value)}
                  >
                    <option value="">{t('operation')}</option>
                    <option value={OperationEnum.SALE}>{t('advisement.operation.sale')}</option>
                    <option value={OperationEnum.RENT}>{t('advisement.operation.rent')}</option>
                    <option value={OperationEnum.BUY}>{t('advisement.operation.buy')}</option>
                  </select>
                </Col>
                <Col md={2}>
                  <select
                    className="form-select form-select-solid"
                    defaultValue={searchPrams.usage_status ?? ''}
                    onChange={(e) => searchPramsChanged('usage_status', e.target.value)}
                  >
                    <option value="">{t('usage_status')}</option>
                    <option value={UsageStatusEnum.NEW}>{t('advisement.usage_status.new')}</option>
                    <option value={UsageStatusEnum.USED}>{t('advisement.usage_status.used')}</option>
                  </select>
                </Col>
              </Row>
              <Row>
                <Col md={2}>
                  <CarBrandsSelect
                    value={selectsData?.car_brand}
                    placeholder={t('car_brand')}
                    onChange={(e) => {
                      searchPramsChanged('car_brand_id', e?.value as string);
                      setSelectsData((prev) => ({ ...prev, car_brand: e, car_type: null }));
                      searchPramsChanged('car_type_id', undefined);
                    }}
                  />
                </Col>
                <Col md={2}>
                  <CarTypesSelect
                    carBrandId={selectsData?.car_brand?.value}
                    value={selectsData?.car_type}
                    placeholder={t('car_type')}
                    onChange={(e) => {
                      searchPramsChanged('car_type_id', e?.value as string);
                      setSelectsData((prev) => ({ ...prev, car_type: e }));
                    }}
                  />
                </Col>
                <Col md={2}>
                  <CarCategoriesSelect
                    value={selectsData?.category}
                    placeholder={t('car_category')}
                    onChange={(e) => {
                      searchPramsChanged('car_category_id', e?.value as string);
                      setSelectsData((prev) => ({ ...prev, category: e }));
                    }}
                  />
                </Col>
                <Col md={2}>
                  <RegionsSelect
                    value={selectsData?.region}
                    placeholder={t('region')}
                    onChange={(e) => {
                      searchPramsChanged('region_id', e?.value as string);
                      setSelectsData((prev) => ({ ...prev, region: e, city: null }));
                      searchPramsChanged('city_id', undefined);
                    }}
                  />
                </Col>
                <Col md={2}>
                  <CitiesSelect
                    regionId={selectsData?.region?.value}
                    value={selectsData?.city}
                    placeholder={t('city')}
                    onChange={(e) => {
                      searchPramsChanged('city_id', e?.value as string);
                      setSelectsData((prev) => ({ ...prev, city: e }));
                    }}
                  />
                </Col>
                <Col md={2} className="d-flex align-items-center justify-content-end">
                   <select
                    className="form-select form-select-solid w-100px"
                    defaultValue={searchPrams.per_page ?? 10}
                    onChange={(e) => searchPramsChanged('per_page', Number(e.target.value))}
                  >
                    <option value={10}>10</option>
                    <option value={25}>25</option>
                    <option value={50}>50</option>
                  </select>
                </Col>
              </Row>
            </div>
          </div>
        </div>

        {/* ── Card Grid ── */}
        {rows.data.length === 0 ? (
          <div className="card border-0 shadow-sm">
            <div className="card-body py-20 text-center">
              <KTIcon iconName="car-2" className="fs-5x mb-5 text-gray-300" />
              <p className="text-muted fw-semibold fs-5">{t('no_data')}</p>
            </div>
          </div>
        ) : (
          <div className="row g-6 g-xl-8">
            {rows.data.map((row) => (
              <div className="col-xl-3 col-lg-6" key={`advisement-${row.id}`}>
                <CarAdvisementCard row={row} />
              </div>
            ))}
          </div>
        )}

        {/* ── Pagination ── */}
        <div className="mt-8">
          <Pagination paginationMeta={rows.meta} preserveScroll />
        </div>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
