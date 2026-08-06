import { Conversation as Chat } from "@/shared/types/models";
import { useConversations } from "@/store/use-chat";
import { KTIcon } from "@/vendor/metronic/helpers";
import { useTranslation } from "react-i18next";
import clsx from "clsx";

type Props = {
  chat: Chat,
  currentSocketId: string
};
const Conversation = ({ chat, currentSocketId }: Props) => {
  const user = chat.user1?.socket_id !== currentSocketId ? chat.user1 : chat.user2;
  const { setCurrentConversation, setPrevConversation, currentConversation } = useConversations();
  const { t } = useTranslation();
  const displayName = user?.name ?? t('conversation');
  const firstName = displayName.replace(/[_\-\\/]/i, ' ').split(' ')[0] || displayName;
  const isActive = currentConversation?.id === chat.id;

  const lastMessage = chat.last_message;
  const previewBody = (() => {
    const content = (lastMessage?.content ?? '').trim();
    if (content) {
      return content;
    }

    const attachmentCount = lastMessage?.attachments_count
      ?? lastMessage?.attachments?.length
      ?? 0;

    if (attachmentCount > 0 || lastMessage?.has_attachments) {
      return attachmentCount > 1
        ? t('attachments')
        : t('attachment');
    }

    return '';
  })();

  return (
    <button
      type="button"
      className={clsx(
        // Metronic selected-list pattern: bg-light-primary (same family as menu-link.active / notices).
        'd-flex py-4 w-100 border-0 min-w-0 overflow-hidden rounded px-3 text-start',
        isActive ? 'bg-light-primary' : 'bg-transparent bg-hover-light',
      )}
      aria-current={isActive ? 'true' : undefined}
      onClick={() => {
        setPrevConversation(currentConversation);
        setCurrentConversation(chat)
      }}
    >
      <div className='d-flex align-items-center flex-grow-1 min-w-0 overflow-hidden'>
        <div className='symbol symbol-45px symbol-circle flex-shrink-0'>
          {user?.image ? (
            <img alt='Pic' src={user.image} />
          ) : (
            <div className="symbol-label bg-light-primary text-primary fs-4 fw-bold">
              {firstName.charAt(0).toUpperCase()}
            </div>
          )}
          <div
            className={`${user?.socket_id ?? ''} symbol-badge bg-success start-100 top-100 border-4 h-15px w-15px ms-n2 mt-n2 ${user?.online ? '' : 'd-none'}`} />
        </div>

        <div className='ms-5 flex-grow-1 text-start min-w-0 overflow-hidden'>
          <a
            href='#'
            className={clsx(
              'fs-5 fw-bolder mb-2 text-start text-truncate d-block',
              isActive ? 'text-primary' : 'text-gray-900 text-hover-primary',
            )}
            style={{ maxWidth: '100%' }}
            title={displayName}
            onClick={(e) => e.preventDefault()}
          >
            {displayName}
          </a>
          <div className='fw-bold text-gray-500 text-start min-w-0'>
            <div className='d-flex align-items-center justify-content-between min-w-0 gap-2'>
              <span className='flex-grow-1 min-w-0 overflow-hidden text-truncate'>
                {lastMessage?.sender?.socket_id !== currentSocketId ? firstName : t('You')}:&nbsp;
                {previewBody}
              </span>
              {
                lastMessage?.read_at ? (
                  <KTIcon iconName='double-check' className="text-primary fs-1 flex-shrink-0" />
                ) : <KTIcon iconName='check' className="text-muted fs-1 flex-shrink-0" />
              }

            </div>
          </div>
        </div>
      </div>

      <div className='d-flex flex-column align-items-end ms-2 flex-shrink-0'>
        {/* Backend sends Carbon shortAbsoluteDiffForHumans() (e.g. "2h ago"), not ISO — do not Date-parse. */}
        <span className='text-muted fs-7 mb-1'>{chat.last_massage_at || chat.last_message_at || ''}</span>
        {chat.unread_count && chat.unread_count > 0 ? (
          <span className='badge badge-sm badge-circle badge-light-warning'>{chat.unread_count}</span>
        ) : null}
      </div>
    </button>
  );
}


export default Conversation
