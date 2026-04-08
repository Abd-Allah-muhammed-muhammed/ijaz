import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
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
import TopUpController from "@/actions/App/Http/Controllers/Provider/TopUpController";
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
      <Head title={t('top-up_requests')}/>
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
        {t('top-up_requests')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <TopUpRequest>
            only={[
              'rows'
            ]}
            name='top-up'
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
                title: t('payment_method'),
                property: 'payment_method',
                render: (row) => (
                  <span className={`badge badge-light-secondary`}> {row.payment_method.label}</span>
                )
              },
              {
                title: t('payment_status'),
                property: 'payment_status',
                render: (row) => row.payment_status ?
                  (<span className={`badge badge-light-${row.payment_status.color}`}> {row.payment_status.label}</span>)
                  : (<span className={`badge badge-light-secondary`}> {t('N/A')}</span>)
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
                    key={`edit-top-up-${row.id}`}
                    href={TopUpController.show(row.id as string).url}
                    title={t('show')}
                  />
                ),
              },
              {
                show: true,
                ele: (row) => row.status.value === OperationStatusEnum.Pending
                  ? <ConfirmAction
                    key={`delete-top-up-${row.id}`}
                    callback={() => {
                      router.delete(TopUpController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                  : <></>,
              },
            ]}
            // addButton={
            //   <Link
            //     href={TopUpController.create().url}
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
