import { ConversationAttachment, ConversationMessage } from '@/types/models';
import React from "react";
import { KTIcon } from "@/_metronic/helpers";
import Attachments from '@/components/chat/components/attachments';

type Props = {
  conversationMessage: ConversationMessage
}

const MessageIn = ({ conversationMessage }: Props) => {
  return (
    <div
      className='d-flex justify-content-start mb-10'
      data-kt-element="template-in"
    >
      <div className='d-flex flex-column align-items align-items-start'>
        <div className='d-flex align-items-center mb-2'>

          <div className='symbol  symbol-35px symbol-circle '>
            <img alt='Pic' src={conversationMessage.sender?.image} />
          </div>
          <div className='ms-3'>
            <a
              href='#'
              className='fs-5 fw-bolder text-gray-900 text-hover-primary me-1'
            >
              {conversationMessage.sender?.name}
            </a>
            <span className='text-muted fs-7 mb-1'>{new Date(conversationMessage.created_at).toLocaleString()}</span>
          </div>

        </div>

        <div
          className='p-2 rounded w-100 bg-light-info text-gray-900 fw-bold mw-lg-400px'
        >
          {Boolean(conversationMessage.attachments?.length) && <Attachments attachments={conversationMessage.attachments as ConversationAttachment[]} />}
          <p dangerouslySetInnerHTML={{ __html: conversationMessage.content }} className='text-start mb-0'></p>
          <div className='d-flex justify-content-end'>
            {
              conversationMessage.read_at ? (
                <KTIcon iconName='double-check' className="text-primary fs-1" />
              ) : <KTIcon iconName='check' className="text-muted fs-1" />
            }
          </div>
        </div>
      </div>
    </div>
  );
}


export default MessageIn
