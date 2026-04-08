import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {Category, Skill} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import CategoryController from "@/actions/App/Http/Controllers/Dashboard/CategoryController";
import SkillController from "@/actions/App/Http/Controllers/Dashboard/SkillController";


type Props = {
  row: Skill,
  categories: Category[]

};

const Edit = ({categories, row}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('skills')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('skills'),
          path: SkillController.index().url,
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
        {t('skills')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            categories={categories}
            row={row}
            callback={(form) => {
              const route = SkillController.update(row.id as number);
              form.transform((data) => {
                return {
                  ...data,
                  _method: route.method,
                }
              })
              form.post(route.url)
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Edit.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Edit;
