import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Table from "@/shared/components/Table";
import {PaginationResource} from "@/shared/types";
import {Message} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import MessageController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/MessageController";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";


type Props = {
  rows: PaginationResource<Message>,
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
    visitWithFilters(MessageController.index().url, next, { only: ['rows'] });
  };
  return (
    <>
      <Head title={t('messages')}/>
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
        {t('messages')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Message>
            name='messages'
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
                title: t('name'),
                property: 'name',
              },
              {
                title: t('phone'),
                property: 'phone',
              },
              {
                title: t('content'),
                property: 'content',
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-messages-${row.id}`}
                    callback={() => {
                      router.delete(MessageController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
          />
        </KTCard>
      </Content>
    </>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page}/>;

export default Index;
