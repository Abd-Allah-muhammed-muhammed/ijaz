import { PageTitle } from '@/_metronic/layout/core';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';
import { CarBrand } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form, { CarBrandForm } from './Form';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';

type Props = {
  carBrand: CarBrand;
};

const Edit = ({ carBrand }: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('edit_car_brand')} />
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
        {t('edit_car_brand')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <Form
          carBrand={carBrand}
          onSubmit={(form) => {
            form.transform((data: CarBrandForm) => ({
              ...data,
              _method: 'put',
            }));
            form.post(CarBrandController.update(carBrand.id).url);
          }}
        />
      </Content>
    </>
  );
};

Edit.layout = (page: ReactNode) => <MasterLayout>{page}</MasterLayout>;

export default Edit;
