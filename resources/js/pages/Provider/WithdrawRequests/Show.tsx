import { useTranslation } from 'react-i18next';
import { PageTitle } from "@/_metronic/layout/core";
import { ToolbarWrapper } from "@/_metronic/layout/components/toolbar";
import { Content } from "@/_metronic/layout/components/content";
import { Head, usePage } from "@inertiajs/react";
import { KTCard } from "@/_metronic/helpers";
import { WithdrawRequest } from "@/types/models";
import { ReactNode } from "react";
import WithdrawController from "@/actions/Modules/Wallet/Http/Controllers/Provider/WithdrawController";
import ProviderLayout from "@/layouts/provider/ProviderLayout";
import { PaymentResponse } from "@/types/api";
import { build_date } from "@/helpers/general";
import BankCardBootstrap from "@/components/BankCardBootstrap";


type Props = {
  row: WithdrawRequest
  paymentResponse: PaymentResponse | null
};

const Show = ({ row, paymentResponse }: Props) => {
  const { t } = useTranslation();
  const auth = usePage().props.auth.user
  return (
    <>
      <Head title={t('withdraw_requests')} />
      <PageTitle breadcrumbs={[
        {
          title: t('withdraw_requests'),
          path: WithdrawController.index().url,
          isSeparator: false,
          isActive: false,
        },
        {
          title: t('show'),
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('withdraw_requests')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="row justify-content-center g-4">
          {/* Details Card */}
          <div className="col-12">
            <KTCard className="p-4 h-100">
              <h2 className="text-xl font-bold mb-6">{t('withdraw_request_details')}</h2>
              <dl className="divide-y divide-gray-200 dark:divide-gray-700">
                {/*<div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">*/}
                {/*  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{trans('user')}</dt>*/}
                {/*  <dd className="text-gray-900 dark:text-white">*/}
                {/*    {row.user?.name || trans('not_available')}*/}
                {/*    {row.user_type ? (*/}
                {/*      <span className="ml-2 text-xs text-gray-400">({trans(row.user_type)})</span>*/}
                {/*    ) : null}*/}
                {/*  </dd>*/}
                {/*</div>*/}
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('amount')}</dt>
                  <dd className="text-gray-900 dark:text-white">{row.amount}</dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('status')}</dt>
                  <dd>
                    <span className={`px-2 py-1 rounded text-xs font-semibold`} style={{ backgroundColor: row.status?.color || '#eee', color: '#222' }}>
                      {t(row.status?.label || 'not_available')}
                    </span>
                  </dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('created_at')}</dt>
                  <dd className="text-gray-900 dark:text-white">{build_date(row.created_at)}</dd>
                </div>
              </dl>
            </KTCard>
          </div>
        </div>
      </Content>
    </>
  );
}
// @ts-ignore
Show.layout = (page: ReactNode) => <ProviderLayout children={page} {...page.props} />

export default Show;
