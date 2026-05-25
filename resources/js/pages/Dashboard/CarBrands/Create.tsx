import { PageTitle } from '@/_metronic/layout/core';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';
import { Head, router } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { Content } from '@/_metronic/layout/components/content';

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
