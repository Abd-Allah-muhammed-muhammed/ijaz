import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {City, Provider, ProviderType, Region} from "@/types/models";
import Form from "./Form";
import ProviderController from "@/actions/App/Http/Controllers/Dashboard/ProviderController";
import {zodValidate} from "@/helpers/general";
import {Inputs} from "@/pages/Dashboard/Providers/Validation";


type Props = {
  row: Provider,
  types: ProviderType[]
  regions: Region[],
  cities: City[]
};
const Edit = ({row, types, regions, cities}: Props) => {
  const { t } = useTranslation();
  console.log(row)
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
          title: t('edit'),
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
          row={row}
          types={types}
          regions={regions}
          cities={cities}
          backUrl={ProviderController.index().url}
          callback={(form, requiredFiles) => {
            const {method, url} = ProviderController.update(row.id as number);
            form.transform(data => {
              return {
                ...data,
                _method: method,
              };
            })

            // if (!zodValidate(Inputs, form, {requiredFiles, id: row.id})) {
            //   return;
            // }
            form.post(url)
          }}/>
      </Content>
    </>
  );
}
Edit.layout = (page: any) => <MasterLayout children={page}/>;

export default Edit;
