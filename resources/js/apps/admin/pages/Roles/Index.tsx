import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head, Link} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/vendor/metronic/helpers";
import Table, {LinkAction} from "@/shared/components/Table";
import {PaginationResource} from "@/shared/types";
import {Role} from "@/shared/types/models";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import Swal from 'sweetalert2'
import withReactContent from 'sweetalert2-react-content'
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";

type Props = {
  rows: PaginationResource<Role>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
};
const Index = (
  {
    rows,
    prams,
  }: Props
) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
  };
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchPrams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(RoleController.index().url, next, { only: ['rows'] });
  };
  return (
    <>
      <Head title={t('roles')}/>
      <PageTitle breadcrumbs={[
        // {
        //   title: 'User Management',
        //   path: '/apps/user-management/users',
        //   isSeparator: false,
        //   isActive: false,
        // },
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('roles')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Role>
            name='roles'
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: t('name'),
                property: 'name',
              },
              {
                title: t('users_count'),
                property: 'users_count',
                render: (row) => row.users_count || 0,
              }
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-role-${row.id}`}
                    href={RoleController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-role-${row.id}`}
                    callback={() => {
                      router.delete(RoleController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={RoleController.create().url}
                className="btn btn-primary"
              >
                <KTIcon iconName='plus' className='fs-2'/>
              </Link>
            }
          />
        </KTCard>
        {/*{itemIdForUpdate !== undefined && <UserEditModal/>}*/}
      </Content>
    </>
  );
}

Index.layout = (page: any) => <MasterLayout children={page}/>;

export default Index;
