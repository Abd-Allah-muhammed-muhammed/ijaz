import { Order } from "@/types/models"
import { Badge, Card, Col, OverlayTrigger, Row, Tooltip } from "react-bootstrap";
import { KTIcon } from "@/_metronic/helpers";
import { useTranslation } from "react-i18next";

type Props = {
  order: Order
}

const OffersTap = ({ order }: Props) => {
  const { t } = useTranslation();
  return (
    <Row className="transition-all duration-300">
      <Col xl={12}>
        <Card className="shadow-lg border-0 rounded-4 h-100 bg-white">
          <Card.Header className="pt-7">
            <Card.Title className='text-gray-900 fw-bold fs-3'>{t('offers_submitted')}</Card.Title>
            <div className="card-toolbar">
              <span className="badge badge-light-primary fw-bold me-auto px-4 py-3">{t('total_offers')}: {order.offers?.length || 0}</span>
            </div>
          </Card.Header>
          <Card.Body className="p-0">
            <div className="table-responsive">
              <table className="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer px-5 table-hover">
                <thead className="bg-light">
                  <tr className="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th className="min-w-150px ps-9 rounded-start">{t('provider')}</th>
                    <th className="min-w-100px">{t('price')}</th>
                    <th className="min-w-100px">{t('status')}</th>
                    <th className="min-w-150px">{t('date')}</th>
                    <th className="min-w-200px rounded-end">{t('description')}</th>
                  </tr>
                </thead>
                <tbody className="text-gray-700 fw-semibold">
                  {order.offers?.map((offer) => (
                    <tr key={offer.id} className="transition-all hover:bg-gray-50">
                      <td className="ps-9">
                        <div className="d-flex align-items-center">
                          <div className="symbol symbol-40px symbol-circle me-3">
                            {offer.provider?.logo ? (
                              <img src={offer.provider?.logo} alt={offer.provider?.name} />
                            ) : (
                              <div className="symbol-label bg-light-info text-info fw-bold fs-5">
                                {offer.provider?.name?.charAt(0)}
                              </div>
                            )}
                          </div>
                          <div className="d-flex flex-column">
                            <a href="#" className="text-gray-900 text-hover-primary mb-1 fw-bold fs-6">{offer.provider?.name}</a>
                          </div>
                        </div>
                      </td>
                      <td><span className="text-success fw-bold fs-6">{offer.price}</span></td>
                      <td>
                        <Badge bg={`light-${offer.status.color}`} text={offer.status.color} className={`border border-${offer.status.color} border-opacity-25 px-3 py-2 rounded-pill fw-bold`}>
                          {offer.status.label}
                        </Badge>
                      </td>
                      <td>
                        <span className="text-gray-500 fs-7">{new Date(offer.created_at).toLocaleDateString()}</span>
                      </td>
                      <td>
                        <OverlayTrigger placement="top" overlay={<Tooltip id={`tooltip-${offer.id}`}>{offer.description}</Tooltip>}>
                          <span className="text-truncate d-inline-block text-gray-600 cursor-pointer" style={{ maxWidth: '200px' }}>{offer.description}</span>
                        </OverlayTrigger>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {(!order.offers || order.offers.length === 0) && (
                <div className="card-body d-flex flex-column align-items-center justify-content-center py-10">
                  <KTIcon iconName="magnifier" className="fs-3x text-muted mb-5" />
                  <span className="fw-semibold text-gray-400 fs-4">{t('no_offers_yet')}</span>
                </div>
              )}
            </div>
          </Card.Body>
        </Card>
      </Col>
    </Row>
  )
}

export default OffersTap
