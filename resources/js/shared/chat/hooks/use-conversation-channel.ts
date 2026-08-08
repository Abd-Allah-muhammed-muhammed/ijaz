import { useEffect, type Dispatch, type MutableRefObject, type RefObject, type SetStateAction } from 'react';
import { ChatEventEnum } from '@/Enums/Chat';
import type { Conversation, ConversationMessage, ConversationUser } from '@/shared/types/models';
import { buildSidebarPreviewFromMessage, isNearBottom } from '@/shared/chat/utils/conversation';

type UseConversationChannelArgs = {
  activeConversation: Conversation | null | undefined;
  isControlled: boolean;
  prevConversation: Conversation | null | undefined;
  currentSocketId?: string;
  syncSidebar: boolean;
  messagesBoxRef: RefObject<HTMLDivElement | null>;
  ignoreScrollRef: MutableRefObject<boolean>;
  stickToBottomRef: MutableRefObject<boolean>;
  activeSearchRef: MutableRefObject<string>;
  unreadMessageIdsRef: MutableRefObject<Set<string>>;
  setMessages: Dispatch<SetStateAction<ConversationMessage[]>>;
  setLoadingMessages: Dispatch<SetStateAction<boolean>>;
  setErrorFileIndexes: Dispatch<SetStateAction<number[]>>;
  setSearchOpen: Dispatch<SetStateAction<boolean>>;
  setSearchInput: Dispatch<SetStateAction<string>>;
  setActiveSearch: Dispatch<SetStateAction<string>>;
  fetchMessages: (conversation: Conversation, options?: { search?: string; page?: number }) => Promise<{
    data: {
      data?: {
        items?: ConversationMessage[];
        paginate?: { has_more_pages?: boolean; current_page?: number };
      };
    };
  }>;
  resetPagination: (hasMore: boolean, page: number) => void;
  notifyIncomingMessage: (isOwn: boolean) => void;
  clearTyping: () => void;
  handleRemoteTyping: (user: ConversationUser) => void;
  updateConversationForNewMessages: (conversation: Conversation) => void;
};

/**
 * Echo presence channel: join/leave, new-message, typing, messages-read, joining
 * optimistic read receipts, plus initial message fetch for the active conversation.
 */
export function useConversationChannel({
  activeConversation,
  isControlled,
  prevConversation,
  currentSocketId,
  syncSidebar,
  messagesBoxRef,
  ignoreScrollRef,
  stickToBottomRef,
  activeSearchRef,
  unreadMessageIdsRef,
  setMessages,
  setLoadingMessages,
  setErrorFileIndexes,
  setSearchOpen,
  setSearchInput,
  setActiveSearch,
  fetchMessages,
  resetPagination,
  notifyIncomingMessage,
  clearTyping,
  handleRemoteTyping,
  updateConversationForNewMessages,
}: UseConversationChannelArgs): void {
  useEffect(() => {
    if (!activeConversation) {
      return;
    }

    if (!isControlled && activeConversation.id === prevConversation?.id) {
      return;
    }

    setMessages([]);
    setLoadingMessages(true);
    setErrorFileIndexes([]);
    stickToBottomRef.current = true;

    if (!isControlled && prevConversation) {
      window.Echo.leave(`chats.${prevConversation.id}`);
    }

    window.Echo.join(`chats.${activeConversation.id}`)
      .listen(`.${ChatEventEnum.New_Message}`, (incoming: ConversationMessage) => {
        const isOwnMessage = incoming.sender?.socket_id === currentSocketId;
        notifyIncomingMessage(Boolean(isOwnMessage));

        // While filtering, only surface live messages that still match the query.
        const search = activeSearchRef.current.trim().toLowerCase();
        if (search !== '') {
          const content = String(incoming.content ?? '').toLowerCase();
          if (!content.includes(search)) {
            return;
          }
        }

        // Only follow if the user was already at/near the bottom.
        if (messagesBoxRef.current && !ignoreScrollRef.current) {
          stickToBottomRef.current = isNearBottom(messagesBoxRef.current);
        }
        clearTyping();
        setMessages((prevMessages) => [...prevMessages, incoming]);
        if (syncSidebar) {
          updateConversationForNewMessages(
            buildSidebarPreviewFromMessage(incoming, activeConversation),
          );
        }
      })
      .listen(`.${ChatEventEnum.Typing}`, (typing: ConversationUser) => {
        handleRemoteTyping(typing);
      })
      .listen(`.${ChatEventEnum.Messages_Read}`, (payload: {
        conversation_id?: string;
        message_ids?: string[];
        read_at?: string;
      }) => {
        const ids = new Set((payload.message_ids ?? []).map(String));
        if (ids.size === 0) {
          return;
        }

        const readAt = payload.read_at ?? new Date().toISOString();

        setMessages((prevMessages) => prevMessages.map((message) => (
          ids.has(String(message.id))
            ? { ...message, read_at: readAt as unknown as Date }
            : message
        )));

        if (syncSidebar && activeConversation?.last_message && ids.has(String(activeConversation.last_message.id))) {
          updateConversationForNewMessages({
            ...activeConversation,
            last_message: {
              ...activeConversation.last_message,
              read_at: readAt as unknown as Date,
            },
          });
        }
      })
      .joining((joiningUser: ConversationUser) => {
        if (joiningUser.socket_id !== currentSocketId) {
          setMessages((prevMessages) => {
            const unreadIds = unreadMessageIdsRef.current;
            if (unreadIds.size === 0) {
              return prevMessages;
            }

            const readAt = new Date();
            let changed = false;
            const next = prevMessages.map((messageItem) => {
              if (!unreadIds.has(String(messageItem.id)) || messageItem.read_at) {
                return messageItem;
              }
              changed = true;
              return { ...messageItem, read_at: readAt as unknown as Date };
            });

            unreadMessageIdsRef.current = new Set();
            return changed ? next : prevMessages;
          });
        }
      });

    let cancelled = false;

    setSearchOpen(false);
    setSearchInput('');
    setActiveSearch('');
    activeSearchRef.current = '';

    fetchMessages(activeConversation)
      .then(({ data: response }) => {
        if (cancelled) {
          return;
        }

        // API paginates newest-first (listForConversation ->latest()). Chat UI
        // needs chronological within the page (oldest → newest, newest at bottom).
        // Same pattern as ConversationMessageRepository::listRecentForConversation.
        const items = response?.data?.items;
        const paginate = response?.data?.paginate;
        stickToBottomRef.current = true;
        setMessages(Array.isArray(items) ? [...items].reverse() : []);
        resetPagination(
          Boolean(paginate?.has_more_pages),
          paginate?.current_page ?? 1,
        );
      })
      .catch(() => {
        if (!cancelled) {
          setMessages([]);
          resetPagination(false, 1);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoadingMessages(false);
        }
      });

    return () => {
      cancelled = true;
      window.Echo.leave(`chats.${activeConversation.id}`);
      setMessages([]);
    };
  // Intentionally keyed on conversation identity — same as pre-refactor.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeConversation?.id, isControlled]);
}
