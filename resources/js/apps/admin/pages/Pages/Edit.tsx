import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import {PageTitle} from "@/vendor/metronic/layout/core";
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import {Head} from "@inertiajs/react";
import {Page} from "@/shared/types/models";
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import PageController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController";
import {zodValidate} from "@/shared/helpers/general";
import {Inputs} from "@/apps/admin/pages/Pages/validation";


type Props = {
  row: Page,
};

const Edit = ({row}: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('pages')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('pages'),
          path: PageController.index().url,
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
        {t('pages')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="border-0 shadow-sm rounded-4">
          <div className="card-body p-6 p-lg-9">
            <Form
              row={row}
              callback={(form) => {
                if (zodValidate(Inputs, form)) {
                  form.submit(PageController.update(row));
                }
              }}
            />
          </div>
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Edit;
