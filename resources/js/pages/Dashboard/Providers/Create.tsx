import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import Form from "./Form";
import {City, ProviderType, Region} from "@/types/models";
import ProviderController from "@/actions/App/Http/Controllers/Dashboard/ProviderController";
import {CategoryFormData} from "@/pages/Dashboard/Providers/types";
import {zodValidate} from "@/helpers/general";
import {Inputs} from "@/pages/Dashboard/Providers/Validation";


type Props = {
  types: ProviderType[]
  regions: Region[],
  cities: City[]
};

const Create = ({types, regions, cities}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('providers')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('providers'),
          path: ProviderController.index().url,
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
        {t('providers')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <Form
          types={types}
          regions={regions}
          cities={cities}
          backUrl={ProviderController.index().url}
          callback={(form,files) => {
            if (!zodValidate(Inputs, form, {requiredFiles: files})) {
              return ;
            }
            form.submit(ProviderController.store());
          }}/>
      </Content>
    </>
  );
}
Create.layout = (page: any) => <MasterLayout children={page}/>;
export default Create;
