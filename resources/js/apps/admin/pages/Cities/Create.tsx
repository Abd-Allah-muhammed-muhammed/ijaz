import { useTranslation } from 'react-i18next';
import MasterLayout from "@/apps/admin/layouts/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {Region} from "@/shared/types/models";
import {ReactNode} from "react";
import CityController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/CityController";


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
