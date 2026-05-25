import { KTCard } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import PropertyTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyTypeController';
import { PropertyType } from '@/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  propertyType: PropertyType;
};

const Edit = ({ propertyType }: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('property_types')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('property_types'),
            path: PropertyTypeController.index().url,
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
        {t('property_types')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            propertyType={propertyType}
            callback={(form) => {
              const route = PropertyTypeController.update(propertyType.id as number);
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
