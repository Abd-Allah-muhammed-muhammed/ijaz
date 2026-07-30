import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/vendor/metronic/helpers";
import Table, {LinkAction} from "@/shared/components/Table";
import {PaginationResource} from "@/shared/types";
import {Region} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import RegionController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/RegionController";
import CityController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/CityController";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";


type Props = {
  rows: PaginationResource<Region>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
};
const Index = (
  {
    rows,
    prams,
  }: Props
) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
  };
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchPrams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(RegionController.index().url, next);
  };
  return (
    <>
      <Head title={t('region')}/>
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
        {t('region')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Region>
            name='skills'
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
                title: t('cities_count'),
                property: 'cities_count',
                render: (row) => (
                  <Link
                    href={CityController.index({
                      mergeQuery: {
                        region_id: row.id,
                      }
                    })
                      .url
                    }
                    className='text-primary'
                  >
                    {row.cities_count || '0'}
                  </Link>
                ),
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-region-${row.id}`}
                    href={RegionController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-region-${row.id}`}
                    callback={() => {
                      router.delete(RegionController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={RegionController.create().url}
                className="btn btn-primary"
              >
                <KTIcon iconName='plus' className='fs-2'/>
              </Link>
            }
          />
        </KTCard>
      </Content>
    </>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page}/>;

export default Index;
