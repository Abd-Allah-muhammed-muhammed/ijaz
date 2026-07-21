import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {Banner} from "@/types/models";
import ConfirmAction from "@/components/Table/partials/confirm-action";
import BannerController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/BannerController";
import {ReactElement} from "react";


type Props = {
  rows: PaginationResource<Banner>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  parent_id?: number;
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
    router.get(BannerController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('banners')}/>
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
        {t('banners')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Banner>
            name='banners'
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: t('image'),
                property: 'image',
                render: (row) => (
                  <img
                    src={row.image}
                    alt={row.id as string}
                    className="w-50px h-50px rounded"/>
                ),
              },
              {
                title: t('link'),
                property: 'link',
                render: (row) => row.link ? (
                  <a
                    href={row.link}
                    className=""
                    target="_blank"
                  >
                    {row.link}
                  </a>
                ) : ('---'),
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-banners-${row.id}`}
                    href={BannerController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-banners-${row.id}`}
                    callback={() => {
                      router.delete(BannerController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={BannerController.create().url}
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

Index.layout = (page: ReactElement) => <MasterLayout {...page} children={page}/>;

export default Index;
