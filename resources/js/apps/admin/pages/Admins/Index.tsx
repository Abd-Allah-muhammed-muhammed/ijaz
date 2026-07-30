import { type ReactElement, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, Link, router } from '@inertiajs/react';
import MasterLayout from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import {
  AvatarCell,
  DataTable,
  DateCell,
  StatusBadgeCell,
  type DataTableColumn,
} from '@/shared/components/DataTable';
import usePermissions from '@/shared/hooks/use-permissions';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';
import type { PaginationResource } from '@/shared/types';
import type { Admin } from '@/shared/types/models';
import AdminController from '@/actions/App/Http/Controllers/Dashboard/AdminController';

type SearchParams = {
  per_page?: number;
  search?: string;
};

type Props = {
  rows: PaginationResource<Admin>;
  prams: SearchParams | null;
};

/**
 * Admins index — pilot DataTable migration (reference for remaining CRUD indexes).
 *
 * Note: admins resource has no Show route (`except(['show'])`). Row click opens Edit
 * when the user has `edit admins`; otherwise the row is not clickable.
 */
const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canEdit = hasPermission('edit admins');
  const canCreate = hasPermission('create admins');
  const canDelete = hasPermission('delete admins');

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
    visitWithFilters(AdminController.index().url, next, { only: ['rows', 'prams'] });
  };

  const columns = useMemo<DataTableColumn<Admin>[]>(
    () => [
      {
        id: 'name',
        header: t('name'),
        cell: (row) => (
          <AvatarCell name={row.name} image={row.image} description={row.email} />
        ),
      },
      {
        id: 'role',
        header: t('role'),
        cell: (row) => {
          if (row.root) {
            return <StatusBadgeCell label={t('root', { defaultValue: 'Root' })} color="warning" />;
          }

          const roleName = row.roles?.[0]?.name;

          if (!roleName) {
            return <span className="text-muted-foreground">—</span>;
          }

          return <StatusBadgeCell label={roleName} color="primary" />;
        },
      },
      {
        id: 'phone',
        header: t('phone'),
        accessorKey: 'phone',
      },
      {
        id: 'job',
        header: t('job'),
        accessorKey: 'job',
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
      <Head title={t('admins')} />
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
        {t('admins')}
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
                    router.visit(AdminController.edit(row.id as number).url);
                  }
                : undefined
            }
            toolbar={
              canCreate ? (
                <Link href={AdminController.create().url} className="btn btn-primary">
                  <KTIcon iconName="plus" className="fs-2" />
                </Link>
              ) : null
            }
            actions={() => [
              {
                id: 'edit',
                label: t('edit'),
                href: (row) => AdminController.edit(row.id as number).url,
                visible: canEdit,
              },
              {
                id: 'delete',
                label: t('delete'),
                variant: 'destructive',
                visible: canDelete,
                confirm: { type: 'swal' },
                onSelect: (row) => {
                  router.delete(AdminController.destroy(row.id as number).url);
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
