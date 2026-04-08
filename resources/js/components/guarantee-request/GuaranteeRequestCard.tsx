import React from 'react';
import { Card, Badge, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { Link } from "@inertiajs/react";
import { GuaranteeRequest } from "@/types/models";
import { KTIcon } from "@/_metronic/helpers";
import { useTranslation } from "react-i18next";

type Props = {
  item: GuaranteeRequest,
  url?: string
}

const GuaranteeRequestCard = ({ item, url = '#' }: Props) => {
  const {t} = useTranslation();
  return (
    <Link href={url} className="text-decoration-none">
      <Card className="h-100 border-0 shadow-sm rounded-4 hover-elevate-up overflow-hidden position-relative">
        {/* Status Strip */}
        <div className={`position-absolute top-0 start-0 bottom-0 border-start border-4 border-${item.status.color}`} style={{ width: '4px' }}></div>

        <Card.Body className="p-0 d-flex flex-column h-100">

          {/* Header Section */}
          <div className="d-flex align-items-center justify-content-between p-5 pb-3">
            {/* User Info */}
            <div className="d-flex align-items-center">
              <div className="symbol symbol-40px me-3">
                {item.user?.image ? (
                  <img src={item.user.image} alt={item.user.name} className="object-fit-cover rounded-circle" />
                ) : (
                  <div className={`symbol-label bg-light-${item.status.color} text-${item.status.color} fs-4 fw-bold rounded-circle`}>
                    {item.user?.name?.charAt(0).toUpperCase()}
                  </div>
                )}
              </div>
              <div className="d-flex flex-column">
                <span className="text-gray-900 fw-bold text-hover-primary fs-6 line-clamp-1" title={item.user?.name}>
                  {item.user?.name}
                </span>
                <div className="d-flex align-items-center text-gray-500 fs-8">
                  <span className="me-1">{new Date(item.created_at).toLocaleDateString()}</span>
                </div>
              </div>
            </div>

            {/* Status Badge */}
            <Badge bg={`light-${item.status.color}`} className={`text-${item.status.color} fw-bold px-3 py-2 rounded-pill`}>
              {item.status.label}
            </Badge>
          </div>

          <div className="separator separator-dashed mx-5"></div>

          {/* Body Section */}
          <div className="p-5 pt-4 grow d-flex flex-column">
            {/* Title */}
            {item.title && (
              <h5 className="text-gray-900 fw-bolder mb-3 text-truncate lh-base" title={item.title}>
                {item.title}
              </h5>
            )}

             {/* Provider */}
             {item.provider && (
                <div className="d-flex align-items-center mb-3">
                    <span className="text-gray-600 fs-7 me-1">{t('provider')}:</span>
                    <span className="text-gray-800 fw-bold fs-7">{item.provider.name}</span>
                </div>
            )}


            {/* Amount */}
            <div className="mb-4">
              <span className="text-gray-500 fs-7 fw-semibold d-block mb-1 text-uppercase ls-1">{t('total_amount')}</span>
              <div className="d-flex align-items-center">
                <KTIcon iconName="wallet" className="fs-2 text-primary me-2" />
                <span className="text-gray-800 fw-bolder fs-4">
                  {item.total}
                </span>
              </div>
            </div>
          </div>

          {/* Footer Section */}
          <div className="card-footer bg-light-primary py-3 px-5 border-0 d-flex justify-content-between align-items-center">
            <div className="d-flex align-items-center gap-4">
              <OverlayTrigger overlay={<Tooltip id={`media-${item.id}`}>{t('files')}</Tooltip>}>
                <div className="d-flex align-items-center text-gray-600">
                  <KTIcon iconName="paper-clip" className="fs-4 me-1" />
                  <span className="fw-bolder">{item.media?.length || 0}</span>
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
export default GuaranteeRequestCard;
