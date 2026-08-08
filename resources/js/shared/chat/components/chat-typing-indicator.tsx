import React from 'react';
import { useTranslation } from 'react-i18next';
import type { ConversationUser } from '@/shared/types/models';

type Props = {
  user: ConversationUser | null;
};

/**
 * Subtle "Name is typing..." row — matches the Active/online badge treatment
 * in the conversation header (small success dot + muted fs-7 text).
 */
const ChatTypingIndicator = ({ user }: Props) => {
  const { t } = useTranslation();

  if (!user?.name) {
    return null;
  }

  return (
    <div
      className="d-flex align-items-center gap-2 mt-3 mb-1 ps-1"
      aria-live="polite"
      data-kt-chat-typing="1"
    >
      <span className="badge badge-success badge-circle w-10px h-10px flex-shrink-0" />
      <span className="fs-7 fw-semibold text-gray-500 text-truncate">
        {t('{{name}} is typing...', { name: user.name, defaultValue: `${user.name} is typing...` })}
      </span>
    </div>
  );
};

export default ChatTypingIndicator;
