import BankController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/BankController';
import Form from './Form';
import { KTCard } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

const Create = () => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('banks')} />
      <PageTitle
        breadcrumbs={[
          {
            title: t('banks'),
            path: BankController.index().url,
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
        {t('banks')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard className="p-4">
          <Form
            logoUrl="/media/avatars/blank.png"
            callback={(form) => {
              form.submit(BankController.store());
            }}
          />
        </KTCard>
      </Content>
    </>
  );
};

Create.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Create;
