import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import MasterLayout from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import { DataTable, type DataTableColumn } from '@/shared/components/DataTable';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { City } from '@/shared/types/models';
import CityController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/CityController';

type SearchParams = {
  per_page?: number;
  search?: string;
  region_id?: number;
};

type Props = {
  rows: PaginationResource<City>;
  prams: SearchParams | null;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();

  const searchParams: SearchParams = {
    per_page: prams?.per_page ?? 10,
    ...(prams?.search ? { search: prams.search } : {}),
    ...(prams?.region_id ? { region_id: prams.region_id } : {}),
  };

  const searchParamsChanged = (name: keyof SearchParams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchParams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(CityController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<City>[]>(
    () => [
      {
        id: 'title',
        header: t('title'),
        accessorKey: 'title',
      },
      {
        id: 'region',
        header: t('region'),
        cell: (row) => row.region?.title || '—',
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('cities')} />
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
        {t('cities')}
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
              router.visit(CityController.edit(row.id as number).url);
            }}
            toolbar={
              <Link href={CityController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
              </Link>
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => CityController.edit(row.id as number).url,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(CityController.destroy(row.id as number).url);
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
