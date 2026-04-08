import { KTIcon } from "@/_metronic/helpers"
import { GuaranteeRequest } from "@/types/models"
import { Card, Col, Row } from "react-bootstrap"
import { useTranslation } from "react-i18next"

type Props = {
  item: GuaranteeRequest
}
const OverviewTap = ({ item }: Props) => {
  const { t } = useTranslation();

  return (
    <Row>
      <Col xl="4" className="transition-all duration-300">
        <Card className="card-flush shadow-sm border-0 rounded-4 h-100 bg-white">
          <Card.Header className="pt-7">
            <Card.Title><h3 className='text-gray-900 fw-bold'>{t('details')}</h3></Card.Title>
          </Card.Header>
          <Card.Body className="pt-2">
            <div className="mb-7">
              <label className="fw-semibold text-muted text-uppercase fs-8 mb-2 d-block">{t('description')}</label>
              <div className="fs-6 text-gray-800 text-pre-line lh-lg bg-light p-4 rounded-3 border border-gray-100">
                {item.description}
              </div>
            </div>

            {/* Attachments */}
            <div className="mb-0">
              <label className="fw-semibold text-muted text-uppercase fs-8 mb-3 d-block">{t('files')}</label>
              <div className="d-flex flex-column gap-3">
                {item.media && item.media.length > 0 ? item.media.map(media => (
                  <div key={media.id} className="d-flex align-items-center p-3 rounded-3 bg-light-success bg-opacity-25 border border-success border-opacity-10 transition-hover-shadow">
                    <div className="symbol symbol-35px me-3">
                      <span className="symbol-label bg-white text-success shadow-sm">
                        <KTIcon iconName="file" className="fs-2" />
                      </span>
                    </div>
                    <div className="flex-grow-1 text-truncate">
                      <div className="fw-bold text-gray-800 text-truncate fs-6">{media.file_name}</div>
                      <div className="text-muted fs-8">{media.size}</div>
                    </div>
                    <a href={media.url} target="_blank" rel="noreferrer" className="btn btn-sm btn-icon btn-bg-white btn-active-color-primary shadow-sm rounded-circle">
                      <KTIcon iconName="arrow-down" className="fs-4" />
                    </a>
                  </div>
                )) : t('no_files')}
              </div>
            </div>
          </Card.Body>
        </Card>
      </Col>
      <Col xl={8} className="transition-all duration-300">
      {item.provider ? (
        <div className="d-flex flex-column gap-5 gap-xl-10">
          <div className=" border-0 rounded-4">
              <div className="d-flex flex-column gap-5">
                <div className="d-flex align-items-center p-3 rounded-3 bg-white border border-gray-200">
                  <div className="symbol symbol-50px symbol-circle me-3 border border-2 border-white shadow-sm position-relative">
                    <img src={item.provider.logo || "https://ui-avatars.com/api/?name=" + item.provider.name} alt={item.provider.name} />
                    <div className="position-absolute translate-middle bottom-0 start-100 mb-1 bg-success rounded-circle border border-2 border-white h-15px w-15px"></div>
                  </div>
                  <div className='d-flex flex-column grow gap-3'>
                    <div className="d-flex align-items-center mb-1">
                      <span className="text-gray-900 fw-bold text-hover-primary fs-6 me-1">{item.provider.name}</span>
                      <KTIcon iconName="verify" className="fs-2 text-primary" />
                    </div>

                    <div className="d-flex align-items-center text-gray-400 fs-7 mb-1">
                      <div className="rating d-flex align-items-center">
                        <i className="bi bi-star-fill fs-5 text-warning me-1"></i>
                        <span className="fw-bold text-gray-700">{item.provider.average_rating || 0}</span>
                      </div>
                    </div>

                    <div className="d-flex flex-column text-gray-600 fs-7">
                      {item.provider.phone && (
                        <div className="d-flex align-items-center mb-1">
                          <KTIcon iconName="phone" className="fs-4 me-2" />
                          <span dir="ltr">{item.provider.phone}</span>
                        </div>
                      )}
                      {item.provider.email && (
                        <div className="d-flex align-items-center">
                          <KTIcon iconName="sms" className="fs-4 me-2" />
                          <span>{item.provider.email}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                <div className="bg-light-primary rounded-3 border border-primary border-opacity-10 p-4">
                    <div className="d-flex align-items-center mb-3">
                    <KTIcon iconName="bill" className="fs-2 text-primary me-2" />
                    <span className="text-gray-900 fw-bold fs-6">{t('offer_details')}</span>
                    </div>

                    <div className="d-flex justify-content-between align-items-center mb-2 border-bottom border-primary border-opacity-10 pb-2">
                    <span className="text-gray-600 fw-semibold">{t('amount')}</span>
                    <span className="text-primary fw-bolder fs-5">{item.amount}</span>
                    </div>

                    <div className="d-flex justify-content-between align-items-center mb-2 border-bottom border-primary border-opacity-10 pb-2">
                    <span className="text-gray-600 fw-semibold">{t('fees')}</span>
                    <span className="text-primary fw-bolder fs-5">{item.fees}</span>
                    </div>

                    <div className="d-flex justify-content-between align-items-center mb-2 border-bottom border-primary border-opacity-10 pb-2">
                    <span className="text-gray-600 fw-semibold">{t('total')}</span>
                    <span className="text-primary fw-bolder fs-5">{item.total}</span>
                    </div>
                </div>
            </div>
            </div>
        </div>
      ):(
        <div className="alert alert-dismissible bg-light-warning border border-warning border-dashed d-flex flex-column flex-sm-row p-4 rounded-3 mb-0">
          <div className="d-flex align-items-center">
            <KTIcon iconName="information-5" className="fs-2 text-warning me-3" />
            <span className="text-gray-700 fw-semibold fs-7">{t('item_has_no_provider_yet')}</span>
          </div>
        </div>
      )}
      </Col>
    </Row>
  )
}

export default OverviewTap
