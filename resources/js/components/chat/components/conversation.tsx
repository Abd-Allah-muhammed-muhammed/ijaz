import { Conversation as Chat } from "@/types/models";
import { useConversations } from "@/store/use-chat";
import { KTIcon } from "@/_metronic/helpers";
import { useTranslation } from "react-i18next";

type Props = {
  chat: Chat,
  currentSocketId: string
};
const Conversation = ({ chat, currentSocketId }: Props) => {
  const user = chat.user1.socket_id !== currentSocketId ? chat.user1 : chat.user2;
  const { setCurrentConversation, setPrevConversation, currentConversation } = useConversations();
  const { t } = useTranslation();
  return (
    <button className='d-flex  py-4 w-100 bg-transparent border-0' onClick={() => {
      setPrevConversation(currentConversation);
      setCurrentConversation(chat)
    }}>
      <div className='d-flex align-items-center flex-grow-1'>
        <div className='symbol symbol-45px symbol-circle'>
          <img alt='Pic' src={user.image} />
          <div
            className={`${user.socket_id} symbol-badge bg-success start-100 top-100 border-4 h-15px w-15px ms-n2 mt-n2 ${user.online ? '' : 'd-none'}`} />
        </div>

        <div className='ms-5 w-100 text-start'>
          <a href='#' className='fs-5 fw-bolder text-gray-900 text-hover-primary mb-2 text-start'>
            {user.name}
          </a>
          <div className='fw-bold text-gray-500 text-start'>
            <div className='d-flex align-items-center justify-content-between '>
              <span className='flex-grow-1 overflow-hidden'>
                {chat.last_message?.sender?.socket_id !== currentSocketId ? user.name.replace(/[_\-\\/]/i, ' ').split(' ')[0] : t('You')}:&nbsp;
                {chat.last_message?.content}
              </span>
              {
                chat.last_message?.read_at ? (
                  <KTIcon iconName='double-check' className="text-primary fs-1" />
                ) : <KTIcon iconName='check' className="text-muted fs-1" />
              }

            </div>
          </div>
        </div>
      </div>

      <div className='d-flex flex-column align-items-end ms-2'>
        <span className='text-muted fs-7 mb-1'>{chat.last_massage_at ? new Date(chat.last_massage_at).toLocaleString() : ''}</span>
        {chat.unread_count && chat.unread_count > 0 ? (
          <span className='badge badge-sm badge-circle badge-light-warning'>{chat.unread_count}</span>
        ) : null}
      </div>
    </button>
  );
}


export default Conversation
