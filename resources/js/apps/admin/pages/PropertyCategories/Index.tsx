import { KTCard, KTIcon } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { PageTitle } from '@/vendor/metronic/layout/core';
import PropertyCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyCategoryController';
import Table, { LinkAction } from '@/shared/components/Table';
import usePermissions from '@/shared/hooks/use-permissions';
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
  const { hasPermission } = usePermissions();
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    // prams is $request->all() and may include `page` from the current URL.
    // Always drop it when filters change so parent_id/search navigation does not
    // land on an empty page of a smaller filtered result set.
    const next: Record<string, string | number> = { ...(prams || { per_page: 10, search: '' }) };
    if (value) {
      next[name] = value;
    } else {
      delete next[name];
    }
    delete next.page;
    router.get(PropertyCategoryController.index().url, next);
  };
  return (
    <>
      <Head title={t('property_categories')} />
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
        {t('property_categories')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard>
          <Table<Category>
            name="property_category"
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
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
                show: hasPermission('edit propertyCategories'),
                ele: (row) => (
                  <LinkAction key={`edit-category-${row.id}`} href={PropertyCategoryController.edit(row.id as number).url} title={t('edit')} />
                ),
              },
              {
                show: hasPermission('delete propertyCategories'),
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-category-${row.id}`}
                    callback={() => {
                      router.delete(PropertyCategoryController.destroy(row.id as number).url);
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              hasPermission('create propertyCategories') ? (
                <Link href={PropertyCategoryController.create().url} className="btn btn-primary">
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
