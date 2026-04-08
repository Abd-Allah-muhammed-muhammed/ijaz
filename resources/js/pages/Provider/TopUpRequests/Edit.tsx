import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {Category} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import CategoryController from "@/actions/App/Http/Controllers/Dashboard/CategoryController";


type Props = {
  category: Category,
  categories: Category[]

};

const Edit = ({categories, category}: Props) => {
  const { t } = useTranslation();
  console.log(category)
  return (
    <>
      <Head title={t('categories')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('categories'),
          path: CategoryController.index().url,
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
        {t('categories')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard className="p-4">
          <Form
            categories={categories}
            category={category}
            image={category.icon}
            callback={(form) => {
              const route = CategoryController.update(category.id as number);
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
