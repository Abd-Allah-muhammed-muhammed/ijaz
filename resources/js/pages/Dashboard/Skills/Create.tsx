import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import RoleController from "@/actions/App/Http/Controllers/Dashboard/RoleController";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {Category, Role} from "@/types/models";
import AdminController from "@/actions/App/Http/Controllers/Dashboard/AdminController";
import {ReactNode} from "react";
import CategoryController from "@/actions/App/Http/Controllers/Dashboard/CategoryController";
import SkillController from "@/actions/App/Http/Controllers/Dashboard/SkillController";


type Props = {
  categories: Category[]
};

const Create = ({categories}: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('skills')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('skills'),
          path: RoleController.index().url,
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
        {t('skills')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            categories={categories}
            callback={(form) => {
              form.submit(SkillController.store());
            }}/>
        </KTCard>
      </Content>
    </>
  );
}
Create.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Create;
