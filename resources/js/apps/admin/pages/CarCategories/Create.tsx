import { KTCard } from '@/vendor/metronic/helpers';
import MasterLayout from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import CarCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarCategoryController';
import { Category } from '@/shared/types/models';
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
