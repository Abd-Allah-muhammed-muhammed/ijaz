import { KTCard } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import ElectronicBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/ElectronicBrandController';
import { ElectronicBrand } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  electronicBrand: ElectronicBrand;
};

const Edit = ({ electronicBrand }: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('electronic_brands')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('electronic_brands'),
            path: ElectronicBrandController.index().url,
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
        {t('electronic_brands')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            electronicBrand={electronicBrand}
            image={electronicBrand.image_url || '/media/avatars/blank.png'}
            callback={(form) => {
              const route = ElectronicBrandController.update(electronicBrand.id as number);
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
