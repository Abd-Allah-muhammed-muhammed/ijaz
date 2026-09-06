import { type ReactElement, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import {
  STATEMENT_PAGE_SIZE,
  formatListDate,
} from '@/shared/lib/formatters';
import Pagination from '@/shared/components/Table/partials/Pagination';
import {
  EmptyState,
  PageFilterBar,
  StackedDataTable,
  StatusBadge,
  type PageFilterField,
  type StackedDataTableColumn,
} from '@/shared/components/ui';
import type { PaginationResource } from '@/shared/types';
import type { Provider, WalletTransaction } from '@/shared/types/models';
import WalletBalanceHero from '@/apps/provider/pages/Auth/Profile/wallet/components/WalletBalanceHero';
import WalletMetrics from '@/apps/provider/pages/Auth/Profile/wallet/components/WalletMetrics';
import type { MetricTileData } from '@/apps/provider/types/metric-tile-data';

type SearchParams = {
  per_page: number;
  search: string;
};

export type WalletPageProps = {
  transactions: PaginationResource<WalletTransaction>;
  provider: Provider;
  prams: SearchParams | null;
};

function formatAmountCell(row: WalletTransaction, pendingLabel: string) {
  const amount = Number(row.amount) || 0;
  const formatted = amount.toFixed(2);

  if (row.is_pending) {
    return (
      <span className="d-flex align-items-center gap-2 flex-wrap">
        <span className="fw-bold text-gray-500">{formatted}</span>
        <StatusBadge label={pendingLabel} color="warning" className="fs-8" />
      </span>
    );
  }

  if (row.is_credit) {
    return <span className="fw-bold text-success">+{formatted}</span>;
  }

  return <span className="fw-bold text-gray-800">-{formatted}</span>;
}

const Wallet = ({ transactions, provider, prams }: WalletPageProps) => {
  const { t, i18n } = useTranslation();
  const searchParams: SearchParams = prams || {
    per_page: STATEMENT_PAGE_SIZE,
    search: '',
  };

  const searchParamsChanged = (name: keyof SearchParams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchParams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(AuthController.statements().url, next, {
      only: ['transactions', 'prams'],
    });
  };

  const filters: PageFilterField[] = [
    {
      name: 'search',
      type: 'search',
      value: prams?.search || '',
      placeholder: t('search'),
    },
  ];

  const metrics: MetricTileData[] = [
    { value: provider.wallet?.pending_debit, label: t('wallet_on_hold') },
    {
      value: provider.wallet?.amount_in_transfer,
      label: t('wallet_being_transferred'),
    },
    { value: provider.wallet?.total_earning, label: t('wallet_total_earned') },
  ];

  const columns: StackedDataTableColumn<WalletTransaction>[] = useMemo(
    () => [
      {
        id: 'operation',
        header: t('operation'),
        grow: true,
        mobile: 'title',
        cell: (row) => row.description || row.operation_type,
      },
      {
        id: 'amount',
        header: t('amount'),
        widthClassName: 'w-150px',
        mobile: 'meta',
        cell: (row) => formatAmountCell(row, t('pending')),
      },
      {
        id: 'status',
        header: t('status'),
        widthClassName: 'w-125px',
        mobile: 'badge',
        cell: (row) => (
          <StatusBadge status={row.transfer_status} className="fs-8" />
        ),
      },
      {
        id: 'date',
        header: t('date'),
        widthClassName: 'w-125px',
        mobile: 'meta',
        cell: (row) => formatListDate(row.created_at, i18n.language),
      },
    ],
    [t, i18n.language],
  );

  return (
    <>
      <Head title={t('wallet')} />
      <PageTitle breadcrumbs={[]}>{t('wallet')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <WalletBalanceHero
          balance={provider.wallet?.balance}
          currencyLabel={t('SAR')}
          locale={i18n.language}
        />
        <WalletMetrics metrics={metrics} />
        <div className="mb-4">
          <PageFilterBar
            filters={filters}
            onFilterChange={(name, value) => {
              if (name === 'search') {
                searchParamsChanged('search', value);
              }
            }}
            className="mb-0"
          />
        </div>
        <StackedDataTable
          rows={transactions.data}
          columns={columns}
          getRowKey={(row) => row.id}
          emptyState={
            <EmptyState
              title={t('no_matching_records_found')}
              compact
            />
          }
        />
        <div className="mt-4">
          <Pagination
            paginationMeta={transactions.meta}
            only={['transactions', 'prams']}
            preserveScroll
          />
        </div>
      </Content>
    </>
  );
};

Wallet.layout = (page: ReactElement) => (
  <ProviderLayout>{page}</ProviderLayout>
);

export default Wallet;
