import { KTCard } from '@/vendor/metronic/helpers';
import MasterLayout from '@/apps/admin/layouts';
import { Content } from '@/apps/admin/layouts';
import { ToolbarWrapper } from '@/apps/admin/layouts';
import { PageTitle } from '@/vendor/metronic/layout/core';
import SpecializationController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/SpecializationController';
import { Specialization } from '@/shared/types/models';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Form from './Form';

type Props = {
  specializations: Specialization[];
};

const Create = ({ specializations }: Props) => {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('specializations')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('specializations'),
            path: SpecializationController.index().url,
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
        {t('specializations')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            specializations={specializations}
            image={'/media/avatars/blank.png'}
            callback={(form) => {
              form.submit(SpecializationController.store());
            }}
          />
        </KTCard>
      </Content>
    </>
  );
};
Create.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Create;
