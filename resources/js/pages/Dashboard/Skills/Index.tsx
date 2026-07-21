import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {Skill} from "@/types/models";
import ConfirmAction from "@/components/Table/partials/confirm-action";
import CategoryController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/CategoryController";
import {ReactElement} from "react";
import SkillController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/SkillController";


type Props = {
  rows: PaginationResource<Skill>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  category_id?: number;
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
    category_id: undefined,
  };

  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.get(SkillController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('skills')}/>
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
        {t('skills')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Skill>
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
                title: t('category'),
                property: 'category',
                render: (row) => (
                  row.category?.title || '---'
                ),
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-category-${row.id}`}
                    href={SkillController.edit(row.id as number).url}
                    title={t('edit')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-category-${row.id}`}
                    callback={() => {
                      router.delete(SkillController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
            addButton={
              <Link
                href={SkillController.create().url}
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
