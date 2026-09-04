/**
 * PAUSED (not removed) — Provider dashboard top-up list page.
 * Unreachable while Modules/Wallet/Routes/provider.php top-up-requests
 * resource and the Provider sidebar entry are commented out.
 * Re-enable: uncomment the route + sidebar + RechargeTrigger + TopUpController
 * Wayfinder import/usages below (chore/provider-topup-pause, 2026-09-04).
 * Note: after re-enabling the route, re-run wayfinder:generate so
 * TopUpController.ts is restored under resources/js/actions/...
 */
import { useTranslation } from 'react-i18next';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head, router} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Table, {LinkAction} from "@/shared/components/Table";
import {PaginationResource} from "@/shared/types";
import {TopUpRequest} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import {OperationStatusEnum} from "@/Enums/Enums";
// Paused (not removed) — Wayfinder file absent while provider top-up routes are commented out.
// import TopUpController from "@/actions/Modules/Wallet/Http/Controllers/Provider/TopUpController";
// Paused (not removed) — chore/provider-topup-pause, 2026-09-04.
// import {RechargeTrigger} from '@/apps/provider/components/wallet/WalletQuickActions';
import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";


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
      <Head title={t('recharge_requests')}/>
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
        {t('recharge_requests')}
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
                    // Paused — was TopUpController.show(row.id as string).url
                    href={`#paused-top-up-show-${row.id}`}
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
                      // Paused — was router.delete(TopUpController.destroy(row.id as number).url)
                      // router.delete(TopUpController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                  : <></>,
              },
            ]}
            // Paused (not removed) — chore/provider-topup-pause, 2026-09-04.
            // addButton={
            //   <RechargeTrigger reloadOnly={['rows']} />
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
