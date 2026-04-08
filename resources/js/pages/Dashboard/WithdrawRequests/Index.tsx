import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, router} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {WithdrawRequest} from "@/types/models";
import {ReactElement} from "react";
import WithdrawRequestController from "@/actions/App/Http/Controllers/Dashboard/WithdrawRequestController";


type Props = {
  rows: PaginationResource<WithdrawRequest>,
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
    });
  };
  return (
    <>
      <Head title={t('withdraw_requests')}/>
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
        {t('withdraw_requests')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <WithdrawRequest>
            only={[
              'rows'
            ]}
            name='withdraw'
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: '#',
                property: 'id',
              },
              {
                title: t('name'),
                property: 'name',
                render: row => (row.user?.name ?? "-"),
              },
              {
                title: t('amount'),
                property: 'amount',
              },
              {
                title: t('status'),
                property: 'status',
                render: (row) => (
                  <span className={`badge badge-light-${row.status.color}`}> {row.status.label}</span>
                )
              },
              {
                title: t('created_at'),
                property: 'created_at',
                render: (row) => {
                  const data = new Date(row.created_at);
                  return (<span>{data.toLocaleDateString()}: {data.toLocaleTimeString()}</span>)
                }
              },
            ]}
            actions={[
              {
                show: true,
                ele: (row) => (
                  <LinkAction
                    key={`edit-withdraw-${row.id}`}
                    href={WithdrawRequestController.show(row.id as string).url}
                    title={t('show')}
                  />
                ),
              },
            ]}
            // addButton={
            //   <Link
            //     href={WithdrawRequestController.create().url}
            //     className="btn btn-primary"
            //   >
            //     <KTIcon iconName='plus' className='fs-2'/>
            //   </Link>
            // }
          />
        </KTCard>
      </Content>
    </>
  );
}

//@ts-ignore
Index.layout = (page: ReactElement) => <MasterLayout children={page} {...page.props}/>;

export default Index;
