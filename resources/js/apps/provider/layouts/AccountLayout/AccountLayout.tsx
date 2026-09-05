import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import type { ReactElement } from 'react';
import type { Provider } from '@/shared/types/models';
import AccountHeader from '@/apps/provider/layouts/AccountLayout/components/AccountHeader';
import AccountMetrics from '@/apps/provider/layouts/AccountLayout/components/AccountMetrics';

export type AccountLayoutProps = {
  children: ReactElement;
  provider: Provider;
};

const AccountLayout = ({ children, provider }: AccountLayoutProps) => {
  return (
    <>
      <ToolbarWrapper />
      <Content>
        <div className="card mb-5 mb-xl-10">
          <div className="card-body pt-9 pb-6">
            <AccountHeader provider={provider} />
            <AccountMetrics wallet={provider.wallet} />
          </div>
        </div>
      </Content>
      {children}
    </>
  );
};

export default AccountLayout;
