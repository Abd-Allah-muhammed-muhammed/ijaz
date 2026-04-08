import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import {Permission, Role} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";

type PermissionsGroup = {
  [key: string]: Permission[]
}


type Props = {
  permissions: PermissionsGroup,
  role: Role,

};

const Edit = ({permissions, role}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('roles')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('roles'),
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
        {t('roles')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        {/*<KTCard className="p-4">*/}
          <Form permissions={permissions} role={role} callback={(form) => {
            form.transform(data => {
              return {
                ...data,
                permissions: Array.from(data.permissions)
              }
            })
            form.submit(RoleController.update(role.id as number))
          }}/>
        {/*</KTCard>*/}
      </Content>
    </>
  );
}

Edit.layout = (page: any) => <MasterLayout children={page}/>;

export default Edit;
