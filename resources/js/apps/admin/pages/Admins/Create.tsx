import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from "@/vendor/metronic/layout/core";
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { Head } from "@inertiajs/react";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import { KTCard } from "@/vendor/metronic/helpers";
import Form from "./Form";
import { Role } from "@/shared/types/models";
import AdminController from "@/actions/App/Http/Controllers/Dashboard/AdminController";


type Props = {
  roles: Role[]
};

const Create = ({ roles }: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('admins')} />
      <PageTitle breadcrumbs={[
        {
          title: t('admins'),
          path: AdminController.index().url,
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
        {t('admins')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            roles={roles}
            image={'/media/avatars/blank.png'}
            callback={(form) => {
              form.submit(AdminController.store());
            }} />
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: any) => <MasterLayout children={page} />;
export default Create;
