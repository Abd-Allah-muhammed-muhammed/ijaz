import { Col, Card, Row } from 'react-bootstrap';
import { useEffect, useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { ConversationContent, useConversations } from '@/shared/chat';
import { useTranslation } from 'react-i18next';
import { Conversation } from '@/shared/types/models';
import GuarantorDashboardController from '@/actions/Modules/Guarantor/Http/Controllers/Dashboard/GuarantorController';
import usePermissions from '@/shared/hooks/use-permissions';

type Props = {
  guarantorRequestId: string | number;
  conversation?: Conversation | null;
};

const ChatTap = ({ guarantorRequestId, conversation }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canReply = hasPermission('manage guarantors');
  const { setCurrentSocketId } = useConversations();
  const { auth } = usePage<{ auth: { user?: { socket_id?: string } } }>().props;
  const activeConversation = (conversation ?? null) as Conversation | null;

  useEffect(() => {
    if (auth.user?.socket_id) {
      setCurrentSocketId(auth.user.socket_id);
    }
  }, [auth.user?.socket_id, setCurrentSocketId]);

  const endpoints = useMemo(() => {
    const typingFn = GuarantorDashboardController.conversationTyping;
    const typingUrl =
      typeof typingFn === 'function' ? typingFn(guarantorRequestId).url : null;

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

        return GuarantorDashboardController.conversationMessages(guarantorRequestId, params).url;
      },
      sendUrl: GuarantorDashboardController.sendConversationMessage(guarantorRequestId).url,
      typingUrl,
    };
  }, [guarantorRequestId]);

  return (
    <Row>
      <Col xl={12} className="transition-all duration-300">
        <div style={{ minHeight: 520, height: 'min(70vh, 720px)' }} className="rounded-4 overflow-hidden shadow-lg">
          <ConversationContent
            conversation={activeConversation}
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
                      {t('guarantor.no_conversation_yet', {
                        defaultValue: 'No conversation has been started for this guarantor request yet.',
                      })}
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
