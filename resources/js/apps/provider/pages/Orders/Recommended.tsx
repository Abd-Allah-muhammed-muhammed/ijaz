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
  period?: string;
};

const PERIOD_OPTIONS = [
  { value: '30', labelKey: 'period_30_days' },
  { value: '90', labelKey: 'period_90_days' },
  { value: '180', labelKey: 'period_6_months' },
  { value: '365', labelKey: 'period_1_year' },
] as const;

const Recommended = ({ rows, prams }: Props) => {
  const { t, i18n } = useTranslation();
  const { filters, onFilterChange } = useOrderFilters<SearchPrams>({
    prams,
    defaults: { per_page: 10, search: '' },
    url: OrderController.new().url,
  });

  const filterFields: PageFilterField[] = [
    {
      name: 'search',
      type: 'search',
      value: filters.search,
      placeholder: t('search'),
    },
    {
      name: 'period',
      type: 'select',
      value: filters.period ?? '30',
      options: PERIOD_OPTIONS.map((option) => ({
        value: option.value,
        label: t(option.labelKey),
      })),
    },
  ];

  return (
    <>
      <Head title={t('new_orders')} />
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
        {t('new_orders')}
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
          <div className="d-flex flex-column gap-3 mb-5">
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
          </div>
        )}
        <Pagination paginationMeta={rows.meta} preserveScroll />
      </Content>
    </>
  );
};

Recommended.layout = (page: ReactElement) => <ProviderLayout>{page}</ProviderLayout>;

export default Recommended;
