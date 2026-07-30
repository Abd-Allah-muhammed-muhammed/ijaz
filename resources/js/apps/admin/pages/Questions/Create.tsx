import { useTranslation } from 'react-i18next';
import MasterLayout from '@/apps/admin/layouts';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from '@/apps/admin/layouts';
import {Content} from '@/apps/admin/layouts';
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import {zodValidate} from "@/shared/helpers/general";
import {Inputs} from "@/apps/admin/pages/Questions/validation";
import QuestionController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/QuestionController";


type Props = {};

const Create = ({}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('questions')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('questions'),
          path: QuestionController.index().url,
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
        {t('questions')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            callback={(form) => {
              if (zodValidate(Inputs, form)) {
                form.submit(QuestionController.store());
              }
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Create;
