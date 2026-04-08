import { Col, Card, Row } from "react-bootstrap";
import ConversationContent from "../conversation-content";
import { useTranslation } from "react-i18next";
import { Order } from "@/types/models";

type Props = {
  order: Order
}

const ChatTap = ({ order }: Props) => {
  const { t } = useTranslation();
  return (
    <Row>
      <Col xl={12} className="transition-all duration-300">
        <Card className="shadow-lg border-0 rounded-4 h-100 bg-white">
          <Card.Header className="pt-7 pb-3 border-0">
            <Card.Title className='flex-column'>
              <h2 className='fw-bold text-gray-900 mb-1'>{t('conversation')}</h2>
              <span className='fs-7 fw-semibold text-muted'>{t('live_chat_history')}</span>
            </Card.Title>
            <div className="card-toolbar">
              <span className="badge badge-light-success fw-bold px-3 py-2 border border-success border-opacity-25 rounded-pill">
                {t('secure_connection')}
              </span>
            </div>
          </Card.Header>
          <Card.Body className="">
            <div className="rounded-bottom-4 overflow-hidden">
              <ConversationContent order={order} />
            </div>
          </Card.Body>
        </Card>
      </Col>
    </Row>
  );
};

export default ChatTap;
