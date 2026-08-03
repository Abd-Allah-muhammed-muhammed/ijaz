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
import {Nationality} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import NationalityController from "@/actions/Modules/Geo/Http/Controllers/Dashboard/NationalityController";


type Props = {
  rows: PaginationResource<Nationality>,
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
    router.get(NationalityController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('nationalities')}/>
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
        {t('nationalities')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Nationality>
            name='nationalities'
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
                show: hasPermission('edit nationalities'),
                ele: (row) => (
                  <LinkAction
                    key={`edit-nationality-${row.id}`}
                    href={NationalityController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: hasPermission('delete nationalities'),
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-nationality-${row.id}`}
                    callback={() => {
                      router.delete(NationalityController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              hasPermission('create nationalities') ? (
                <Link
                href={NationalityController.create().url}
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
