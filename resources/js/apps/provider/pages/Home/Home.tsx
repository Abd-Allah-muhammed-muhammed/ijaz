import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { useTranslation } from 'react-i18next';
import { Head, usePage } from '@inertiajs/react';
import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import { useRecommendedOrdersContext } from '@/store/recommend-orders-context';
import type {
  Banner,
  Order,
  Provider,
  Wallet,
  WalletTransaction,
} from '@/shared/types/models';
import { useEffect, type ReactElement } from 'react';
import WalletQuickActions from '@/apps/provider/components/wallet/WalletQuickActions';
import { parseAmount } from '@/shared/lib/formatters';
import type { BackendOrderTabCounts } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';
import { useOrderTabCounts } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';
import { useNeedsAttentionCount } from '@/apps/provider/pages/Home/hooks/use-needs-attention-count';
import AttentionBanner from '@/apps/provider/pages/Home/components/AttentionBanner';
import HomeBannerStrip from '@/apps/provider/pages/Home/components/HomeBannerStrip';
import HomeMetrics from '@/apps/provider/pages/Home/components/HomeMetrics';
import OrderTabsSection from '@/apps/provider/pages/Home/components/OrderTabsSection';
import RecentActivityCard from '@/apps/provider/pages/Home/components/RecentActivityCard';
import type { MetricTileData } from '@/apps/provider/types/metric-tile-data';

export type HomePageProps = {
  totalOrders: number;
  totalFinishedOrders: number;
  orderTabCounts?: BackendOrderTabCounts;
  wallet?: Wallet;
  recentTransactions?: WalletTransaction[];
  recommendOrders: Order[];
  banners: Banner[];
  pendingOrders: Order[];
  approvedOrders: Order[];
  inProgressOrders: Order[];
  endedByProviderOrders: Order[];
};

const Home = ({
  totalOrders,
  totalFinishedOrders,
  orderTabCounts,
  wallet,
  recentTransactions = [],
  recommendOrders,
  banners,
  pendingOrders,
  approvedOrders,
  inProgressOrders,
  endedByProviderOrders,
}: HomePageProps) => {
  const { t } = useTranslation();
  const user = usePage().props.auth.user as unknown as Provider;
  const { setOrders } = useRecommendedOrdersContext();
  const tabCounts = useOrderTabCounts(orderTabCounts);
  const needsAttentionCount = useNeedsAttentionCount(tabCounts);

  useEffect(() => {
    setOrders(recommendOrders);
  }, [recommendOrders, setOrders]);

  const displayBanners = banners.filter((banner) => Boolean(banner.image));
  const hasBanners = displayBanners.length > 0;

  const metrics: MetricTileData[] = [
    { value: wallet?.balance, label: t('balance') },
    { value: wallet?.amount_in_transfer ?? 0, label: t('amount_in_transfer') },
    { value: totalOrders, label: t('total_orders') },
    { value: totalFinishedOrders, label: t('completed_orders') },
  ];

  return (
    <>
      <Head title={t('dashboard')} />
      <PageTitle breadcrumbs={[]}>{t('dashboard')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="card mb-5">
          <div className="card-body py-5">
            <div className="d-flex flex-wrap justify-content-between align-items-center gap-3">
              <div className="d-flex align-items-center min-w-0">
                <div className="symbol symbol-50px symbol-circle me-4 flex-shrink-0">
                  {user.logo ? (
                    <img src={user.logo} alt={user.name} className="object-fit-contain" />
                  ) : (
                    <span className="symbol-label fs-3 fw-bold text-primary">
                      {(user.name ?? '').charAt(0)}
                    </span>
                  )}
                </div>
                <h2 className="fs-4 fs-md-3 fw-bold text-gray-900 mb-0 text-truncate">
                  {t('welcome_back', { name: user.name })}
                </h2>
              </div>
              <WalletQuickActions
                className="d-flex"
                reloadOnly={['wallet', 'recentTransactions', 'orderTabCounts']}
                availableBalance={
                  wallet
                    ? parseAmount(wallet.balance) -
                      parseAmount(wallet.pending_debit)
                    : undefined
                }
              />
            </div>
          </div>
        </div>

        <AttentionBanner count={needsAttentionCount} />

        <HomeMetrics metrics={metrics} />

        {hasBanners ? <HomeBannerStrip banners={displayBanners} /> : null}

        <div className="mb-5">
          <OrderTabsSection
            counts={tabCounts}
            pendingOrders={pendingOrders}
            approvedOrders={approvedOrders}
            inProgressOrders={inProgressOrders}
            endedByProviderOrders={endedByProviderOrders}
          />
        </div>

        <RecentActivityCard transactions={recentTransactions} />
      </Content>
    </>
  );
};

Home.layout = (page: ReactElement) => <ProviderLayout>{page}</ProviderLayout>;

export default Home;
