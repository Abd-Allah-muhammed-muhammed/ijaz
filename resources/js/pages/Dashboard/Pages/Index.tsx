import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {Page} from "@/types/models";
import ConfirmAction from "@/components/Table/partials/confirm-action";
import {ReactElement} from "react";

import PageController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController";


type Props = {
  rows: PaginationResource<Page>,
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
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.reload({
      data: searchPrams,
      only: ['rows'],
      // @ts-ignore
      "preserveState": true,
    });
  };
  return (
    <>
      <Head title={t('pages')}/>
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
        {t('pages')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Page>
            name='pages'
            only={['rows']}
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
                title: t('slug'),
                property: 'slug',
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-pages-${row.id}`}
                    href={PageController.edit(row).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-pages-${row.id}`}
                    callback={() => {
                      router.delete(PageController.destroy(row).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={PageController.create().url}
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
