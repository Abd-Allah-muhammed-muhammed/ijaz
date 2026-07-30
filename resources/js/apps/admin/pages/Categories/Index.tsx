import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import { DataTable, type DataTableColumn } from '@/shared/components/DataTable';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { Category } from '@/shared/types/models';
import CategoryController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/CategoryController';
import { CategoryFeesTypeEnum } from '@/Enums/Marketplace';

type SearchParams = {
  per_page?: number;
  search?: string;
  parent_id?: number;
};

type Props = {
  rows: PaginationResource<Category>;
  prams: SearchParams | null;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();

  const searchParams: SearchParams = {
    per_page: prams?.per_page ?? 10,
    ...(prams?.search ? { search: prams.search } : {}),
    ...(prams?.parent_id ? { parent_id: prams.parent_id } : {}),
  };

  const searchParamsChanged = (name: keyof SearchParams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchParams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(CategoryController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<Category>[]>(
    () => [
      {
        id: 'title',
        header: t('title'),
        accessorKey: 'title',
      },
      {
        id: 'children_count',
        header: t('children'),
        cell: (row) => (
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              searchParamsChanged('parent_id', row.id as number);
            }}
            className="btn btn-icon btn-light-twitter btn-sm"
          >
            {row.children_count || 0}
          </button>
        ),
      },
      {
        id: 'fees',
        header: t('fees'),
        cell: (row) =>
          `${row.fees_type.value === CategoryFeesTypeEnum.INHERITED ? row.fees_type.label : row.fees || 0}`,
      },
      {
        id: 'fees_type',
        header: t('fees_type'),
        cell: (row) => `${row.fees_type.label}`,
      },
    ],
    [t, prams?.per_page, prams?.search, prams?.parent_id],
  );

  return (
    <>
      <Head title={t('category')} />
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
        {t('category')}
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
              router.visit(CategoryController.edit(row.id as number).url);
            }}
            toolbar={
              <Link href={CategoryController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
              </Link>
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => CategoryController.edit(row.id as number).url,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(CategoryController.destroy(row.id as number).url);
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
