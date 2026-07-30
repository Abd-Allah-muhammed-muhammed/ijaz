import { useTranslation } from 'react-i18next';
import MasterLayout from '@/apps/admin/layouts';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from '@/apps/admin/layouts';
import {Content} from '@/apps/admin/layouts';
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import ProviderTypeController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController";
import {PermissionsGroup} from "@/apps/admin/pages/Roles/types";
import { Category } from '@/shared/types/models';


type Props = {
  permissions: PermissionsGroup,
  categories?: Category[]
};

const Create = ({permissions, categories}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('provider_types')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('provider_types'),
          path: ProviderTypeController.index().url,
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
        {t('provider_types')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            callback={(form) => {
              form.submit(ProviderTypeController.store());
            }}
            categories={categories}
          />
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Create;
