import React from 'react';
import { Card, Badge, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { Link } from "@inertiajs/react";
import { Order } from "@/types/models";
import { KTIcon } from "@/_metronic/helpers";
import { useTranslation } from "react-i18next";

type Props = {
  order: Order,
  url?: string
}

const OrderCard = ({ order, url = '#' }: Props) => {
  const {t} = useTranslation();
  return (
    <Link href={url} className="text-decoration-none">
      <Card className="h-100 border-0 shadow-sm rounded-4 hover-elevate-up overflow-hidden position-relative">
        {/* Status Strip */}
        <div className={`position-absolute top-0 start-0 bottom-0 border-start border-4 border-${order.status.color}`} style={{ width: '4px' }}></div>

        <Card.Body className="p-0 d-flex flex-column h-100">

          {/* Header Section */}
          <div className="d-flex align-items-center justify-content-between p-5 pb-3">
            {/* User Info */}
            <div className="d-flex align-items-center">
              <div className="symbol symbol-40px me-3">
                {order.user?.image ? (
                  <img src={order.user.image} alt={order.user.name} className="object-fit-cover rounded-circle" />
                ) : (
                  <div className={`symbol-label bg-light-${order.status.color} text-${order.status.color} fs-4 fw-bold rounded-circle`}>
                    {order.user?.name?.charAt(0).toUpperCase()}
                  </div>
                )}
              </div>
              <div className="d-flex flex-column">
                <span className="text-gray-900 fw-bold text-hover-primary fs-6 line-clamp-1" title={order.user?.name}>
                  {order.user?.name}
                </span>
                <div className="d-flex align-items-center text-gray-500 fs-8">
                  <span className="me-1">{new Date(order.created_at).toLocaleDateString()}</span>
                </div>
              </div>
            </div>

            {/* Status Badge */}
            <Badge bg={`light-${order.status.color}`} className={`text-${order.status.color} fw-bold px-3 py-2 rounded-pill`}>
              {order.status.label}
            </Badge>
          </div>

          <div className="separator separator-dashed mx-5"></div>

          {/* Body Section */}
          <div className="p-5 pt-4 grow d-flex flex-column">
            {/* Title */}
            {order.title && (
              <h5 className="text-gray-900 fw-bolder mb-3 text-truncate lh-base" title={order.title}>
                {order.title}
              </h5>
            )}

            {/* Budget */}
            <div className="mb-4">
              <span className="text-gray-500 fs-7 fw-semibold d-block mb-1 text-uppercase ls-1">{t('budget')}</span>
              <div className="d-flex align-items-center">
                <KTIcon iconName="wallet" className="fs-2 text-primary me-2" />
                <span className="text-gray-800 fw-bolder fs-4">
                  {order.budget_start} - {order.budget_end}
                </span>
              </div>
            </div>

            {/* Meta Grid */}
            <div className="d-flex flex-wrap gap-3 mt-auto">
              <div className="d-flex align-items-center bg-gray-100 rounded px-2 py-1" title={t('expected_time')}>
                <KTIcon iconName="time" className="fs-5 text-gray-600 me-2" />
                <span className="text-gray-700 fw-bold fs-7">{order.expected_time}</span>
              </div>

              {(order.city?.translation?.title || order.region?.translation?.title) && (
                <div className="d-flex align-items-center bg-gray-100 rounded px-2 py-1" title={t('location')}>
                  <KTIcon iconName="geolocation" className="fs-5 text-gray-600 me-2" />
                  <span className="text-gray-700 fw-bold fs-7 line-clamp-1" style={{ maxWidth: '150px' }}>
                    {[order.city?.translation?.title, order.region?.translation?.title].filter(Boolean).join(' - ')}
                  </span>
                </div>
              )}
              {/* Category */}
              {order.category?.translation?.title && (
                <div className="d-flex align-items-center bg-gray-100 rounded px-2 py-1" title={t('category')}>
                  <KTIcon iconName="category" className="fs-5 text-gray-600 me-2" />
                  <span className="text-gray-700 fw-bold fs-7 line-clamp-1" style={{ maxWidth: '150px' }}>
                    {order.category.translation.title}
                  </span>
                </div>
              )}
            </div>
          </div>

          {/* Footer Section */}
          <div className="card-footer bg-light-primary py-3 px-5 border-0 d-flex justify-content-between align-items-center">
            <div className="d-flex align-items-center gap-4">
              <OverlayTrigger overlay={<Tooltip id={`offers-${order.id}`}>{t('offers')}</Tooltip>}>
                <div className="d-flex align-items-center text-primary">
                  <KTIcon iconName="message-text-2" className="fs-4 me-1" />
                  <span className="fw-bolder">{order.offers_count || 0}</span>
                </div>
              </OverlayTrigger>
              <OverlayTrigger overlay={<Tooltip id={`media-${order.id}`}>{t('files')}</Tooltip>}>
                <div className="d-flex align-items-center text-gray-600">
                  <KTIcon iconName="paper-clip" className="fs-4 me-1" />
                  <span className="fw-bolder">{order.media_count || 0}</span>
                </div>
              </OverlayTrigger>
            </div>

            <div className="d-flex align-items-center text-primary fw-bold fs-7">
              {t('view_details')}
              <KTIcon iconName="arrow-right" className="fs-4 ms-1" />
            </div>
          </div>

        </Card.Body>
      </Card>
    </Link>
  );
}
export default OrderCard;
