import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import NationalityController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/NationalityController";


type Props = {};

const Create = ({}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('nationalities')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('nationalities'),
          path: NationalityController.index().url,
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
        {t('nationalities')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            callback={(form) => {
              form.submit(NationalityController.store());
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Create;
