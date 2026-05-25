import { KTCard, KTIcon } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar';
import { PageTitle } from '@/_metronic/layout/core';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';
import Table, { LinkAction } from '@/components/Table';
import ConfirmAction from '@/components/Table/partials/confirm-action';
import usePermissions from '@/hooks/use-permissions';
import { PaginationResource } from '@/types';
import { CarBrand } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { ReactNode } from 'react';
import FormCheckInput from 'react-bootstrap/FormCheck';
import { useTranslation } from 'react-i18next';

type Props = {
  rows: PaginationResource<CarBrand>;
  prams: SearchPrams;
};
type SearchPrams = {
  per_page: number;
  search: string;
};
const Index = ({ rows, prams }: Props) => {
  // @ts-ignore
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

    router.reload({
      data: searchPrams,
      // @ts-ignore
      preserveScroll: true,
      preserveState: true,
    });
  };
  return (
    <>
      <Head title={t('car_brands')} />
      <PageTitle breadcrumbs={[]}>{t('car_brands')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard>
          <Table<CarBrand>
            name="car_brands"
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            addButton={
              hasPermission('create carBrands') ? (
                <Link href={CarBrandController.create().url} className="btn btn-primary">
                  <KTIcon iconName="plus" className="fs-2" />
                </Link>
              ): undefined
            }
            headers={[
              {
                property: 'image_url',
                title: t('image'),
                render: (row) => (
                  <div className="symbol symbol-50px me-2">
                    <img src={row.image_url} alt="" />
                  </div>
                ),
              },
              {
                property: 'name',
                title: t('name'),
              },
              {
                property: 'is_active',
                title: t('is_active'),
                render: (row) => (
                  <div className="form-check form-switch form-check-custom form-check-solid me-10">
                    <FormCheckInput
                      className="h-20px w-30px"
                      type="checkbox"
                      defaultChecked={row.is_active}
                      onClick={() => {
                        router.put(
                          CarBrandController.updateStatus(row.id as number).url,
                          {
                            is_active: !row.is_active,
                          },
                          {
                            preserveScroll: true,
                            preserveState: true,
                          },
                        );
                      }}
                    />
                  </div>
                ),
              },
            ]}
            rows={rows}
            actions={[
              {
                ele: (row) => <LinkAction href={CarBrandController.edit(row.id as number).url} title={t('edit')} />,
                show: hasPermission('edit carBrands'),
              },
              {
                ele: (row) => (
                  <ConfirmAction
                    callback={() => {
                      router.delete(CarBrandController.destroy(row.id as number).url, {
                        only: ['rows'],
                        preserveScroll: true,
                      });
                    }}
                    title={t('delete')}
                  />
                ),
                show: hasPermission('delete carBrands'),
              },
            ]}
          />
        </KTCard>
      </Content>
    </>
  );
};

Index.layout = (page: ReactNode) => <MasterLayout>{page}</MasterLayout>;

export default Index;
