import Conversation from '@/shared/chat/components/inbox/conversation';
import { useConversations } from '@/shared/chat/store';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import { Fragment } from 'react';

const ConversationsList = () => {
  const { conversations, currentSocketId } = useConversations();
  const { t } = useTranslation();

  return (
    <div
      className="scroll-y me-n5 pe-5 h-100"
      data-kt-scroll="true"
      data-kt-scroll-activate="{default: false, lg: true}"
      data-kt-scroll-max-height="100%"
      data-kt-scroll-dependencies="#kt_header, #kt_toolbar, #kt_footer, #kt_chat_contacts_header"
      data-kt-scroll-wrappers="#kt_content, #kt_chat_contacts_body"
      data-kt-scroll-offset="0px"
    >
      {conversations.length === 0 ? (
        <div className="py-20 text-center">
          <KTIcon iconName="messages" className="fs-5x mb-5 text-gray-300" />
          <p className="text-muted fw-semibold fs-5 mb-0">{t('no_conversations_yet')}</p>
        </div>
      ) : (
        conversations.map((chat, index) => (
          <Fragment key={chat.id}>
            <Conversation chat={chat} currentSocketId={currentSocketId} />
            {index !== conversations.length - 1 ? (
              <div className="separator separator-dashed d-none" />
            ) : null}
          </Fragment>
        ))
      )}
    </div>
  );
};

export default ConversationsList;
