import { useTranslation } from 'react-i18next';
import MasterLayout from "@/apps/admin/layouts/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import { Category, CategoryFeesType } from '@/shared/types/models';
import {KTCard} from "@/vendor/metronic/helpers";
import Form from "./Form";
import {ReactNode} from "react";
import CategoryController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/CategoryController";


type Props = {
  category: Category,
  categories: Category[],
  fees_types: CategoryFeesType[],
};

const Edit = ({categories, category, fees_types}: Props) => {
  const { t } = useTranslation();

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
            fees_types={fees_types}
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
