import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import {PageTitle} from "@/vendor/metronic/layout/core";
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import {Head} from "@inertiajs/react";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import {Permission} from "@/shared/types/models";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";

type PermissionsGroup = {
  [key: string]: Permission[]
}


type Props = {
  permissions: PermissionsGroup,
};

const Create = ({permissions}: Props) => {
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
          title: t('create'),
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
          <Form permissions={permissions} callback={(form) => {
            form.transform(data => {
              return {
                ...data,
                permissions: Array.from(data.permissions)
              }
            })
            form.post(RoleController.store().url)
          }}/>
        {/*</KTCard>*/}
      </Content>
    </>
  );
}

Create.layout = (page: any) => <MasterLayout children={page}/>;

export default Create;
