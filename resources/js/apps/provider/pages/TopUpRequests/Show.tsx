import { useTranslation } from 'react-i18next';
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Deferred, Head, usePage} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import {TopUpRequest} from "@/shared/types/models";
import {ReactNode} from "react";
import TopUpController from "@/actions/Modules/Wallet/Http/Controllers/Provider/TopUpController";
import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";
import {PaymentResponse} from "@/shared/types/api";
import {build_date} from "@/shared/helpers/general";
import BankCardBootstrap from "@/shared/components/BankCardBootstrap";


type Props = {
  row: TopUpRequest
  paymentResponse: PaymentResponse | null
};

const CardSkeleton = () => (
  <div
    className="rounded-4 bg-light animate-pulse mb-3"
    style={{maxWidth: 340, minHeight: 200}}
    aria-hidden="true"
  />
);

const Show = ({row, paymentResponse}: Props) => {
  const { t } = useTranslation();
  const auth = usePage().props.auth.user

  return (
    <>
      <Head title={t('recharge_requests')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('recharge_requests'),
          path: TopUpController.index().url,
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
        {t('recharge_requests')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <div className="row justify-content-center g-4">
          {/* Details Card */}
          <div className="col-12 col-lg-7">
            <KTCard className="p-4 h-100">
              <h2 className="text-xl font-bold mb-6">{t('recharge_request_details')}</h2>
              <dl className="divide-y divide-gray-200 dark:divide-gray-700">
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('amount')}</dt>
                  <dd className="text-gray-900 dark:text-white">{row.amount}</dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('status')}</dt>
                  <dd>
                    <span className={`px-2 py-1 rounded text-xs font-semibold`} style={{backgroundColor: row.status?.color || '#eee', color: '#222'}}>
                      {t(row.status?.label || 'not_available')}
                    </span>
                  </dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('payment_status')}</dt>
                  <dd>
                    {row.payment_status ? (
                      <span className={`px-2 py-1 rounded text-xs font-semibold`} style={{backgroundColor: row.payment_status.color || '#eee', color: '#222'}}>
                        {t(row.payment_status.label)}
                      </span>
                    ) : (
                      <span className="text-gray-400">{t('not_available')}</span>
                    )}
                  </dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('payment_method')}</dt>
                  <dd className="text-gray-900 dark:text-white">{t(row.payment_method?.label || 'not_available')}</dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('attachment')}</dt>
                  <dd>
                    {row.transaction_image ? (
                      <a href={row.transaction_image} target="_blank" rel="noopener noreferrer" className="text-blue-600 dark:text-blue-400 underline">{t('download')}</a>
                    ) : (
                      <span className="text-gray-400">{t('not_available')}</span>
                    )}
                  </dd>
                </div>
                <div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                  <dt className="font-medium text-gray-600 dark:text-gray-300 w-40">{t('created_at')}</dt>
                  <dd className="text-gray-900 dark:text-white">{build_date(row.created_at)}</dd>
                </div>
              </dl>
            </KTCard>
          </div>
          {/* Bank Card */}
          <div className="col-12 col-lg-5">
            <Deferred data="paymentResponse" fallback={<CardSkeleton/>}>
              {!paymentResponse?.card ? (
                <p className="text-gray-400 mb-0">{t('no_card_details')}</p>
              ) : (
                <BankCardBootstrap
                  cardHolder={auth.name}
                  cardNumber={paymentResponse.card?.payment_description}
                  expiryMonth={paymentResponse.card?.expiryMonth}
                  expiryYear={paymentResponse.card?.expiryYear}
                  bankName={paymentResponse.card?.payment_method}
                  cardType={paymentResponse.card?.card_type}
                  cardScheme={paymentResponse.card?.card_scheme}
                />
              )}
            </Deferred>
          </div>
        </div>
      </Content>
    </>
  );
}
// @ts-ignore
Show.layout = (page: ReactNode) => <ProviderLayout children={page} {...page.props}/>

export default Show;
