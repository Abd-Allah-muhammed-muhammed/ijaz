import { ConversationAttachment, ConversationMessage } from '@/shared/types/models';
import React from 'react';
import { KTIcon } from '@/vendor/metronic/helpers';
import Attachments from '@/shared/components/chat/components/attachments';

type Props = {
  conversationMessage: ConversationMessage;
};

const MessageIn = ({ conversationMessage }: Props) => {
  return (
    <div
      className="d-flex justify-content-start mb-10 mw-100 min-w-0"
      data-kt-element="template-in"
    >
      <div
        className="d-flex flex-column align-items-start min-w-0"
        style={{ maxWidth: '100%' }}
      >
        <div className="d-flex align-items-center mb-2">
          <div className="symbol symbol-35px symbol-circle">
            <img alt="Pic" src={conversationMessage.sender?.image} />
          </div>
          <div className="ms-3 min-w-0">
            <a
              href="#"
              className="fs-5 fw-bolder text-gray-900 text-hover-primary me-1 text-break"
            >
              {conversationMessage.sender?.name}
            </a>
            {/* Backend / broadcast payloads use shortAbsoluteDiffForHumans(), not ISO. */}
            <span className="text-muted fs-7 mb-1">
              {String(conversationMessage.created_at ?? '')}
            </span>
          </div>
        </div>

        <div
          className="p-2 rounded bg-light-info text-gray-900 fw-bold w-100 min-w-0"
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
          <p
            dangerouslySetInnerHTML={{ __html: conversationMessage.content }}
            className="text-start mb-0 text-break"
            style={{ overflowWrap: 'anywhere', wordBreak: 'break-word' }}
          ></p>
          <div className="d-flex justify-content-end">
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

export default MessageIn;
