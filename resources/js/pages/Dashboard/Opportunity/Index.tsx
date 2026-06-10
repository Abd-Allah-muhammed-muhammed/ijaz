import { KTIcon } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import Pagination from '@/components/Table/partials/Pagination';
import { PaginationResource } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ReactElement, useEffect, useRef, useState } from 'react';
import { Col, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import OpportunityCard, { OpportunityListItem } from './components/OpportunityCard';

type StatusOption = {
  value: string;
  label: string;
  color: string;
};

type Props = {
  rows: PaginationResource<OpportunityListItem>;
  prams: SearchPrams | null;
  selects: { statuses: StatusOption[] };
};

type SearchPrams = {
  per_page: number;
  search?: string;
  status?: string;
};

const Index = ({ rows, prams, selects }: Props) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || { per_page: 10 };
  const [searchValue, setSearchValue] = useState(searchPrams.search ?? '');
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const searchPramsChanged = (name: keyof SearchPrams, value: string | number | undefined) => {
    const updatedPrams = { ...searchPrams };
    if (value) {
      updatedPrams[name] = value as never;
    } else {
      delete updatedPrams[name];
    }
    router.reload({
      only: ['rows', 'prams'],
      data: updatedPrams,
      preserveScroll: true,
    });
  };

  useEffect(() => {
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }
    debounceRef.current = setTimeout(() => {
      if (searchValue !== (searchPrams.search ?? '')) {
        searchPramsChanged('search', searchValue || undefined);
      }
    }, 400);

    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, [searchValue]);

  const activeCount = rows.data.filter((r) => ['new', 'offer_accepted', 'in_progress'].includes(r.status?.value)).length;
  const endedCount = rows.data.filter((r) => r.status?.value === 'ended').length;
  const cancelledCount = rows.data.filter((r) => r.status?.value === 'cancelled').length;

  return (
    <>
      <Head title={t('opportunities')} />
      <PageTitle breadcrumbs={[{ title: '', path: '', isSeparator: true, isActive: false }]}>{t('opportunities')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="row g-5 g-xl-8 mb-8">
          {[
            { label: t('total_requests'), value: rows.meta.total ?? 0, icon: 'briefcase', bg: 'bg-light-primary', color: 'text-primary' },
            { label: t('active_requests'), value: activeCount, icon: 'time', bg: 'bg-light-warning', color: 'text-warning' },
            { label: t('opportunity.status.ended'), value: endedCount, icon: 'check-circle', bg: 'bg-light-success', color: 'text-success' },
            { label: t('opportunity.status.cancelled'), value: cancelledCount, icon: 'cross-circle', bg: 'bg-light-danger', color: 'text-danger' },
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

        <div className="card mb-6 border-0 shadow-sm">
          <div className="card-body p-5">
            <Row className="g-4">
              <Col md={4}>
                <div className="d-flex align-items-center position-relative">
                  <KTIcon iconName="magnifier" className="fs-3 position-absolute ms-4 text-gray-500" />
                  <input
                    type="text"
                    value={searchValue}
                    className="form-control form-control-solid w-100 ps-12"
                    placeholder={t('search')}
                    onChange={(e) => setSearchValue(e.target.value)}
                  />
                </div>
              </Col>
              <Col md={3}>
                <select
                  className="form-select form-select-solid"
                  defaultValue={searchPrams.status ?? ''}
                  onChange={(e) => searchPramsChanged('status', e.target.value || undefined)}
                >
                  <option value="">{t('status')}</option>
                  {selects.statuses.map((status) => (
                    <option key={status.value} value={status.value}>
                      {status.label}
                    </option>
                  ))}
                </select>
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

        {rows.data.length === 0 ? (
          <div className="card border-0 shadow-sm">
            <div className="card-body py-20 text-center">
              <KTIcon iconName="briefcase" className="fs-5x mb-5 text-gray-300" />
              <p className="text-muted fw-semibold fs-5">{t('no_opportunities')}</p>
            </div>
          </div>
        ) : (
          <div className="row g-6 g-xl-8">
            {rows.data.map((row) => (
              <div className="col-xl-3 col-lg-6" key={`opportunity-${row.id}`}>
                <OpportunityCard row={row} />
              </div>
            ))}
          </div>
        )}

        <div className="mt-8">
          <Pagination paginationMeta={rows.meta} preserveScroll only={['rows', 'prams']} />
        </div>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
