import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import {Admin, Role} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import AdminController from "@/actions/App/Http/Controllers/Dashboard/AdminController";


type Props = {
  admin: Admin,
  roles: Role[],

};

const Edit = ({roles, admin}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('admins')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('admins'),
          path: RoleController.index().url,
          isSeparator: false,
          isActive: false,
        },
        {
          title: t('edit'),
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('admins')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            roles={roles}
            admin={admin}
            image={admin.image}
            callback={(form) => {
              const route = AdminController.update(admin.id as number);
              form.transform((data) => {
                return {
                  ...data,
                  _method: route.method,
                }
              })
              form.post(route.url)
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: any) => <MasterLayout children={page}/>;

export default Edit;
