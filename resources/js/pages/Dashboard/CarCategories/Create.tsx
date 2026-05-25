import { KTCard } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import CarCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarCategoryController';
import { Category } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  categories: Category[];
};

const Create = ({ categories }: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('car_categories')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('car_categories'),
            path: CarCategoryController.index().url,
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
        {t('car_categories')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            categories={categories}
            image={'/media/avatars/blank.png'}
            callback={(form) => {
              form.submit(CarCategoryController.store());
            }}
          />
        </KTCard>
      </Content>
    </>
  );
};
Create.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Create;
