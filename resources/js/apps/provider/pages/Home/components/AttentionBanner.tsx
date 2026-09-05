import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import { NEEDS_ATTENTION_OFFERS_STATUS } from '@/apps/provider/pages/Home/hooks/use-needs-attention-count';
import { shouldRenderAttentionBanner } from '@/apps/provider/pages/Home/components/attention-banner-utils';

export type AttentionBannerProps = {
  count: number;
};

export { shouldRenderAttentionBanner };

export default function AttentionBanner({ count }: AttentionBannerProps) {
  const { t } = useTranslation();

  if (!shouldRenderAttentionBanner(count)) {
    return null;
  }

  const href = OrderController.offers.url({
    query: { status: NEEDS_ATTENTION_OFFERS_STATUS },
  });

  return (
    <aside
      className="card mb-5 border border-warning border-dashed"
      aria-label={t('offers_awaiting_client_approval_count', { count })}
    >
      <div className="card-body py-4 px-4 px-md-6">
        <div className="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
          <div className="d-flex align-items-start gap-3">
            <span className="symbol symbol-40px symbol-circle bg-light-warning flex-shrink-0">
              <span className="symbol-label">
                <KTIcon iconName="notification-bing" className="fs-2 text-warning" />
              </span>
            </span>
            <div>
              <p className="fw-bold text-gray-900 mb-1 fs-6">
                {t('offers_awaiting_client_approval_count', { count })}
              </p>
              <p className="text-muted fs-7 mb-0">
                {t('offers_awaiting_client_approval_description')}
              </p>
            </div>
          </div>
          <Link
            href={href}
            className="btn btn-sm btn-warning align-self-stretch align-self-sm-center text-nowrap"
          >
            {t('review_pending_offers')}
          </Link>
        </div>
      </div>
    </aside>
  );
}
