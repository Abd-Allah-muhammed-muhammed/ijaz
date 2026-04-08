import {Conversation as Chat} from "@/types/models";
import Conversation from "@/components/chat/components/conversation";
import React, {useContext} from "react";
import {ConversationContext, useConversations} from "@/store/use-chat";

type Props = {
  conversations: Chat[],
  currentSocketId: string
};
const ConversationsList = () => {
  const {conversations, currentSocketId} = useConversations();
  return (

    <div
      className='scroll-y me-n5 pe-5 h-100'
      data-kt-scroll='true'
      data-kt-scroll-activate='{default: false, lg: true}'
      data-kt-scroll-max-height='100%'
      data-kt-scroll-dependencies='#kt_header, #kt_toolbar, #kt_footer, #kt_chat_contacts_header'
      data-kt-scroll-wrappers='#kt_content, #kt_chat_contacts_body'
      data-kt-scroll-offset='0px'
    >
      {conversations.map((chat, index) => (
        <React.Fragment key={chat.id}>
          <Conversation chat={chat} currentSocketId={currentSocketId} />
          {index !== (conversations.length - 1) && (
            <div className='separator separator-dashed d-none'></div>
          )}
        </React.Fragment>

      ))}
    </div>
  );
}

export default ConversationsList
