import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTIcon } from '@/vendor/metronic/helpers';
import Pagination from '@/shared/components/Table/partials/Pagination';
import { PaginationResource } from '@/shared/types';
import { Order } from '@/shared/types/models';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import { OrderStatusEnum } from '@/Enums/Order';
import { ORDERS_PAGE_TITLE_KEY } from '@/shared/i18n/orders-label';
import {
  EmptyState,
  PageFilterBar,
  SectionCard,
  type PageFilterField,
} from '@/shared/components/ui';
import { useOrderFilters } from '@/apps/provider/pages/Orders/hooks/use-order-filters';
import OrderListRow from '@/apps/provider/pages/Orders/components/OrderListRow';
import {
  formatOrderBudgetRange,
  formatOrderListTime,
  formatOrderLocation,
} from '@/apps/provider/pages/Orders/components/order-list-row-utils';
import type { ReactElement } from 'react';

type Props = {
  rows: PaginationResource<Order>;
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  status?: string;
  date_from?: string;
  date_to?: string;
};

const Index = ({ rows, prams }: Props) => {
  const { t, i18n } = useTranslation();
  const { filters, onFilterChange } = useOrderFilters<SearchPrams>({
    prams,
    defaults: { per_page: 10, search: '' },
    url: OrderController.index().url,
  });

  const filterFields: PageFilterField[] = [
    {
      name: 'search',
      type: 'search',
      value: filters.search,
      placeholder: t('search'),
    },
    {
      name: 'status',
      type: 'select',
      value: filters.status ?? '',
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
      value: filters.date_from ?? '',
      placeholder: 'Date From',
    },
    {
      name: 'date_to',
      type: 'date',
      value: filters.date_to ?? '',
      placeholder: 'Date To',
    },
  ];

  return (
    <>
      <Head title={t(ORDERS_PAGE_TITLE_KEY)} />
      <PageTitle
        breadcrumbs={[
          {
            title: '',
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}
      >
        {t(ORDERS_PAGE_TITLE_KEY)}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <PageFilterBar filters={filterFields} onFilterChange={onFilterChange} />
        {rows.data.length === 0 ? (
          <SectionCard>
            <EmptyState
              icon={<KTIcon iconName="basket" className="fs-5x mb-5 text-gray-300" />}
              title={t('no_orders_found')}
            />
          </SectionCard>
        ) : (
          <SectionCard className="mb-5" bodyClassName="card-body p-0">
            {rows.data.map((row) => (
              <OrderListRow
                key={row.id}
                href={OrderController.show(row.id as string).url}
                title={row.title}
                amountLabel={formatOrderBudgetRange(row.budget_start, row.budget_end)}
                description={row.description}
                locationLabel={formatOrderLocation(row)}
                timeLabel={formatOrderListTime(row.created_at, i18n.language)}
                offersCount={row.offers_count ?? 0}
                status={row.status}
              />
            ))}
          </SectionCard>
        )}
        <Pagination paginationMeta={rows.meta} preserveScroll />
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <ProviderLayout>{page}</ProviderLayout>;

export default Index;
