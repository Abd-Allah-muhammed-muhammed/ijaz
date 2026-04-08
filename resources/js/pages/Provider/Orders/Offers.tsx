import { Head, Link } from '@inertiajs/react';
import ProviderLayout from "@/layouts/provider/ProviderLayout";
import {OrderOffer} from "@/types/models";
import { useTranslation } from 'react-i18next';

import {Badge, Card, Col, Row} from "react-bootstrap";
import {Content} from "@/_metronic/layout/components/content";
import React from "react";
import {PaginationResource} from "@/types";
import Pagination from "../../../components/Table/partials/Pagination";
import OrderController from '@/actions/App/Http/Controllers/Provider/OrderController';

type Props = {
  rows: PaginationResource<OrderOffer>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;

};
const Show = ({rows}: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('offers')}/>
      <Content>
        <Row>
          {rows.data.map(row => (
            <Col key={row.id} xl={4} lg={6} md={6} sm={12} className={'mb-6'}>
              <Link href={OrderController.show(row.order_id)}>
                <Card className="h-100">
                  <Card.Body>
                    <div className="d-flex justify-content-between mb-4">
                      <Card.Title>{t('Order ID')}: {row.order_id}</Card.Title>
                      <Badge bg={row.status.color}>{row.status.label}</Badge>
                    </div>

                    <Card.Text className='mb-4'>
                      <strong>{t('Price')}:</strong> {row.price}
                    </Card.Text>
                    <div className='d-flex justify-content-end align-items-center'>
                      <small className="text-muted">{new Date(row.created_at).toLocaleDateString()}</small>
                    </div>
                  </Card.Body>
                </Card>
              </Link>
            </Col>
          ))}
        </Row>
        <Pagination paginationMeta={rows.meta}/>
      </Content>
    </>
  );
}

Show.layout = (page: React.ReactElement) => <ProviderLayout children={page}/>
export default Show;
