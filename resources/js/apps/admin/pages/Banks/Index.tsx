import BankController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/BankController';
import Table, { LinkAction } from '@/shared/components/Table';
import usePermissions from '@/shared/hooks/use-permissions';
import { PaginationResource } from '@/shared/types';
import { Bank } from '@/shared/types/models';
import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement } from 'react';
import { useTranslation } from 'react-i18next';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';

type Props = {
  rows: PaginationResource<Bank>;
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
  };

  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam({ ...searchPrams } as Record<string, unknown>, name, value);
    visitWithFilters(BankController.index().url, next);
  };

  return (
    <>
      <Head title={t('banks')} />
      <PageTitle
        breadcrumbs={[
          {
            title: '',
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
        <KTCard>
          <Table<Bank>
            name="banks"
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: t('name'),
                property: 'name',
                render: (row) => (
                  <div className="d-flex align-items-center gap-2">
                    <img
                      src={row.logo_url || '/media/avatars/blank.png'}
                      alt=""
                      className="rounded"
                      style={{ width: 28, height: 28, objectFit: 'contain' }}
                    />
                    <span>{row.name}</span>
                  </div>
                ),
              },
              {
                title: t('status'),
                property: 'is_active',
                render: (row) => (
                  <span
                    className={`badge bg-light-${row.is_active ? 'success' : 'danger'} text-${row.is_active ? 'success' : 'danger'} fw-bold fs-7 px-3 py-2 rounded-pill border border-${row.is_active ? 'success' : 'danger'} border-opacity-25`}
                  >
                    {row.is_active ? t('active') : t('inactive')}
                  </span>
                ),
              },
            ]}
            actions={[
              {
                show: hasPermission('edit banks'),
                ele: (row) => (
                  <LinkAction
                    key={`edit-bank-${row.id}`}
                    href={BankController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
            ]}
            addButton={
              hasPermission('create banks') ? (
                <Link href={BankController.create().url} className="btn btn-primary">
                  <KTIcon iconName="plus" className="fs-2" />
                </Link>
              ) : undefined
            }
          />
        </KTCard>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
