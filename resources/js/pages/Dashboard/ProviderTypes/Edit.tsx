import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import { Category, ProviderType } from '@/types/models';
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import ProviderTypeController from "@/actions/App/Http/Controllers/Dashboard/ProviderTypeController";


type Props = {
  row: ProviderType,
  categories: Category[],
};

const Edit = ({row, categories}: Props) => {
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
          title: t('edit'),
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
            row={row}
            callback={(form) => {
              const route = ProviderTypeController.update(row.id as number);
              form.transform((data) => {
                return {
                  ...data,
                  _method: 'PUT',
                }
              })
              form.post(route.url)
            }}
            categories={categories}
          />
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Edit;
