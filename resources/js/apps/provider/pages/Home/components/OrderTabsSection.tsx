import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, Col, Nav, Row, Tab } from 'react-bootstrap';
import { KTIcon } from '@/vendor/metronic/helpers';
import { EmptyState } from '@/shared/components/ui';
import OrderCard from '@/shared/components/order/order-card';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import type { Order } from '@/shared/types/models';
import type { OrderTabCounts, OrderTabKey } from '@/apps/provider/pages/Home/hooks/use-order-tab-counts';

export type OrderTabsSectionProps = {
  counts: OrderTabCounts;
  pendingOrders: Order[];
  approvedOrders: Order[];
  inProgressOrders: Order[];
  endedByProviderOrders: Order[];
};

type TabDefinition = {
  key: OrderTabKey;
  labelKey:
    | 'waiting_for_offer_approval'
    | 'waiting_for_payment'
    | 'in_progress'
    | 'waiting_for_client_review';
  orders: Order[];
};

export default function OrderTabsSection({
  counts,
  pendingOrders,
  approvedOrders,
  inProgressOrders,
  endedByProviderOrders,
}: OrderTabsSectionProps) {
  const { t } = useTranslation();

  const tabs: TabDefinition[] = [
    {
      key: 'pending',
      labelKey: 'waiting_for_offer_approval',
      orders: pendingOrders,
    },
    {
      key: 'approved',
      labelKey: 'waiting_for_payment',
      orders: approvedOrders,
    },
    {
      key: 'in_progress',
      labelKey: 'in_progress',
      orders: inProgressOrders,
    },
    {
      key: 'ended_by_provider',
      labelKey: 'waiting_for_client_review',
      orders: endedByProviderOrders,
    },
  ];

  return (
    <Card className="h-100">
      <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4 flex-wrap gap-2">
        <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">{t('my orders')}</h3>
        <div className="card-toolbar mb-0">
          <Link href={OrderController.index().url} className="btn btn-sm btn-light">
            {t('show_all')}
          </Link>
        </div>
      </Card.Header>
      <Card.Body className="pt-0">
        <Tab.Container defaultActiveKey="pending">
          <Nav
            variant="pills"
            className="nav-pills-custom position-relative mb-6 mb-md-9 flex-nowrap overflow-auto gap-3 gap-md-5"
            role="tablist"
          >
            {tabs.map((tab) => (
              <Nav.Item key={tab.key} className="flex-shrink-0">
                <Nav.Link
                  className="btn btn-color-gray-600 btn-active-color-primary d-flex justify-content-center align-items-center gap-2 h-100 w-100 border-0 px-2 px-md-0"
                  eventKey={tab.key}
                >
                  <span className="nav-text fw-bold fs-7 fs-md-6 text-gray-600 text-nowrap">
                    {t(tab.labelKey)}
                  </span>
                  <span className="badge badge-circle badge-light-primary fw-bold">
                    {counts[tab.key]}
                  </span>
                  <span
                    className="bullet-custom position-absolute z-index-2 h-3px bottom-0 start-0 end-0 rounded bg-primary"
                    aria-hidden="true"
                  />
                </Nav.Link>
              </Nav.Item>
            ))}
          </Nav>
          <Tab.Content>
            {tabs.map((tab) => (
              <Tab.Pane key={tab.key} eventKey={tab.key}>
                {tab.orders.length === 0 ? (
                  <EmptyState
                    icon={<KTIcon iconName="basket" className="fs-5x mb-5 text-gray-300" />}
                    title={t('no_orders_found')}
                  />
                ) : (
                  <Row className="g-5">
                    {tab.orders.map((order) => (
                      <Col key={order.id} xs={12} sm={6} xl={tab.orders.length === 1 ? 12 : 6}>
                        <OrderCard
                          order={order}
                          url={OrderController.show(order.id as string).url}
                        />
                      </Col>
                    ))}
                  </Row>
                )}
              </Tab.Pane>
            ))}
          </Tab.Content>
        </Tab.Container>
      </Card.Body>
    </Card>
  );
}
