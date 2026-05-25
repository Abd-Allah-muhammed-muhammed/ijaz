import { KTCard } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import PropertyCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyCategoryController';
import { PropertyCategory } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  category: PropertyCategory;
  categories: PropertyCategory[];
};

const Edit = ({ categories, category }: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('property_categories')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('property_categories'),
            path: PropertyCategoryController.index().url,
            isSeparator: false,
            isActive: false,
          },
          {
            title: t('edit'),
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}
      >
        {t('property_categories')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            categories={categories}
            category={category}
            callback={(form) => {
              const route = PropertyCategoryController.update(category.id as number);
              form.transform((data) => {
                return {
                  ...data,
                  _method: route.method,
                };
              });
              form.post(route.url);
            }}
          />
        </KTCard>
      </Content>
    </>
  );
};
Edit.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Edit;
