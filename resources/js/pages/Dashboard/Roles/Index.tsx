import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {Role} from "@/types/models";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import Swal from 'sweetalert2'
import withReactContent from 'sweetalert2-react-content'
import ConfirmAction from "@/components/Table/partials/confirm-action";

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
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.reload({
      data:searchPrams,
      only: ['rows'],
    });
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
