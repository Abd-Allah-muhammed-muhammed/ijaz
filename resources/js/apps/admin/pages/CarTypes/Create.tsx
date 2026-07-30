import MasterLayout from '@/apps/admin/layouts';

import { Content } from '@/apps/admin/layouts';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import CarTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarTypeController';
import { ReactSelect } from '@/shared/types';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  brands: ReactSelect[];
};

const Create = ({ brands }: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('create_car_type')} />

      <PageTitle
        breadcrumbs={[
          {
            title: t('car_types'),
            path: CarTypeController.index().url,
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
        {t('create_car_type')}

      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <Form
          brands={brands}
          callback={(form) => {
            form.post(CarTypeController.store().url);
          }}
        />
      </Content>

    </>
  );
};

Create.layout = (page: ReactNode) => <MasterLayout>{page}</MasterLayout>;

export default Create;

