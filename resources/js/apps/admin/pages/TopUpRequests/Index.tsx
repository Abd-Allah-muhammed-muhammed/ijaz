import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, router } from '@inertiajs/react';
import MasterLayout from '@/apps/admin/layouts/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTCard } from '@/vendor/metronic/helpers';
import {
  CurrencyCell,
  DataTable,
  DateCell,
  StatusBadgeCell,
  type DataTableColumn,
} from '@/shared/components/DataTable';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { TopUpRequest } from '@/shared/types/models';
import TopUpRequestController from '@/actions/Modules/Wallet/Http/Controllers/Dashboard/TopUpRequestController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<TopUpRequest>;
  prams: SearchParams | null;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();

  const searchParams: SearchParams = {
    per_page: prams?.per_page ?? 10,
    ...(prams?.search ? { search: prams.search } : {}),
  };

  const searchParamsChanged = (name: keyof SearchParams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchParams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(TopUpRequestController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<TopUpRequest>[]>(
    () => [
      {
        id: 'id',
        header: '#',
        accessorKey: 'id',
      },
      {
        id: 'name',
        header: t('name'),
        cell: (row) => row.user?.name ?? '—',
      },
      {
        id: 'amount',
        header: t('amount'),
        cell: (row) => <CurrencyCell value={row.amount} options={{ currencyLabel: '' }} />,
      },
      {
        id: 'status',
        header: t('status'),
        cell: (row) => (
          <StatusBadgeCell label={row.status.label} color={row.status.color} />
        ),
      },
      {
        id: 'payment_method',
        header: t('payment_method'),
        cell: (row) => (
          <StatusBadgeCell label={row.payment_method.label} color="secondary" />
        ),
      },
      {
        id: 'payment_status',
        header: t('payment_status'),
        cell: (row) =>
          row.payment_status ? (
            <StatusBadgeCell
              label={row.payment_status.label}
              color={row.payment_status.color}
            />
          ) : (
            <StatusBadgeCell label={t('N/A')} color="secondary" />
          ),
      },
      {
        id: 'created_at',
        header: t('created_at'),
        cell: (row) => <DateCell value={row.created_at} />,
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('top_up_requests')} />
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
        {t('top_up_requests')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-6">
          <DataTable
            columns={columns}
            data={rows.data}
            pagination={rows.meta}
            paginationOnly={['rows', 'prams']}
            searchable
            searchValue={prams?.search ?? ''}
            searchPlaceholder={t('search', { defaultValue: 'Search' })}
            onSearch={(value) => searchParamsChanged('search', value)}
            onRowClick={(row) => {
              router.visit(TopUpRequestController.show(row.id as string).url);
            }}
            actions={() => [
              {
                id: 'show',
                label: t('show'),
                href: (row) => TopUpRequestController.show(row.id as string).url,
              },
            ]}
          />
        </KTCard>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout>{page}</MasterLayout>;

export default Index;
