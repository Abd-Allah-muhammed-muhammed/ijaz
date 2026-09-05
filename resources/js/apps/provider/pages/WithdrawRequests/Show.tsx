import { useTranslation } from 'react-i18next';
import { PageTitle } from "@/vendor/metronic/layout/core";
import { ToolbarWrapper } from "@/vendor/metronic/layout/components/toolbar";
import { Content } from "@/vendor/metronic/layout/components/content";
import { Head } from "@inertiajs/react";
import { WithdrawRequest } from "@/shared/types/models";
import { ReactNode } from "react";
import WithdrawController from "@/actions/Modules/Wallet/Http/Controllers/Provider/WithdrawController";
import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";
import { build_date } from "@/shared/helpers/general";
import {
  DetailSection,
  SectionCard,
  StatusBadge,
} from '@/shared/components/ui';


type Props = {
  row: WithdrawRequest
};

const Show = ({ row }: Props) => {
  const { t } = useTranslation();
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
          <div className="col-12">
            <SectionCard title={t('withdraw_request_details')}>
              <div className="d-flex flex-column gap-6">
                <DetailSection label={t('amount')} value={row.amount} />
                <DetailSection label={t('status')}>
                  <StatusBadge
                    status={row.status}
                    label={row.status?.label ?? t('not_available')}
                  />
                </DetailSection>
                <DetailSection label={t('transfer_status')}>
                  <StatusBadge status={row.transfer_status} />
                </DetailSection>
                <DetailSection
                  label={t('created_at')}
                  value={build_date(row.created_at)}
                />
              </div>
            </SectionCard>
          </div>
        </div>
      </Content>
    </>
  );
}
// @ts-ignore
Show.layout = (page: ReactNode) => <ProviderLayout children={page} {...page.props} />

export default Show;
