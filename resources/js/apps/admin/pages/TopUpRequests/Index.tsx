/**
 * PAUSED (not removed) — Admin dashboard top-up list page.
 * Unreachable while Modules/Wallet/Routes/dashboard.php top-up-requests
 * routes and the Admin sidebar entry are commented out.
 * Re-enable: uncomment the route group + sidebar + TopUpRequestController
 * Wayfinder import/usages below (chore/provider-topup-pause, 2026-09-04).
 * Note: after re-enabling the route, re-run wayfinder:generate so
 * TopUpRequestController.ts is restored under resources/js/actions/...
 */
import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Table, {LinkAction} from "@/shared/components/Table";
import usePermissions from '@/shared/hooks/use-permissions';
import {PaginationResource} from "@/shared/types";
import {TopUpRequest} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import {OperationStatusEnum} from "@/Enums/Enums";
// Paused (not removed) — Wayfinder file absent while admin top-up routes are commented out.
// import TopUpRequestController from "@/actions/Modules/Wallet/Http/Controllers/Dashboard/TopUpRequestController";
import {applyFilterParam, visitWithFilters} from "@/shared/lib/filters";


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
  const { hasPermission } = usePermissions();
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
    // Paused — was TopUpRequestController.index().url
    visitWithFilters('#paused-admin-top-up-index', next, { only: ['rows'] });
  };
  return (
    <>
      <Head title={t('top_up_requests')}/>
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
        {t('top_up_requests')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <div className="alert alert-info d-flex align-items-center mb-5">
          <span className="badge badge-light-primary me-3">{t('pending')}</span>
          <span>{t('pending_requests_listed_first')}</span>
        </div>
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
                show: hasPermission('show topUpRequests'),
                ele: (row) => (
                  <LinkAction
                    key={`edit-top-up-${row.id}`}
                    // Paused — was TopUpRequestController.show(row.id as string).url
                    href={`#paused-admin-top-up-show-${row.id}`}
                    title={t('show')}
                  />
                ),
              },
            ]}
            // addButton={
            //   <Link
            //     href={TopUpRequestController.create().url}
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
