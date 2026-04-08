import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import {zodValidate} from "@/helpers/general";
import {Inputs} from "@/pages/Dashboard/Pages/validation";
import QuestionController from "@/actions/App/Http/Controllers/Dashboard/QuestionController";


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
