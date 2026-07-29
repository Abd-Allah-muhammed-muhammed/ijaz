import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {ProviderType} from "@/types/models";
import ConfirmAction from "@/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import ProviderTypeController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController";


type Props = {
  rows: PaginationResource<ProviderType>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
};
const Index = ({rows, prams,}: Props) => {
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
    router.get(ProviderTypeController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('provider_types')}/>
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
        {t('provider_types')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <ProviderType>
            name='provider_types'
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
              {
                title: t('providers_count'),
                property: 'providers_count',
                render: (row) => {
                  return (
                    <span className="badge badge-light-primary">
                      {row.providers_count ?? 0}
                    </span>
                  );
                },
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-provider_type-${row.id}`}
                    href={ProviderTypeController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-provider_type-${row.id}`}
                    callback={() => {
                      router.delete(ProviderTypeController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={ProviderTypeController.create().url}
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
