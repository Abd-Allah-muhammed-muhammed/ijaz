import { Col, Card, Row } from "react-bootstrap";
import { Order } from "@/types/models";
import { useTranslation } from "react-i18next";
import clsx from "clsx";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faStar } from "@fortawesome/free-solid-svg-icons";

type Props = {
  order: Order;
}

const ReviewsTap = ({ order }: Props) => {
  const { t } = useTranslation();
  const reviews = order.reviews || [];
  const providerReview = reviews.find(i => i.reviewer_type === "Provider");
  const userReview = reviews.find(i => i.reviewer_type === "User");


  return (
    <Row>
      <Col md={12}>
        <Card className="shadow-sm border-0 rounded-4 mb-5">
          <Card.Header className="min-h-50px pt-5">
            <Card.Title className="fs-5 fw-bold text-gray-800">{t('customer_review')}</Card.Title>
          </Card.Header>
          <Card.Body>
            {userReview ? (
              <div className="d-flex flex-column">
                <div className="d-flex align-items-center mb-3">
                  <div className="rating">
                    {[...Array(5)].map((_, i) => (
                      <div key={i} className={clsx("rating-label me-1", i < userReview.rating && "checked")}>
                        <FontAwesomeIcon icon={faStar} className={i < userReview.rating ? "text-warning" : "text-gray-300"} />
                      </div>
                    ))}
                  </div>
                  <span className="ms-2 fw-bold text-gray-800 fs-6">{userReview.rating}/5</span>
                </div>
                <div className="p-4 bg-light-info bg-opacity-25 rounded-3 border border-info border-opacity-10">
                  <i className="bi bi-chat-quote-fill fs-3 text-info me-2 opacity-50"></i>
                  <span className="text-gray-800 fs-6 opacity-75 fst-italic">{userReview.comment}</span>
                </div>
              </div>
            ) :
              t('no_reveiw_yet')
            }

          </Card.Body>
        </Card>
      </Col>

      <Col md={12}>
        <Card className="shadow-sm border-0 rounded-4 mb-5">
          <Card.Header className="min-h-50px pt-5">
            <Card.Title className="fs-5 fw-bold text-gray-800">{t('provider_review')}</Card.Title>
          </Card.Header>
          <Card.Body>
            {providerReview ? (
              <div className="d-flex flex-column">
                <div className="d-flex align-items-center mb-3">
                  <div className="rating">
                    {[...Array(5)].map((_, i) => (
                      <div key={i} className={clsx("rating-label me-1", i < providerReview.rating && "checked")}>
                        <FontAwesomeIcon icon={faStar} className={i < providerReview.rating ? "text-warning" : "text-gray-300"} />
                      </div>
                    ))}
                  </div>
                  <span className="ms-2 fw-bold text-gray-800 fs-6">{providerReview.rating}/5</span>
                </div>
                <div className="p-4 bg-light-primary bg-opacity-25 rounded-3 border border-primary border-opacity-10">
                  <i className="bi bi-chat-quote-fill fs-3 text-primary me-2 opacity-50"></i>
                  <span className="text-gray-800 fs-6 opacity-75 fst-italic">{providerReview.comment}</span>
                </div>
              </div>
            ) :
              t('no_reveiw_yet')
            }
          </Card.Body>
        </Card>
      </Col>
    </Row>
  );
};
export default ReviewsTap;