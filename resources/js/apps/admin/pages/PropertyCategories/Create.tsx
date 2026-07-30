import { KTCard } from '@/vendor/metronic/helpers';
import MasterLayout from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import PropertyCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyCategoryController';
import { PropertyCategory } from '@/shared/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  categories: PropertyCategory[];
};

const Create = ({ categories }: Props) => {
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
            title: t('create'),
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
            callback={(form) => {
              form.submit(PropertyCategoryController.store());
            }}
          />
        </KTCard>
      </Content>
    </>
  );
};
Create.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Create;
