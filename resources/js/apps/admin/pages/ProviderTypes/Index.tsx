import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import {
  DataTable,
  StatusBadgeCell,
  type DataTableColumn,
} from '@/shared/components/DataTable';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { ProviderType } from '@/shared/types/models';
import ProviderTypeController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<ProviderType>;
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
    visitWithFilters(ProviderTypeController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<ProviderType>[]>(
    () => [
      {
        id: 'name',
        header: t('name'),
        accessorKey: 'name',
      },
      {
        id: 'providers_count',
        header: t('providers_count'),
        cell: (row) => (
          <StatusBadgeCell label={String(row.providers_count ?? 0)} color="primary" />
        ),
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('provider_types')} />
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
        {t('provider_types')}
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
              router.visit(ProviderTypeController.edit(row.id as number).url);
            }}
            toolbar={
              <Link href={ProviderTypeController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
              </Link>
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => ProviderTypeController.edit(row.id as number).url,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(ProviderTypeController.destroy(row.id as number).url);
                },
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
