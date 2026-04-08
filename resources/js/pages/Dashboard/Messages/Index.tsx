import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, router} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Table from "@/components/Table";
import {PaginationResource} from "@/types";
import {Message} from "@/types/models";
import ConfirmAction from "@/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import MessageController from "@/actions/App/Http/Controllers/Dashboard/MessageController";


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
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.reload({
      only: ['rows'],
      data: searchPrams,
      // @ts-ignore
      preserveState: true,
      preserveScroll: true,
    });
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
