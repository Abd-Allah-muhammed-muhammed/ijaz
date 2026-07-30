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
import type { Banner } from '@/shared/types/models';
import BannerController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/BannerController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<Banner>;
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
    visitWithFilters(BannerController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<Banner>[]>(
    () => [
      {
        id: 'image',
        header: t('image'),
        cell: (row) => (
          <img
            src={row.image}
            alt={String(row.id)}
            className="h-12 w-12 rounded object-cover"
          />
        ),
      },
      {
        id: 'link',
        header: t('link'),
        cell: (row) =>
          row.link ? (
            <a
              href={row.link}
              target="_blank"
              rel="noreferrer"
              className="text-primary"
              onClick={(event) => event.stopPropagation()}
            >
              {row.link}
            </a>
          ) : (
            '—'
          ),
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('banners')} />
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
        {t('banners')}
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
              router.visit(BannerController.edit(row.id as number).url);
            }}
            toolbar={
              <Link href={BannerController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
              </Link>
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => BannerController.edit(row.id as number).url,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(BannerController.destroy(row.id as number).url);
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
