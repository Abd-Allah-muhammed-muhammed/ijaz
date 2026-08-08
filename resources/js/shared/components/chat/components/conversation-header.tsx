import React from 'react';
import type { ConversationUser } from '@/shared/types/models';
import ConversationSearchToolbar from '@/shared/components/chat/components/conversation-search-toolbar';

export type ConversationHeaderProps = {
  user?: ConversationUser | null;
  displayName: string;
  searchOpen: boolean;
  searchInput: string;
  onSearchInputChange: (value: string) => void;
  onToggleSearch: () => void;
  showCloseButton?: boolean;
  onClose?: () => void;
};

/**
 * Default peer chrome: avatar / name / online + search toolbar.
 * Pages that need a custom title use ConversationContent's `headerSlot` instead.
 */
const ConversationHeader = ({
  user,
  displayName,
  searchOpen,
  searchInput,
  onSearchInputChange,
  onToggleSearch,
  showCloseButton = false,
  onClose,
}: ConversationHeaderProps) => {
  const avatarInitial = displayName.replace(/[_\-\\/]/i, ' ').split(' ')[0]?.charAt(0)?.toUpperCase() || '?';

  return (
    <div className='card-header' id='kt_chat_messenger_header'>
      <div className='card-title min-w-0'>
        <div className='d-flex align-items-center me-3 min-w-0'>
          <div className='symbol symbol-45px symbol-circle flex-shrink-0 me-3'>
            {user?.image ? (
              <img alt='' src={user.image} />
            ) : (
              <div className="symbol-label bg-light-primary text-primary fs-4 fw-bold">
                {avatarInitial}
              </div>
            )}
            <div
              className={`symbol-badge bg-success start-100 top-100 border-4 h-15px w-15px ms-n2 mt-n2 ${user?.online ? '' : 'd-none'}`}
            />
          </div>
          <div className='d-flex flex-column me-3 min-w-0'>
            <a
              href='#'
              className='fs-4 fw-bolder text-gray-900 text-hover-primary me-1 mb-2 lh-1 text-truncate d-block'
              style={{ maxWidth: 280 }}
              title={displayName}
            >
              {displayName}
            </a>

            <div className={`mb-0 lh-1 ${user?.online ? '' : 'd-none'}`}>
              <span className='badge badge-success badge-circle w-10px h-10px me-1'></span>
              <span className='fs-7 fw-bold text-gray-500'>Active</span>
            </div>
          </div>
        </div>
      </div>

      <div className='card-toolbar'>
        <ConversationSearchToolbar
          searchOpen={searchOpen}
          searchInput={searchInput}
          onSearchInputChange={onSearchInputChange}
          onToggleSearch={onToggleSearch}
          showCloseButton={showCloseButton}
          onClose={onClose}
        />
      </div>
    </div>
  );
};

export default ConversationHeader;
