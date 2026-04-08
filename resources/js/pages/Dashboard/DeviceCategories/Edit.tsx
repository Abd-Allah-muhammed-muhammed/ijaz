import { KTCard } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import DeviceCategoryController from '@/actions/App/Http/Controllers/Dashboard/DeviceCategoryController';
import { Category } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  category: Category;
  categories: Category[];
};

const Edit = ({ categories, category }: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('device_categories')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('device_categories'),
            path: DeviceCategoryController.index().url,
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
        {t('device_categories')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            categories={categories}
            category={category}
            image={category.icon}
            callback={(form) => {
              const route = DeviceCategoryController.update(category.id as number);
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
