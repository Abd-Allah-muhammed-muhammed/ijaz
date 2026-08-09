import { ConversationAttachment, ConversationMessage } from '@/shared/types/models';
import React from 'react';
import Attachments from '@/shared/chat/components/bubbles/attachments';
import { highlightSearchTerm } from '@/shared/chat/components/bubbles/chat-search-highlight';
import RelativeTimestamp from '@/shared/chat/components/relative-timestamp';

type Props = {
  conversationMessage: ConversationMessage;
  highlightTerm?: string | null;
};

const hasCaption = (content?: string | null): boolean =>
  Boolean(content && String(content).trim() !== '');

/**
 * Incoming (other party's) bubble — no read-receipt checkmarks (outgoing-only UX).
 */
const MessageIn = ({ conversationMessage, highlightTerm }: Props) => {
  return (
    <div
      className="d-flex justify-content-start mb-8 mw-100 min-w-0"
      data-kt-element="template-in"
    >
      <div
        className="d-flex flex-column align-items-start min-w-0"
        style={{ maxWidth: 'min(100%, 28rem)' }}
      >
        <div className="d-flex align-items-center mb-2 gap-2">
          <div className="symbol symbol-35px symbol-circle flex-shrink-0">
            <img alt="" src={conversationMessage.sender?.image} />
          </div>
          <div className="min-w-0">
            <span
              className="fs-6 fw-semibold text-gray-900 text-truncate d-inline-block"
              style={{ maxWidth: 220 }}
            >
              {conversationMessage.sender?.name}
            </span>
            <RelativeTimestamp
              className="text-muted fs-8 d-block lh-1 mt-1"
              iso={conversationMessage.created_at_iso ?? conversationMessage.created_at}
              fallback={
                typeof conversationMessage.created_at === 'string'
                  ? conversationMessage.created_at
                  : ''
              }
            />
          </div>
        </div>

        <div
          className="px-4 py-3 text-gray-900 fw-normal w-100 min-w-0 overflow-hidden"
          style={{
            maxWidth: '100%',
            borderRadius: '1.125rem 1.125rem 1.125rem 0.35rem',
            background: 'var(--bs-gray-100)',
            boxShadow: '0 1px 2px rgba(16, 24, 40, 0.06)',
            overflowWrap: 'anywhere',
            wordBreak: 'break-word',
          }}
        >
          {Boolean(conversationMessage.attachments?.length) && (
            <Attachments
              attachments={
                conversationMessage.attachments as ConversationAttachment[]
              }
            />
          )}
          {hasCaption(conversationMessage.content) ? (
            <p
              dangerouslySetInnerHTML={{
                // Always escape first (highlightSearchTerm); <mark> only wraps escaped text.
                __html: highlightSearchTerm(String(conversationMessage.content), highlightTerm),
              }}
              className="text-start mb-0 text-break fs-6 lh-base"
              style={{ overflowWrap: 'anywhere', wordBreak: 'break-word' }}
            ></p>
          ) : null}
        </div>
      </div>
    </div>
  );
};

export default MessageIn;
