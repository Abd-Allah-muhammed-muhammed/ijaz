import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import MasterLayout from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { KTCard } from '@/vendor/metronic/helpers';
import { DataTable, type DataTableColumn } from '@/shared/components/DataTable';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { Order, TicketSupport } from '@/shared/types/models';
import SupportController from '@/actions/Modules/Support/Http/Controllers/Dashboard/SupportController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type TicketRow = TicketSupport<Order>;

type Props = {
  rows: PaginationResource<TicketRow>;
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
    visitWithFilters(SupportController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<TicketRow>[]>(
    () => [
      {
        id: 'title',
        header: t('title'),
        accessorKey: 'title',
      },
      {
        id: 'message',
        header: t('message'),
        accessorKey: 'message',
      },
      {
        id: 'operation',
        header: t('operation'),
        cell: (row) =>
          row.operation ? (
            <Link
              href={row.operation.show_url}
              className="text-primary"
              onClick={(event) => event.stopPropagation()}
            >
              {row.operation.type}(#{row.operation.id})
            </Link>
          ) : (
            t('N/A')
          ),
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('support_tickets')} />
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
        {t('tickets')}
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
              router.visit(SupportController.show(row.id as number).url);
            }}
            actions={() => [
              {
                id: 'show',
                label: t('show'),
                href: (row) => SupportController.show(row.id as number).url,
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
