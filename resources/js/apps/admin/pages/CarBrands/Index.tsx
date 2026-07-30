import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import FormCheckInput from 'react-bootstrap/FormCheck';
import MasterLayout from '@/apps/admin/layouts/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import { DataTable, type DataTableColumn } from '@/shared/components/DataTable';
import usePermissions from '@/shared/hooks/use-permissions';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { CarBrand } from '@/shared/types/models';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<CarBrand>;
  prams: SearchParams | null;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canEdit = hasPermission('edit carBrands');
  const canCreate = hasPermission('create carBrands');
  const canDelete = hasPermission('delete carBrands');

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
    visitWithFilters(CarBrandController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<CarBrand>[]>(
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
                  CarBrandController.updateStatus(row.id as number).url,
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
      <Head title={t('car_brands')} />
      <PageTitle breadcrumbs={[]}>{t('car_brands')}</PageTitle>
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
                    router.visit(CarBrandController.edit(row.id as number).url);
                  }
                : undefined
            }
            toolbar={
              canCreate ? (
                <Link href={CarBrandController.create().url} className="btn btn-primary">
                  <KTIcon iconName="plus" className="fs-2" />
                </Link>
              ) : null
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => CarBrandController.edit(row.id as number).url,
                visible: canEdit,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                visible: canDelete,
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(CarBrandController.destroy(row.id as number).url, {
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
