import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {Admin} from "@/types/models";
import AdminController from "@/actions/App/Http/Controllers/Dashboard/AdminController";
import UserInfo from "@/components/User/user-info";
import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import ConfirmAction from "@/components/Table/partials/confirm-action";


type Props = {
  rows: PaginationResource<Admin>,
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
  const swal = withReactContent(Swal)
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.get(AdminController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('admins')}/>
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
        {t('admins')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table<Admin>
            name='admins'
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
                render: (row) => (
                  <UserInfo user={{
                    name: row.name,
                    image: row.image,
                    email: row.email,
                  }}/>
                )
              },
              {
                title: t('role'),
                property: 'roles',
                render: (row) => {
                  if (row.roles?.length > 0) {
                    return row.roles[0].name;
                  }
                  return '--';
                }
              },
              {
                title: t('phone'),
                property: 'phone',
              },
              {
                title: t('job'),
                property: 'job',
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-admin-${row.id}`}
                    href={AdminController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-admin-${row.id}`}
                    callback={() => {
                      router.delete(AdminController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={AdminController.create().url}
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

export default Index
