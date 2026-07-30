import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { PageTitle } from '@/vendor/metronic/layout/core';
import CarCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarCategoryController';
import Table, { LinkAction } from '@/shared/components/Table';
import ConfirmAction from '@/shared/components/Table/partials/confirm-action';
import { PaginationResource } from '@/shared/types';
import { Category } from '@/shared/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
  rows: PaginationResource<Category>;
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  parent_id?: number;
};
const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
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
    router.get(CarCategoryController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('car_categories')} />
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
        {t('car_categories')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard>
          <Table<Category>
            name="car_categories"
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: t('icon'),
                property: 'icon',
                render: (row) => (
                  <div className="symbol symbol-50px me-2">
                    <img src={row.icon || '/media/avatars/blank.png'} alt="" />
                  </div>
                ),
              },
              {
                title: t('title'),
                property: 'title',
              },
              {
                title: t('children'),
                property: 'children_count',
                render: (row) => (
                  <button
                    type="button"
                    onClick={() => {
                      searchPramsChanged('parent_id', row.id as number);
                    }}
                    className="btn btn-icon btn-light-twitter btn-sm"
                  >
                    {row.children_count || 0}
                  </button>
                ),
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction key={`edit-category-${row.id}`} href={CarCategoryController.edit(row.id as number).url} title={t('edit')} />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-category-${row.id}`}
                    callback={() => {
                      router.delete(CarCategoryController.destroy(row.id as number).url);
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link href={CarCategoryController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
              </Link>
            }
          />
        </KTCard>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Index;
