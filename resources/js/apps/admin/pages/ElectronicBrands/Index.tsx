import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { PageTitle } from '@/vendor/metronic/layout/core';
import ElectronicBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/ElectronicBrandController';
import Table, { LinkAction } from '@/shared/components/Table';
import usePermissions from '@/shared/hooks/use-permissions';
import ConfirmAction from '@/shared/components/Table/partials/confirm-action';
import { PaginationResource } from '@/shared/types';
import { ElectronicBrand } from '@/shared/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
  rows: PaginationResource<ElectronicBrand>;
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
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.get(ElectronicBrandController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('electronic_brands')} />
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
        {t('electronic_brands')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard>
          <Table<ElectronicBrand>
            name="electronic_brands"
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
              },
            ]}
            actions={[
              {
                show: hasPermission('edit electronicBrands'),
                ele: (row) => (
                  <LinkAction key={`edit-electronic-brand-${row.id}`} href={ElectronicBrandController.edit(row.id as number).url} title={t('edit')} />
                ),
              },
              {
                show: hasPermission('delete electronicBrands'),
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-electronic-brand-${row.id}`}
                    callback={() => {
                      router.delete(ElectronicBrandController.destroy(row.id as number).url);
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              hasPermission('create electronicBrands') ? (
                <Link href={ElectronicBrandController.create().url} className="btn btn-primary">
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
