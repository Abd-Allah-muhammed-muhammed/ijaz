import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTIcon } from '@/vendor/metronic/helpers';
import Pagination from '@/shared/components/Table/partials/Pagination';
import { PaginationResource } from '@/shared/types';
import { OrderOffer } from '@/shared/types/models';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import { OfferStatusEnum } from '@/Enums/Order';
import { formatCurrency } from '@/shared/lib/formatters';
import { getOfferStatusBadgeClass } from '@/apps/provider/pages/Orders/order-show-utils';
import {
  EmptyState,
  PageFilterBar,
  SectionCard,
  type PageFilterField,
} from '@/shared/components/ui';
import { useOrderFilters } from '@/apps/provider/pages/Orders/hooks/use-order-filters';
import OrderListRow from '@/apps/provider/pages/Orders/components/OrderListRow';
import { formatOrderListTime } from '@/apps/provider/pages/Orders/components/order-list-row-utils';
import type { ReactElement } from 'react';

type Props = {
  rows: PaginationResource<OrderOffer>;
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  status?: string;
};

const Offers = ({ rows, prams }: Props) => {
  const { t, i18n } = useTranslation();
  const currencyLabel = t('SAR');
  const { filters, onFilterChange } = useOrderFilters<SearchPrams>({
    prams,
    defaults: { per_page: 10, search: '' },
    url: OrderController.offers().url,
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
        ...Object.values(OfferStatusEnum).map((status) => ({
          value: status,
          label: (t as (key: string) => string)(status),
        })),
      ],
    },
  ];

  return (
    <>
      <Head title={t('offers')} />
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
        {t('offers')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <PageFilterBar filters={filterFields} onFilterChange={onFilterChange} />

        {rows.data.length === 0 ? (
          <SectionCard>
            <EmptyState
              icon={<KTIcon iconName="price-tag" className="fs-5x mb-5 text-gray-300" />}
              title={t('no_offers')}
            />
          </SectionCard>
        ) : (
          <SectionCard className="mb-5" bodyClassName="card-body p-0">
            {rows.data.map((row) => {
              const orderTitle = row.order?.title?.trim() || t('Order ID');
              const offerBadge = getOfferStatusBadgeClass(row.status?.value);

              return (
                <OrderListRow
                  key={row.id}
                  href={OrderController.show(row.order_id).url}
                  title={orderTitle}
                  amountLabel={formatCurrency(row.price, {
                    locale: i18n.language,
                    currencyLabel,
                    maximumFractionDigits: 2,
                    minimumFractionDigits: 0,
                  })}
                  description={row.description}
                  timeLabel={formatOrderListTime(row.created_at, i18n.language)}
                  statusLabel={row.status?.label}
                  statusColorClass={offerBadge}
                />
              );
            })}
          </SectionCard>
        )}
        <Pagination paginationMeta={rows.meta} preserveScroll />
      </Content>
    </>
  );
};

Offers.layout = (page: ReactElement) => <ProviderLayout>{page}</ProviderLayout>;

export default Offers;
