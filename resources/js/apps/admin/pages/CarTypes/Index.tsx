import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import FormCheckInput from 'react-bootstrap/FormCheck';
import MasterLayout from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import { DataTable, type DataTableColumn } from '@/shared/components/DataTable';
import usePermissions from '@/shared/hooks/use-permissions';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { CarType } from '@/shared/types/models';
import CarTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarTypeController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<CarType>;
  prams: SearchParams | null;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canEdit = hasPermission('edit carTypes');
  const canCreate = hasPermission('create carTypes');
  const canDelete = hasPermission('delete carTypes');

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
    visitWithFilters(CarTypeController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<CarType>[]>(
    () => [
      {
        id: 'image',
        header: t('image'),
        cell: (row) => (
          <div className="symbol symbol-50px me-2">
            <img src={row.image_url || '/media/avatars/blank.png'} alt="" />
          </div>
        ),
      },
      {
        id: 'name',
        header: t('name'),
        accessorKey: 'name',
      },
      {
        id: 'brand',
        header: t('car_brand'),
        cell: (row) => row.brand?.name ?? '---',
      },
      {
        id: 'is_active',
        header: t('is_active'),
        cell: (row) => (
          <div
            className="form-check form-switch form-check-custom form-check-solid me-10"
            onClick={(event) => event.stopPropagation()}
          >
            <FormCheckInput
              className="h-20px w-30px"
              type="checkbox"
              defaultChecked={row.is_active}
              onClick={() => {
                router.put(
                  CarTypeController.updateStatus(row.id as number).url,
                  {
                    is_active: !row.is_active,
                  },
                  {
                    preserveScroll: true,
                    preserveState: true,
                  },
                );
              }}
            />
          </div>
        ),
      },
    ],
    [t],
  );

  return (
    <>
      <Head title={t('car_types')} />
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
        {t('car_types')}
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
            onRowClick={
              canEdit
                ? (row) => {
                    router.visit(CarTypeController.edit(row.id as number).url);
                  }
                : undefined
            }
            toolbar={
              canCreate ? (
                <Link href={CarTypeController.create().url} className="btn btn-primary">
                  <KTIcon iconName="plus" className="fs-2" />
                </Link>
              ) : null
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => CarTypeController.edit(row.id as number).url,
                visible: canEdit,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                visible: canDelete,
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(CarTypeController.destroy(row.id as number).url, {
                    only: ['rows'],
                  });
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
