import { ConversationAttachment, ConversationMessage } from '@/shared/types/models';
import React from 'react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import Attachments from '@/shared/components/chat/components/attachments';
import { highlightSearchTerm } from '@/shared/components/chat/components/chat-search-highlight';

type Props = {
  conversationMessage: ConversationMessage;
  highlightTerm?: string | null;
};

const hasCaption = (content?: string | null): boolean =>
  Boolean(content && String(content).trim() !== '');

const MessageOut = ({ conversationMessage, highlightTerm }: Props) => {
  const { t } = useTranslation();
  return (
    <div
      className="d-flex justify-content-end mb-10 mw-100 min-w-0"
      data-kt-element="template-out"
    >
      <div
        className="d-flex flex-column align-items-end min-w-0"
        style={{ maxWidth: '100%' }}
      >
        <div className="d-flex align-items-center mb-2">
          <div className="me-3 min-w-0 text-end">
            {/* Backend / broadcast payloads use shortAbsoluteDiffForHumans(), not ISO. */}
            <span className="text-muted fs-7 mb-1">
              {String(conversationMessage.created_at ?? '')}
            </span>
            <a
              href="#"
              className="fs-5 fw-bolder text-gray-900 text-hover-primary ms-1"
            >
              {t('You')}
            </a>
          </div>
          <div className="symbol symbol-35px symbol-circle">
            <img alt="Pic" src={conversationMessage.sender?.image} />
          </div>
        </div>
        <div
          className="p-3 rounded bg-light-primary text-gray-900 fw-bold w-100 d-flex flex-column min-w-0 overflow-hidden"
          style={{
            maxWidth: 400,
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
                __html: highlightTerm
                  ? highlightSearchTerm(String(conversationMessage.content), highlightTerm)
                  : String(conversationMessage.content),
              }}
              className="text-end mb-2 text-break"
              style={{ overflowWrap: 'anywhere', wordBreak: 'break-word' }}
            ></p>
          ) : null}
          <div className="d-flex justify-content-start">
            {conversationMessage.read_at ? (
              <KTIcon iconName="double-check" className="text-primary fs-1" />
            ) : (
              <KTIcon iconName="check" className="text-muted fs-1" />
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default MessageOut;
