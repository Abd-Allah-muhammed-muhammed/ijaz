import { Head } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { PageTitle } from "@/vendor/metronic/layout/core";
import { ToolbarWrapper } from "@/vendor/metronic/layout/components/toolbar";
import { Content } from "@/vendor/metronic/layout/components/content";
import { KTIcon } from "@/vendor/metronic/helpers";
import Pagination from "@/shared/components/Table/partials/Pagination";
import { PaginationResource } from "@/shared/types";
import { Order } from "@/shared/types/models";
import OrderController from "@/actions/Modules/Orders/Http/Controllers/Dashboard/OrderController";
import OrderCard from "@/shared/components/order/order-card";
import OrderStats from "@/shared/components/order/OrderStats";
import { Col, Row } from "react-bootstrap";
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { OrderStatusEnum } from "@/Enums/Order";
import { applyFilterParam, visitWithFilters } from "@/shared/lib/filters";


type Props = {
  rows: PaginationResource<Order>,
  prams: SearchPrams | null;
  stats: {
    total: number;
    active: number;
    pending: number;
    completed: number;
    cancelled: number;
  };
};

type SearchPrams = {
  per_page: number;
  search: string;
  status?: string;
  date_from?: string;
  date_to?: string;
};
const Index = (
  {
    rows,
    prams,
    stats,
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
    visitWithFilters(OrderController.index().url, next, { only: ['rows', 'prams'] });
  };
  return (
    <>
      <Head title={t('orders')} />
      <PageTitle breadcrumbs={[
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('orders')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <OrderStats stats={stats} />
        <div className='d-flex flex-wrap flex-stack mb-6'>
          <h3 className='fw-bolder my-2'>
            <div className='d-flex align-items-center position-relative my-1'>
              <KTIcon iconName='magnifier' className='fs-1 position-absolute ms-6' />
              <input
                type='text'
                defaultValue={searchPrams.search}
                data-kt-user-table-filter='search'
                className='form-control  ps-14'
                placeholder='Search'
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    searchPramsChanged('search', e.currentTarget.value)
                  }
                }}
              />
            </div>
          </h3>

          <div className='d-flex align-items-center my-2 gap-2'>
            <div className='w-200px'>
                <select
                name='status'
                data-control='select2'
                data-hide-search='true'
                className='form-select form-select-white form-select-sm'
                defaultValue={searchPrams.status}
                onChange={(e) => searchPramsChanged('status', e.target.value)}
                >
                <option value=''>{t('all')}</option>
                {Object.values(OrderStatusEnum).map((status) => (
                    <option key={status} value={status}>
                    {t(status)}
                    </option>
                ))}
                </select>
            </div>
            <div className='w-150px'>
                <input
                    type='date'
                    className='form-control form-control-white form-control-sm'
                    placeholder='Date From'
                    defaultValue={searchPrams.date_from}
                    onChange={(e) => searchPramsChanged('date_from', e.target.value)}
                />
            </div>
            <div className='w-150px'>
                <input
                    type='date'
                    className='form-control form-control-white form-control-sm'
                    placeholder='Date To'
                    defaultValue={searchPrams.date_to}
                    onChange={(e) => searchPramsChanged('date_to', e.target.value)}
                />
            </div>
          </div>
        </div>
        <Row>
          {rows.data.map((row) => (
            <Col sm={6} xl={3} className="transition-all duration-300 mb-5" key={'order-' + row.id}>
              <OrderCard url={OrderController.show(row.id as string).url} order={row} />
            </Col>
          ))}
        </Row>
        <Pagination paginationMeta={rows.meta} preserveScroll />
      </Content>
    </>
  )
}


Index.layout = (page: React.ReactNode) => {
  return <MasterLayout>{page}</MasterLayout>
}

export default Index
