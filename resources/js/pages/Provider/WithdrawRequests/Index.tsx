import { useTranslation } from 'react-i18next';
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, router} from "@inertiajs/react";
import {KTCard} from "@/_metronic/helpers";
import Table, {LinkAction} from "@/components/Table";
import {PaginationResource} from "@/types";
import {TopUpRequest} from "@/types/models";
import ConfirmAction from "@/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import {OperationStatusEnum} from "@/Enums/Enums";
import WithdrawController from "@/actions/App/Http/Controllers/Provider/WithdrawController";
import ProviderLayout from "@/layouts/provider/ProviderLayout";


type Props = {
  rows: PaginationResource<TopUpRequest>,
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
            <TopUpRequest>
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
                    href={WithdrawController.show(row.id as string).url}
                    title={t('show')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => row.status.value === OperationStatusEnum.Pending
                  ? <ConfirmAction
                    key={`delete-withdraw-${row.id}`}
                    callback={() => {
                      router.delete(WithdrawController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                  : <></>,
              },
            ]}
            // addButton={
            //   <Link
            //     href={WithdrawController.create().url}
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
Index.layout = (page: ReactElement) => <ProviderLayout children={page} {...page.props}/>;

export default Index;
