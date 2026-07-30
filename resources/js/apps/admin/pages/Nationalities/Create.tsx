import { useTranslation } from 'react-i18next';
import MasterLayout from '@/apps/admin/layouts';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from '@/apps/admin/layouts';
import {Content} from '@/apps/admin/layouts';
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
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
