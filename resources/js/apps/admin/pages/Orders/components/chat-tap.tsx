import { Col, Card, Row } from "react-bootstrap";
import { useEffect, useMemo } from "react";
import { usePage } from "@inertiajs/react";
import ConversationContent from "@/shared/components/chat/components/conversation-content";
import { useTranslation } from "react-i18next";
import { Conversation, Order } from "@/shared/types/models";
import OrderController from "@/actions/Modules/Orders/Http/Controllers/Dashboard/OrderController";
import usePermissions from "@/shared/hooks/use-permissions";
import { useConversations } from "@/store/use-chat";

type Props = {
  order: Order
}

const ChatTap = ({ order }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canReply = hasPermission('edit orders');
  const { setCurrentSocketId } = useConversations();
  const { auth } = usePage<{ auth: { user?: { socket_id?: string } } }>().props;
  const conversation = (order.conversation ?? null) as Conversation | null;

  useEffect(() => {
    if (auth.user?.socket_id) {
      setCurrentSocketId(auth.user.socket_id);
    }
  }, [auth.user?.socket_id, setCurrentSocketId]);

  const endpoints = useMemo(() => {
    const orderId = order.id as string | number;

    return {
      messagesUrl: (options?: { search?: string; page?: number }) => {
        const query: Record<string, string | number> = {};
        if (options?.search && options.search.trim() !== '') {
          query.search = options.search.trim();
        }
        if (options?.page && options.page > 1) {
          query.page = options.page;
        }
        const params = Object.keys(query).length > 0 ? { query } : undefined;

        return OrderController.conversationMessages(orderId, params).url;
      },
      sendUrl: OrderController.sendConversationMessage(orderId).url,
      typingUrl: OrderController.conversationTyping(orderId).url,
    };
  }, [order.id]);

  return (
    <Row>
      <Col xl={12} className="transition-all duration-300">
        <div style={{ minHeight: 520, height: 'min(70vh, 720px)' }} className="rounded-4 overflow-hidden shadow-lg">
          <ConversationContent
            conversation={conversation}
            endpoints={endpoints}
            showCloseButton={false}
            showComposer={canReply}
            syncSidebar={false}
            emptyFallback={(
              <Card className="shadow-lg border-0 rounded-4 h-100 bg-white">
                <Card.Body className="d-flex align-items-center justify-content-center py-15">
                  <div className="text-center">
                    <h3 className="fw-bold text-gray-900 mb-2">{t('conversation')}</h3>
                    <p className="text-muted mb-0">
                      {t('No conversation yet', { defaultValue: 'No conversation has been started for this order yet.' })}
                    </p>
                  </div>
                </Card.Body>
              </Card>
            )}
          />
        </div>
      </Col>
    </Row>
  );
};

export default ChatTap;
