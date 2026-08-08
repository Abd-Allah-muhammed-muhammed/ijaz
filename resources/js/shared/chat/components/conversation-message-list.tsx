import React, { type RefObject } from 'react';
import { useTranslation } from 'react-i18next';
import { Spinner } from 'react-bootstrap';
import type { ConversationMessage, ConversationUser } from '@/shared/types/models';
import MessageIn from '@/shared/chat/components/message-in';
import MessageOut from '@/shared/chat/components/message-out';
import ChatMessagesSkeleton from '@/shared/chat/components/chat-messages-skeleton';
import ChatTypingIndicator from '@/shared/chat/components/chat-typing-indicator';

export type ConversationMessageListProps = {
  messages: ConversationMessage[];
  currentSocketId?: string;
  highlightTerm: string;
  loadingMessages: boolean;
  loadingOlder: boolean;
  reachedBeginning: boolean;
  typingUser: ConversationUser | null;
  messagesBoxRef: RefObject<HTMLDivElement | null>;
  messagesContentRef: RefObject<HTMLDivElement | null>;
  onScroll: () => void;
};

const ConversationMessageList = ({
  messages,
  currentSocketId,
  highlightTerm,
  loadingMessages,
  loadingOlder,
  reachedBeginning,
  typingUser,
  messagesBoxRef,
  messagesContentRef,
  onScroll,
}: ConversationMessageListProps) => {
  const { t } = useTranslation();
  const term = highlightTerm.trim();

  return (
    <div
      ref={messagesBoxRef}
      className='card-body d-flex flex-column flex-grow-1 scroll-y me-n5 pe-5 mb-5 min-w-0'
      onScroll={onScroll}
    >
      <div ref={messagesContentRef} className="d-flex flex-column w-100 min-w-0">
        {loadingOlder ? (
          <div className="d-flex justify-content-center align-items-center py-3" aria-live="polite">
            <Spinner animation="border" size="sm" className="text-primary" />
          </div>
        ) : null}
        {reachedBeginning && messages.length > 0 && term === '' ? (
          <div className="text-center py-3">
            <span className="fs-8 fw-semibold text-gray-500">
              {t('Beginning of conversation', { defaultValue: 'Beginning of conversation' })}
            </span>
          </div>
        ) : null}
        {loadingMessages && messages.length === 0 ? (
          <ChatMessagesSkeleton />
        ) : null}
        {!loadingMessages && messages.length === 0 && term !== '' ? (
          <div className="text-center py-10">
            <div className="fw-semibold text-gray-600 fs-6">
              {t('No messages found', { defaultValue: 'No messages found' })}
            </div>
          </div>
        ) : null}
        {messages.map((messageItem) => {
          const sender = messageItem.sender as ConversationUser;

          if (sender.socket_id !== currentSocketId) {
            return (
              <MessageIn
                conversationMessage={messageItem}
                key={messageItem.id}
                highlightTerm={term || null}
              />
            );
          }
          return (
            <MessageOut
              conversationMessage={messageItem}
              key={messageItem.id}
              highlightTerm={term || null}
            />
          );
        })}
        <ChatTypingIndicator user={typingUser} />
      </div>
    </div>
  );
};

export default ConversationMessageList;
