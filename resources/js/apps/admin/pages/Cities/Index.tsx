import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/vendor/metronic/helpers";
import Table, {LinkAction} from "@/shared/components/Table";
import usePermissions from '@/shared/hooks/use-permissions';
import {PaginationResource} from "@/shared/types";
import {City} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import CityController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/CityController";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";


type Props = {
  rows: PaginationResource<City>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  city_id?: number;
};
const Index = (
  {
    rows,
    prams,
  }: Props
) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
    city_id: undefined,
  };

  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchPrams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(CityController.index().url, next);
  };
  return (
    <>
      <Head title={t('cities')}/>
      <PageTitle breadcrumbs={[
        // {
        //   title: 'User Management',
        //   path: '/apps/user-management/users',
        //   isSeparator: false,
        //   isActive: false,
        // },
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('cities')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <City>
            name='cities'
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
                title: t('region'),
                property: 'region',
                render: (row) => (
                  row.region?.title || '---'
                ),
              },
            ]}
            actions={[
              {
                show: hasPermission('edit cities'),
                ele: (row) => (
                  <LinkAction
                    key={`edit-region-${row.id}`}
                    href={CityController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: hasPermission('delete cities'),
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-region-${row.id}`}
                    callback={() => {
                      router.delete(CityController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              hasPermission('create cities') ? (
                <Link
                href={CityController.create().url}
                className="btn btn-primary"
              >
                <KTIcon iconName='plus' className='fs-2'/>
              </Link>
              ) : undefined
            }
          />
        </KTCard>
      </Content>
    </>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page}/>;

export default Index;
