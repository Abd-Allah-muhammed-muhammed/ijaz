import { PageTitle } from '@/vendor/metronic/layout/core';
import MasterLayout from '@/apps/admin/layouts';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';
import { Head, router } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';

const Create = () => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('create_new_car_brand')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('car_brands'),
            path: CarBrandController.index().url,
            isSeparator: false,
            isActive: false,
          },
        ]}
      >
        {t('create_new_car_brand')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <Form
          onSubmit={(form) => {
            form.submit(CarBrandController.store());
          }}
        />
      </Content>
    </>
  );
};

Create.layout = (page: ReactNode) => <MasterLayout>{page}</MasterLayout>;

export default Create;
