import { Col, Row } from 'react-bootstrap';
import { StatTile } from '@/shared/components/ui';
import type { MetricTileData } from '@/apps/provider/types/metric-tile-data';

export type WalletMetricsProps = {
  metrics: MetricTileData[];
};

export default function WalletMetrics({ metrics }: WalletMetricsProps) {
  return (
    <Row className="g-3 mb-5">
      {metrics.map((metric) => (
        <Col key={metric.label} xs={12} sm={4}>
          <StatTile label={metric.label} value={metric.value} />
        </Col>
      ))}
    </Row>
  );
}
