import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import ProviderTypeController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController";
import {PermissionsGroup} from "@/pages/Dashboard/Roles/types";
import { Category } from '@/types/models';


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
