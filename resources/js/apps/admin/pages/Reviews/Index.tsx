import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, router } from '@inertiajs/react';
import MasterLayout from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { KTCard } from '@/vendor/metronic/helpers';
import { DataTable, DateCell, type DataTableColumn } from '@/shared/components/DataTable';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { Review } from '@/shared/types/models';
import ReviewController from '@/actions/Modules/Reviews/Http/Controllers/Dashboard/ReviewController';

type SearchParams = {
  per_page?: number;
  search?: string;
  rating?: number | string;
  reviewer_type?: string;
  reviewee_type?: string;
};

type Props = {
  rows: PaginationResource<Review>;
  prams: SearchParams | null;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();

  const searchParams: SearchParams = {
    per_page: prams?.per_page ?? 10,
    ...(prams?.search ? { search: prams.search } : {}),
    ...(prams?.rating != null && prams.rating !== '' ? { rating: prams.rating } : {}),
    ...(prams?.reviewer_type ? { reviewer_type: prams.reviewer_type } : {}),
    ...(prams?.reviewee_type ? { reviewee_type: prams.reviewee_type } : {}),
  };

  const searchParamsChanged = (name: keyof SearchParams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchParams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(ReviewController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<Review>[]>(
    () => [
      {
        id: 'reviewer',
        header: t('reviewer'),
        cell: (row) => (
          <span>
            {row.reviewer?.name || '—'}
            {row.reviewer_type ? ` (${row.reviewer_type})` : ''}
          </span>
        ),
      },
      {
        id: 'reviewee',
        header: t('reviewee'),
        cell: (row) => (
          <span>
            {row.reviewee?.name || '—'}
            {row.reviewee_type ? ` (${row.reviewee_type})` : ''}
          </span>
        ),
      },
      {
        id: 'rating',
        header: t('rating'),
        accessorKey: 'rating',
      },
      {
        id: 'comment',
        header: t('comment'),
        accessorKey: 'comment',
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
      <Head title={t('reviews')} />
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
        {t('reviews')}
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
            actions={() => [
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(ReviewController.destroy(row.id as number).url);
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
