import MasterLayout from '@/_metronic/layout/MasterLayout';

import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import CarTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarTypeController';
import { ReactSelect } from '@/types';
import { CarType } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  carType: CarType;
  brands: ReactSelect[];
};

const Edit = ({ carType, brands }: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('edit_car_type')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('car_types'),
            path: CarTypeController.index().url,
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
        {t('edit_car_type')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <Form
          carType={carType}
          brands={brands}
          callback={(form) => {
            const route = CarTypeController.update(carType.id);
            form.transform((data) => ({
              ...data,
              _method: route.method,
            }));
            form.post(route.url);
          }}
        />
      </Content>

    </>
  );
};

Edit.layout = (page: ReactNode) => <MasterLayout>{page}</MasterLayout>;

export default Edit;

