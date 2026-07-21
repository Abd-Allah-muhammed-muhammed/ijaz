import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {Order, TicketSupport} from "@/types/models";
import {ReactElement} from "react";
import SupportController from "@/actions/Modules/Support/Http/Controllers/Dashboard/SupportController";


type Props = {
  rows: PaginationResource<TicketSupport<Order>>,
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
      <Head title={t('support_tickets')}/>
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
        {t('tickets')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <TicketSupport<Order>>
            name='tickets'
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
                title: t('message'),
                property: 'message',
              },
              {
                title: t('operation'),
                property: 'operation',
                render: (row) => {
                  return row.operation ? (
                    <Link href={row.operation.show_url}>
                      {row.operation.type}(#{row.operation.id})
                    </Link>
                  ) : t('N/A')
                }
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`show-tickets-${row.id}`}
                    href={SupportController.show(row.id as number).url}
                    title={t('show')}
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
