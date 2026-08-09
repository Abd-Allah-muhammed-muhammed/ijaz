import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";
import {Head} from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {KTIcon} from "@/vendor/metronic/helpers";
import Pagination from "@/shared/components/Table/partials/Pagination";
import {PaginationResource} from "@/shared/types";
import {Order} from "@/shared/types/models";
import OrderController from "@/actions/Modules/Orders/Http/Controllers/Provider/OrderController";
import OrderCard from "@/shared/components/order/order-card";
import {Col, Row} from "react-bootstrap";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";

type Props = {
  rows: PaginationResource<Order>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  period?: string;
};

const PERIOD_OPTIONS = [
  { value: '30', labelKey: 'period_30_days' },
  { value: '90', labelKey: 'period_90_days' },
  { value: '180', labelKey: 'period_6_months' },
  { value: '365', labelKey: 'period_1_year' },
] as const;

const Recommended = (
  {
    rows,
    prams,
  }: Props
) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
  };
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchPrams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(OrderController.new().url, next, { only: ['rows', 'prams'] });
  };
  return (
    <>
      <Head title={t('providers')}/>
      <PageTitle breadcrumbs={[
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('providers')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <div className='d-flex flex-wrap flex-stack mb-6'>
          <h3 className='fw-bolder my-2'>
            <div className='d-flex align-items-center position-relative my-1'>
              <KTIcon iconName='magnifier' className='fs-1 position-absolute ms-6'/>
              <input
                type='text'
                defaultValue={searchPrams.search}
                data-kt-user-table-filter='search'
                className='form-control  ps-14'
                placeholder={t('search')}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    searchPramsChanged('search', e.currentTarget.value)
                  }
                }}
              />
            </div>
          </h3>

          <div className='d-flex align-items-center my-2'>
            <div className=' me-5'>
              <select
                name='period'
                data-control='select2'
                data-hide-search='true'
                className='form-select form-select-white form-select-sm'
                defaultValue={searchPrams.period ?? '30'}
                onChange={(e) => searchPramsChanged('period', e.target.value)}
              >
                {PERIOD_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {t(option.labelKey)}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
        {rows.data.length === 0 ? (
          <div className="card border-0 shadow-sm">
            <div className="card-body py-20 text-center">
              <KTIcon iconName="basket" className="fs-5x mb-5 text-gray-300" />
              <p className="text-muted fw-semibold fs-5">{t('no_orders_found')}</p>
            </div>
          </div>
        ) : (
          <Row>
            {rows.data.map((row) => (
              <Col sm={12} md={6} lg={4} xl={3} key={'order-' + row.id}>
                <OrderCard url={OrderController.show(row.id as string).url} order={row}/>
              </Col>
            ))}
          </Row>
        )}
        <Pagination paginationMeta={rows.meta} preserveScroll/>
      </Content>
    </>
  )
}


Recommended.layout = (page: any) => {
  return <ProviderLayout {...page.props}>{page}</ProviderLayout>
}

export default Recommended
