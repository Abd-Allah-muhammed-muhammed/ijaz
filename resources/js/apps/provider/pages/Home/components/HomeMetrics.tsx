import { Col, Row } from 'react-bootstrap';
import { StatTile } from '@/shared/components/ui';
import type { MetricTileData } from '@/apps/provider/types/metric-tile-data';

export type HomeMetricsProps = {
  metrics: MetricTileData[];
};

export default function HomeMetrics({ metrics }: HomeMetricsProps) {
  return (
    <Row className="mb-5 g-3 g-md-5">
      {metrics.map((metric) => (
        <Col key={metric.label} xs={6} md={3}>
          <StatTile label={metric.label} value={metric.value} />
        </Col>
      ))}
    </Row>
  );
}
