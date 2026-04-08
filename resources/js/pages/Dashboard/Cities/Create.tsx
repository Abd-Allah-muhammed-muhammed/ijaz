import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {Region} from "@/types/models";
import {ReactNode} from "react";
import CityController from "@/actions/App/Http/Controllers/Dashboard/CityController";


type Props = {
  regions: Region[]
};

const Create = ({regions}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('cities')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('cities'),
          path: CityController.index().url,
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
        {t('cities')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            regions={regions}
            callback={(form) => {
              form.submit(CityController.store());
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Create;
