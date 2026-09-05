import { useState } from 'react';
import { Col, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { StatTile, SECONDARY_BUTTON_CLASS } from '@/shared/components/ui';
import type { MetricTileData } from '@/apps/provider/types/metric-tile-data';
import type { Wallet } from '@/shared/types/models';

export type AccountMetricsProps = {
  wallet?: Wallet | null;
};

export default function AccountMetrics({ wallet }: AccountMetricsProps) {
  const { t } = useTranslation();
  const [showWalletDetails, setShowWalletDetails] = useState(false);

  const primaryMetrics: MetricTileData[] = [
    { value: wallet?.balance, label: t('balance') },
    { value: wallet?.pending_debit, label: t('wallet_on_hold') },
    { value: wallet?.amount_in_transfer, label: t('wallet_being_transferred') },
    { value: wallet?.total_earning, label: t('wallet_total_earned') },
  ];

  const detailMetrics: MetricTileData[] = [
    { value: wallet?.total_spent, label: t('total_spent') },
    { value: wallet?.credit, label: t('credit') },
    { value: wallet?.pending_credit, label: t('pending_credit') },
    { value: wallet?.debit, label: t('debit') },
  ];

  return (
    <div className="d-flex flex-column flex-grow-1">
      <Row className="g-3 mb-3">
        {primaryMetrics.map((metric) => (
          <Col key={metric.label} xs={6} lg={3}>
            <StatTile label={metric.label} value={metric.value} />
          </Col>
        ))}
      </Row>
      <button
        type="button"
        className={`${SECONDARY_BUTTON_CLASS} mb-3 align-self-start`}
        onClick={() => setShowWalletDetails((open) => !open)}
        aria-expanded={showWalletDetails}
      >
        {showWalletDetails ? t('hide_wallet_details') : t('view_all_wallet_details')}
      </button>
      {showWalletDetails ? (
        <Row className="g-3 mb-3">
          {detailMetrics.map((metric) => (
            <Col key={metric.label} xs={6} lg={3}>
              <StatTile label={metric.label} value={metric.value} />
            </Col>
          ))}
        </Row>
      ) : null}
    </div>
  );
}
