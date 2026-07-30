import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {Question} from "@/shared/types/models";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import {zodValidate} from "@/shared/helpers/general";
import {Inputs} from "@/apps/admin/pages/Questions/validation";
import QuestionController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/QuestionController";


type Props = {
  row: Question,

};

const Edit = ({row}: Props) => {
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
          title: t('edit'),
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
            row={row}
            callback={(form) => {
              if (zodValidate(Inputs, form)) {
                form.submit(QuestionController.update(row.id as number));
              }

            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Edit;
