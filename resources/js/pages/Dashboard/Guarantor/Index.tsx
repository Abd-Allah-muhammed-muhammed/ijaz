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
import GuarantorCard, { GuarantorListItem } from './components/GuarantorCard';

type StatusOption = {
  value: string;
  label: string;
  color: string;
};

type TypeOption = {
  value: string;
  label: string;
  color: string;
};

type Props = {
  rows: PaginationResource<GuarantorListItem>;
  prams: SearchPrams | null;
  selects: { statuses: StatusOption[]; types: TypeOption[] };
  stats: {
    total: number;
    pending_admin: number;
    in_progress: number;
    overdue: number;
    ended: number;
  };
};

type SearchPrams = {
  per_page: number;
  search?: string;
  status?: string;
  type?: string;
  date_from?: string;
  date_to?: string;
};

const Index = ({ rows, prams, selects, stats }: Props) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || { per_page: 15 };
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
      only: ['rows', 'prams', 'stats'],
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

  const statCards = [
    { label: t('guarantor.total'), value: stats.total, icon: 'shield', bg: 'bg-light-primary', color: 'text-primary' },
    { label: t('guarantor.status.pending_admin'), value: stats.pending_admin, icon: 'plus-square', bg: 'bg-light-info', color: 'text-info' },
    { label: t('guarantor.status.in_progress'), value: stats.in_progress, icon: 'time', bg: 'bg-light-success', color: 'text-success' },
    { label: t('guarantor.status.overdue'), value: stats.overdue, icon: 'information-2', bg: 'bg-light-danger', color: 'text-danger' },
    { label: t('guarantor.status.ended'), value: stats.ended, icon: 'check-circle', bg: 'bg-light-success', color: 'text-success' },
  ];

  return (
    <>
      <Head title={t('guarantor.module_title')} />
      <PageTitle breadcrumbs={[{ title: '', path: '', isSeparator: true, isActive: false }]}>{t('guarantor.module_title')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="row g-5 g-xl-8 mb-8">
          {statCards.map((stat) => (
            <div className="col-xl col-md-6 col-12" key={stat.label}>
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
              <Col md={3}>
                <div className="d-flex align-items-center position-relative">
                  <KTIcon iconName="magnifier" className="fs-3 position-absolute ms-4 text-gray-500" />
                  <input
                    type="text"
                    value={searchValue}
                    className="form-control form-control-solid w-100 ps-12"
                    placeholder={t('guarantor.search_placeholder')}
                    onChange={(e) => setSearchValue(e.target.value)}
                  />
                </div>
              </Col>
              <Col md={2}>
                <select
                  className="form-select form-select-solid"
                  defaultValue={searchPrams.status ?? ''}
                  onChange={(e) => searchPramsChanged('status', e.target.value || undefined)}
                >
                  <option value="">{t('guarantor.filter_by_status')}</option>
                  {selects.statuses.map((status) => (
                    <option key={status.value} value={status.value}>
                      {status.label}
                    </option>
                  ))}
                </select>
              </Col>
              <Col md={2}>
                <select
                  className="form-select form-select-solid"
                  defaultValue={searchPrams.type ?? ''}
                  onChange={(e) => searchPramsChanged('type', e.target.value || undefined)}
                >
                  <option value="">{t('guarantor.filter_by_type')}</option>
                  {selects.types.map((type) => (
                    <option key={type.value} value={type.value}>
                      {type.label}
                    </option>
                  ))}
                </select>
              </Col>
              <Col md={2}>
                <input
                  type="date"
                  className="form-control form-control-solid"
                  defaultValue={searchPrams.date_from ?? ''}
                  onChange={(e) => searchPramsChanged('date_from', e.target.value || undefined)}
                  placeholder={t('guarantor.date_from')}
                />
              </Col>
              <Col md={2}>
                <input
                  type="date"
                  className="form-control form-control-solid"
                  defaultValue={searchPrams.date_to ?? ''}
                  onChange={(e) => searchPramsChanged('date_to', e.target.value || undefined)}
                  placeholder={t('guarantor.date_to')}
                />
              </Col>
              <Col md={1} className="d-flex align-items-center justify-content-end">
                <select
                  className="form-select form-select-solid w-100px"
                  defaultValue={searchPrams.per_page ?? 15}
                  onChange={(e) => searchPramsChanged('per_page', Number(e.target.value))}
                >
                  <option value={15}>15</option>
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
              <KTIcon iconName="shield" className="fs-5x mb-5 text-gray-300" />
              <p className="text-muted fw-semibold fs-5">{t('guarantor.no_guarantors')}</p>
            </div>
          </div>
        ) : (
          <div className="row g-6 g-xl-8">
            {rows.data.map((row) => (
              <div className="col-xl-3 col-lg-6" key={`guarantor-${row.id}`}>
                <GuarantorCard row={row} />
              </div>
            ))}
          </div>
        )}

        <div className="mt-8">
          <Pagination paginationMeta={rows.meta} preserveScroll only={['rows', 'prams', 'stats']} />
        </div>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
