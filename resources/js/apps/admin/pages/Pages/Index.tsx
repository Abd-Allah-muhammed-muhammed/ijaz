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
import type { Page } from '@/shared/types/models';
import PageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<Page>;
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
    visitWithFilters(PageController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<Page>[]>(
    () => [
      {
        id: 'title',
        header: t('title'),
        accessorKey: 'title',
      },
      {
        id: 'slug',
        header: t('slug'),
        accessorKey: 'slug',
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('pages')} />
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
        {t('pages')}
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
              router.visit(PageController.edit(row).url);
            }}
            toolbar={
              <Link href={PageController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
              </Link>
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => PageController.edit(row).url,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(PageController.destroy(row).url);
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
