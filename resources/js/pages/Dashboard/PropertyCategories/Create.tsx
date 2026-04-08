import { KTCard } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import PropertyCategoryController from '@/actions/App/Http/Controllers/Dashboard/PropertyCategoryController';
import { PropertyCategory } from '@/types/models';
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
