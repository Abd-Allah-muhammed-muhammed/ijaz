import { KTCard, KTIcon } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { PageTitle } from '@/_metronic/layout/core';
import PropertyTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyTypeController';
import Table, { LinkAction } from '@/components/Table';
import ConfirmAction from '@/components/Table/partials/confirm-action';
import usePermissions from '@/hooks/use-permissions';
import { PaginationResource } from '@/types';
import { PropertyType } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { ReactNode } from 'react';
import FormCheckInput from 'react-bootstrap/FormCheck';
import { useTranslation } from 'react-i18next';
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";

type Props = {
  rows: PaginationResource<PropertyType>;
  prams: SearchPrams;
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
    router.reload({
      only : [
        'rows'
      ],
      data: searchPrams,
      preserveState: true,
      preserveScroll: true,
    });
  };
  return (
    <>
      <Head title={t('property_types')} />
      <PageTitle
        breadcrumbs={[
          {
            title: '',
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}

      >{t('property_types')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <KTCard>
          <Table<PropertyType>
            name="property_types"
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            addButton={
              hasPermission('create propertyTypes') ? (
                <Link href={PropertyTypeController.create().url} className="btn btn-primary">
                <KTIcon iconName="plus" className="fs-2" />
                </Link>
              ): undefined
            }
            headers={[
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
                        router.put(PropertyTypeController.updateStatus(row.id as number).url, {
                          is_active: !row.is_active,
                        },{
                          preserveScroll: true,
                          preserveState: true,
                        });
                      }}
                    />
                  </div>
                ),
              },
            ]}
            rows={rows}
            actions={[
              {
                ele: (row) => <LinkAction href={PropertyTypeController.edit(row.id as number).url} title={t('edit')} />,
                show: hasPermission('edit propertyTypes'),
              },
              {
                ele: (row) => (
                  <ConfirmAction
                    callback={() => {
                      router.delete(PropertyTypeController.destroy(row.id as number).url, {
                        only: ['rows'],
                        preserveScroll: true,
                      });
                    }}
                    title={t('delete')}
                  />
                ),
                show: hasPermission('delete propertyTypes'),
              },
            ]}
          />
        </KTCard>
      </Content>
    </>
  );
};

Index.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Index;
