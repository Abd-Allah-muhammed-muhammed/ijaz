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
import {OrderStatusEnum} from "@/Enums/Order";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";
import { ORDERS_PAGE_TITLE_KEY } from '@/shared/i18n/orders-label';
import {
  EmptyState,
  PageFilterBar,
  SectionCard,
  type PageFilterField,
} from '@/shared/components/ui';

type Props = {
  rows: PaginationResource<Order>,
  prams: SearchPrams | null;
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

  const filterFields: PageFilterField[] = [
    {
      name: 'search',
      type: 'search',
      value: searchPrams.search,
      placeholder: t('search'),
    },
    {
      name: 'status',
      type: 'select',
      value: searchPrams.status ?? '',
      options: [
        { value: '', label: t('all') },
        ...Object.values(OrderStatusEnum).map((status) => ({
          value: status,
          label: t(status),
        })),
      ],
    },
    {
      name: 'date_from',
      type: 'date',
      value: searchPrams.date_from ?? '',
      placeholder: 'Date From',
    },
    {
      name: 'date_to',
      type: 'date',
      value: searchPrams.date_to ?? '',
      placeholder: 'Date To',
    },
  ];

  const handleFilterChange = (name: string, value: string) => {
    if (
      name === 'search'
      || name === 'status'
      || name === 'date_from'
      || name === 'date_to'
    ) {
      searchPramsChanged(name, value);
    }
  };

  return (
    <>
      <Head title={t(ORDERS_PAGE_TITLE_KEY)}/>
      <PageTitle breadcrumbs={[
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t(ORDERS_PAGE_TITLE_KEY)}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <PageFilterBar
          filters={filterFields}
          onFilterChange={handleFilterChange}
        />
        {rows.data.length === 0 ? (
          <SectionCard>
            <EmptyState
              icon={<KTIcon iconName="basket" className="fs-5x mb-5 text-gray-300" />}
              title={t('no_orders_found')}
            />
          </SectionCard>
        ) : (
          <Row className='row'>
            {rows.data.map((row) => (
              <Col sm={6} xl={3} key={'order-' + row.id}>
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


Index.layout = (page: any) => {
  return <ProviderLayout {...page.props}>{page}</ProviderLayout>
}

export default Index
