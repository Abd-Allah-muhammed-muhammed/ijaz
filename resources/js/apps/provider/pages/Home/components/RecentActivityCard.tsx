import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card } from 'react-bootstrap';
import { KTIcon } from '@/vendor/metronic/helpers';
import { EmptyState, StatusBadge, SECONDARY_BUTTON_CLASS } from '@/shared/components/ui';
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import type { WalletTransaction } from '@/shared/types/models';

export type RecentActivityCardProps = {
  transactions: WalletTransaction[];
};

export default function RecentActivityCard({ transactions }: RecentActivityCardProps) {
  const { t } = useTranslation();

  return (
    <Card className="mb-5">
      <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4 flex-wrap gap-2">
        <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">
          {t('recent_wallet_activity')}
        </h3>
        <div className="card-toolbar mb-0">
          <Link href={AuthController.statements().url} className={SECONDARY_BUTTON_CLASS}>
            {t('view_statements')}
          </Link>
        </div>
      </Card.Header>
      <Card.Body>
        {transactions.length === 0 ? (
          <EmptyState
            compact
            icon={<KTIcon iconName="time" className="fs-3x text-gray-300 mb-3" />}
            title={t('no_recent_wallet_activity')}
            description={t('no_recent_wallet_activity_description')}
          />
        ) : (
          transactions.map((transaction, index) => {
            const amount = Number(transaction.amount) || 0;
            const isPending = Boolean(transaction.is_pending);
            const isCredit = Boolean(transaction.is_credit);

            return (
              <div key={transaction.id}>
                <div className="d-flex align-items-center justify-content-between gap-3">
                  <span className="fw-semibold fs-6 text-gray-800 text-break">
                    {transaction.description}
                  </span>
                  {isPending ? (
                    <span className="d-flex align-items-center gap-2 flex-shrink-0">
                      <span className="fw-bold fs-6 text-gray-500">{amount}</span>
                      <StatusBadge label={t('pending')} color="warning" className="fs-8" />
                    </span>
                  ) : (
                    <span
                      className={`fw-bold fs-6 flex-shrink-0 ${isCredit ? 'text-success' : 'text-gray-800'}`}
                    >
                      {isCredit ? `+${amount}` : `-${amount}`}
                    </span>
                  )}
                </div>
                {index !== transactions.length - 1 ? (
                  <div className="separator separator-dashed my-4" />
                ) : null}
              </div>
            );
          })
        )}
      </Card.Body>
    </Card>
  );
}
