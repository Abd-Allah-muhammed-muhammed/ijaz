import { ConversationAttachment, ConversationMessage } from '@/shared/types/models';
import React from 'react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import Attachments from '@/shared/chat/components/attachments';
import { highlightSearchTerm } from '@/shared/chat/utils/search-highlight';
import RelativeTimestamp from '@/shared/chat/components/relative-timestamp';

type Props = {
  conversationMessage: ConversationMessage;
  highlightTerm?: string | null;
};

const hasCaption = (content?: string | null): boolean =>
  Boolean(content && String(content).trim() !== '');

/**
 * Outgoing (current user) bubble — WhatsApp-style sent/read ticks only here.
 * - Single muted check = sent
 * - Double primary check = read by the other core participant
 */
const MessageOut = ({ conversationMessage, highlightTerm }: Props) => {
  const { t } = useTranslation();
  const isRead = Boolean(conversationMessage.read_at);

  return (
    <div
      className="d-flex justify-content-end mb-8 mw-100 min-w-0"
      data-kt-element="template-out"
    >
      <div
        className="d-flex flex-column align-items-end min-w-0"
        style={{ maxWidth: 'min(100%, 28rem)' }}
      >
        <div className="d-flex align-items-center mb-2 gap-2">
          <div className="min-w-0 text-end">
            <span className="fs-6 fw-semibold text-gray-900">{t('You')}</span>
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
          <div className="symbol symbol-35px symbol-circle flex-shrink-0">
            <img alt="" src={conversationMessage.sender?.image} />
          </div>
        </div>
        <div
          className="px-4 py-3 text-gray-900 fw-normal w-100 d-flex flex-column min-w-0 overflow-hidden"
          style={{
            maxWidth: '100%',
            borderRadius: '1.125rem 1.125rem 0.35rem 1.125rem',
            background: 'var(--bs-primary-bg-subtle, #e8f1ff)',
            boxShadow: '0 1px 2px rgba(16, 24, 40, 0.08)',
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
              className="text-end mb-1 text-break fs-6 lh-base"
              style={{ overflowWrap: 'anywhere', wordBreak: 'break-word' }}
            ></p>
          ) : null}
          <div
            className="d-flex align-items-center justify-content-end gap-1 mt-1"
            aria-label={isRead ? t('read', { defaultValue: 'Read' }) : t('sent', { defaultValue: 'Sent' })}
          >
            {isRead ? (
              <KTIcon iconName="double-check" className="text-primary fs-5" />
            ) : (
              <KTIcon iconName="check" className="text-gray-500 fs-5" />
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default MessageOut;
