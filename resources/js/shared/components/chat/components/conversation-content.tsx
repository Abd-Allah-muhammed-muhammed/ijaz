import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { useConversations } from '@/store/use-chat';
import { Conversation, ConversationMessage } from '@/shared/types/models';
import axios from '@/shared/helpers/axios';
import ProviderOrderChatController from '@/actions/Modules/Chat/Http/Controllers/Provider/OrderChatController';
import ChatComposer from '@/shared/components/chat/components/chat-composer';
import ConversationHeader from '@/shared/components/chat/components/conversation-header';
import ConversationMessageList from '@/shared/components/chat/components/conversation-message-list';
import ConversationSearchToolbar from '@/shared/components/chat/components/conversation-search-toolbar';
import { formatFileSize } from '@/shared/components/chat/components/chat-attachment-utils';
import {
  buildSidebarPreviewFromMessage,
  echoSocketId,
  extractValidationErrors,
  isNearBottom,
} from '@/shared/components/chat/components/conversation-content-utils';
import { useChatTyping } from '@/shared/components/chat/hooks/use-chat-typing';
import { useChatNotificationSound } from '@/shared/components/chat/hooks/use-chat-notification-sound';
import { useChatLoadOlderMessages } from '@/shared/components/chat/hooks/use-chat-load-older-messages';
import { useConversationChannel } from '@/shared/components/chat/hooks/use-conversation-channel';
import { useTranslation } from 'react-i18next';
import type { SingleApiResponse, ConversationMessagePaginationResource } from '@/shared/types/api';
import { toast } from 'sonner';

export type ConversationContentEndpoints = {
  messagesUrl: (options?: { search?: string; page?: number }) => string;
  sendUrl: string;
  typingUrl?: string | null;
};

export type ConversationHeaderSlotContext = {
  /**
   * Search toggle + input for the consumer to place in its header row.
   * `null` when there is no active conversation (empty state) — hide the control.
   */
  searchToolbar: React.ReactNode;
};

type Props = {
  /** Controlled conversation (Admin Orders). When omitted, uses Provider chat store. */
  conversation?: Conversation | null;
  /** Override API URLs. Defaults to Provider OrderChatController. */
  endpoints?: ConversationContentEndpoints;
  /** Hide the inbox close (X) button — used for embedded Admin views. */
  showCloseButton?: boolean;
  /**
   * When true (default), renders ConversationHeader (peer name/avatar + search).
   * Ignored when `headerSlot` is provided. When false without `headerSlot`, no
   * internal header strip is rendered (search must be placed via `headerSlot`).
   */
  showHeader?: boolean;
  /**
   * Custom header above the message list. Receives `searchToolbar` so pages can
   * place search inside their own chrome (e.g. Admin Tickets).
   */
  headerSlot?: (ctx: ConversationHeaderSlotContext) => React.ReactNode;
  /** When false, hides ChatComposer (read-only). Default true. */
  showComposer?: boolean;
  /** When false, skips Provider inbox sidebar preview updates. Default true. */
  syncSidebar?: boolean;
  /** Shown when there is no conversation to display. */
  emptyFallback?: React.ReactNode;
};

type ChatMessage = {
  content: string;
  files: File[];
};

const ConversationContent = ({
  conversation: conversationProp,
  endpoints: endpointsProp,
  showCloseButton = true,
  showHeader = true,
  headerSlot,
  showComposer = true,
  syncSidebar = true,
  emptyFallback = null,
}: Props) => {
  const { t } = useTranslation();
  const {
    currentConversation,
    currentSocketId,
    prevConversation,
    setCurrentConversation,
    setPrevConversation,
    updateConversationForNewMessages,
  } = useConversations();
  const isControlled = conversationProp !== undefined;
  const activeConversation = isControlled ? conversationProp : currentConversation;
  const [messages, setMessages] = useState<ConversationMessage[]>([]);
  const [loadingMessages, setLoadingMessages] = useState<boolean>(false);
  const [sending, setSending] = useState<boolean>(false);
  const [errorFileIndexes, setErrorFileIndexes] = useState<number[]>([]);
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchInput, setSearchInput] = useState('');
  const [activeSearch, setActiveSearch] = useState('');
  const [message, setMessage] = useState<ChatMessage>({
    content: '',
    files: [],
  });
  /** Per-instance unread ids for presence .joining() optimistic read receipts. */
  const unreadMessageIdsRef = useRef<Set<string>>(new Set());
  const messagesBox = useRef<HTMLDivElement>(null);
  const messagesContentRef = useRef<HTMLDivElement>(null);
  const activeSearchRef = useRef('');
  /** User is following the live end — auto-scroll on new messages / height growth. */
  const stickToBottomRef = useRef(true);
  /** Ignore onScroll while we programmatically jump to bottom. */
  const ignoreScrollRef = useRef(false);

  const resolveEndpoints = useCallback((conversation: Conversation): ConversationContentEndpoints => {
    if (endpointsProp) {
      return endpointsProp;
    }

    // Defensive: Wayfinder actions are gitignored and only regenerated on
    // `npm run build` (prebuild) / `npm run dev`. A stale server tree without
    // `.typing` used to throw "G.typing is not a function" and crash the page.
    const typingFn = ProviderOrderChatController.typing;
    const typingUrl =
      typeof typingFn === 'function'
        ? typingFn(conversation.id).url
        : null;

    return {
      messagesUrl: (options) => {
        const query: Record<string, string | number> = {};
        if (options?.search && options.search.trim() !== '') {
          query.search = options.search.trim();
        }
        if (options?.page && options.page > 1) {
          query.page = options.page;
        }
        const params = Object.keys(query).length > 0 ? { query } : undefined;

        return ProviderOrderChatController.show(conversation.id, params).url;
      },
      sendUrl: ProviderOrderChatController.send(conversation.id).url,
      typingUrl,
    };
  }, [endpointsProp]);

  const activeEndpoints = activeConversation ? resolveEndpoints(activeConversation) : null;

  const {
    typingUser,
    handleRemoteTyping,
    notifyTyping,
    clearTyping,
    resetEmitThrottle,
  } = useChatTyping({
    conversationId: activeConversation?.id,
    currentSocketId,
    typingUrl: showComposer ? (activeEndpoints?.typingUrl ?? null) : null,
    enabled: showComposer && Boolean(activeConversation),
  });
  const { notifyIncomingMessage } = useChatNotificationSound();

  useEffect(() => {
    activeSearchRef.current = activeSearch;
  }, [activeSearch]);

  const fetchMessages = useCallback((
    conversation: Conversation,
    options?: { search?: string; page?: number },
  ) => {
    const endpoints = resolveEndpoints(conversation);

    return axios.get<SingleApiResponse<ConversationMessagePaginationResource>>(
      endpoints.messagesUrl(options),
    );
  }, [resolveEndpoints]);

  const fetchOlderPage = useCallback(async (page: number) => {
    if (!activeConversation) {
      return { items: [], hasMorePages: false, currentPage: page };
    }

    const { data: response } = await fetchMessages(activeConversation, { page });
    const paginate = response?.data?.paginate;

    return {
      items: Array.isArray(response?.data?.items) ? response.data.items : [],
      hasMorePages: Boolean(paginate?.has_more_pages),
      currentPage: paginate?.current_page ?? page,
    };
  }, [activeConversation, fetchMessages]);

  const prependOlderMessages = useCallback((older: ConversationMessage[]) => {
    setMessages((prev) => {
      const existingIds = new Set(prev.map((m) => m.id));
      const uniqueOlder = older.filter((m) => !existingIds.has(m.id));
      return [...uniqueOlder, ...prev];
    });
  }, []);

  const {
    loadingOlder,
    reachedBeginning,
    resetPagination,
    onScroll: onLoadOlderScroll,
  } = useChatLoadOlderMessages({
    enabled: Boolean(activeConversation) && activeSearch.trim() === '' && !searchOpen,
    messagesBoxRef: messagesBox,
    fetchOlderPage,
    onPrepend: prependOlderMessages,
    onDisableStickToBottom: () => {
      stickToBottomRef.current = false;
    },
  });

  const scrollToBottom = () => {
    const el = messagesBox.current;
    if (!el) {
      return;
    }

    ignoreScrollRef.current = true;
    el.scrollTop = el.scrollHeight;
    requestAnimationFrame(() => {
      ignoreScrollRef.current = false;
      stickToBottomRef.current = true;
    });
  };

  /**
   * Cause of the mid-list landing bug: scrolling only after messages state update
   * (even with double-rAF) runs BEFORE async image/attachment loads finish, so
   * scrollHeight keeps growing afterward. ResizeObserver re-sticks whenever the
   * content height changes while the user is still following the bottom — same
   * idea as WhatsApp.
   */
  useEffect(() => {
    const content = messagesContentRef.current;
    if (!content || typeof ResizeObserver === 'undefined') {
      return;
    }

    const observer = new ResizeObserver(() => {
      if (stickToBottomRef.current) {
        scrollToBottom();
      }
    });

    observer.observe(content);

    return () => {
      observer.disconnect();
    };
  }, [activeConversation?.id]);

  useLayoutEffect(() => {
    if (loadingMessages && messages.length === 0) {
      return;
    }

    if (stickToBottomRef.current) {
      scrollToBottom();
    }
  }, [messages, loadingMessages]);

  useConversationChannel({
    activeConversation,
    isControlled,
    prevConversation,
    currentSocketId,
    syncSidebar,
    messagesBoxRef: messagesBox,
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
  });

  // Debounced in-conversation search — replaces the message list with matches only.
  useEffect(() => {
    if (!activeConversation || !searchOpen) {
      return;
    }

    const term = searchInput.trim();
    const handle = window.setTimeout(() => {
      setActiveSearch(term);
      setLoadingMessages(true);
      stickToBottomRef.current = true;

      fetchMessages(activeConversation, { search: term || undefined })
        .then(({ data: response }) => {
          const items = response?.data?.items;
          const paginate = response?.data?.paginate;
          setMessages(Array.isArray(items) ? [...items].reverse() : []);
          // Search replaces the timeline — no infinite-scroll through search hits.
          resetPagination(false, paginate?.current_page ?? 1);
        })
        .catch(() => {
          setMessages([]);
          resetPagination(false, 1);
        })
        .finally(() => {
          setLoadingMessages(false);
        });
    }, 300);

    return () => {
      window.clearTimeout(handle);
    };
  }, [searchInput, searchOpen, activeConversation?.id, fetchMessages, resetPagination]);

  const clearSearchAndReload = useCallback(() => {
    if (!activeConversation) {
      return;
    }
    setSearchOpen(false);
    setSearchInput('');
    setActiveSearch('');
    activeSearchRef.current = '';
    setLoadingMessages(true);
    stickToBottomRef.current = true;
    fetchMessages(activeConversation)
      .then(({ data: response }) => {
        const items = response?.data?.items;
        const paginate = response?.data?.paginate;
        setMessages(Array.isArray(items) ? [...items].reverse() : []);
        resetPagination(
          Boolean(paginate?.has_more_pages),
          paginate?.current_page ?? 1,
        );
      })
      .catch(() => {
        setMessages([]);
        resetPagination(false, 1);
      })
      .finally(() => setLoadingMessages(false));
  }, [activeConversation, fetchMessages, resetPagination]);

  const onToggleSearch = useCallback(() => {
    if (searchOpen) {
      clearSearchAndReload();
      return;
    }
    setSearchOpen(true);
  }, [searchOpen, clearSearchAndReload]);

  const sendMessage = async () => {
    if (!activeConversation || !activeEndpoints || (message.content.trim() === '' && !message.files.length)) {
      return;
    }

    setSending(true);
    setErrorFileIndexes([]);
    const formData = new FormData();
    formData.append('content', message.content);
    message.files.forEach((file) => {
      formData.append('files[]', file);
    });

    // Only extra headers — never set Content-Type. A manual multipart/form-data
    // (no boundary) or the shared client's former application/json default both
    // break file uploads (axios JSON-stringifies FormData when CT is JSON).
    const headers: Record<string, string> = {};
    const socketId = echoSocketId();
    if (socketId) {
      headers['X-Socket-Id'] = socketId;
    }

    try {
      const { data: response } = await axios.post<SingleApiResponse<ConversationMessage>>(
        activeEndpoints.sendUrl,
        formData,
        { headers },
      );

      const newMessage: ConversationMessage = response.success
        ? response.data
        : {
            id: '0',
            content: message.content,
            created_at: new Date(),
            created_at_iso: new Date().toISOString(),
            updated_at: new Date(),
            sender: {
              id: 0,
              name: 'You',
              image: '/media/avatars/150-1.jpg',
              socket_id: currentSocketId,
              online: true,
            },
            attachments: message.files.map((file) => ({
              id: crypto.randomUUID?.() ?? String(Date.now()),
              name: file.name,
              collection_name: 'attachments',
              file_name: file.name,
              mime_type: file.type,
              type: (file.type.split('/')[0] || 'application'),
              url: URL.createObjectURL(file),
              extension: file.name.includes('.') ? file.name.split('.').pop() : '',
              size: formatFileSize(file.size),
              available: true,
            })),
            has_attachments: message.files.length > 0,
          } as ConversationMessage;

      // Own send — always jump to latest; clear remote typing / emit throttle.
      stickToBottomRef.current = true;
      clearTyping();
      resetEmitThrottle();
      setMessages((prevMessages) => [...prevMessages, newMessage]);
      if (syncSidebar) {
        updateConversationForNewMessages(
          buildSidebarPreviewFromMessage(newMessage, activeConversation),
        );
      }
      setMessage({ content: '', files: [] });
      setErrorFileIndexes([]);
    } catch (error) {
      // Axios timeout (ECONNABORTED) and network drops have no response body —
      // extractValidationErrors returns empty → generic toast + red border below.
      // `finally` clears `sending` so we never leave an infinite spinner.
      const attachedCount = message.files.length;
      const { messages: validationMessages, fileIndexes } = extractValidationErrors(
        error,
        attachedCount,
      );

      if (validationMessages.length > 0) {
        setErrorFileIndexes(
          fileIndexes.length > 0
            ? fileIndexes
            : message.files.map((_, index) => index),
        );
        validationMessages.forEach((msg) => toast.error(msg));
      } else {
        // No body / timeout / network drop / unexpected failure.
        setErrorFileIndexes(message.files.map((_, index) => index));
        toast.error(t('Something went wrong, please try again'));
      }
      // Keep composer content so the user can retry.
    } finally {
      setSending(false);
    }
  };

  const user = activeConversation?.user1?.socket_id !== currentSocketId
    ? activeConversation?.user1
    : activeConversation?.user2;
  const displayName = user?.name ?? t('conversation');

  // Recalculate each render — scoped ref for .joining() optimistic receipts (not module state).
  unreadMessageIdsRef.current = new Set(
    messages.filter((m) => !m.read_at).map((m) => String(m.id)),
  );

  if (!activeConversation) {
    // With headerSlot (Tickets), keep page chrome on empty state; otherwise
    // return emptyFallback bare so Admin Orders / Provider stay identical.
    if (headerSlot) {
      return (
        <div className='card d-flex h-100 flex-column min-w-0'>
          {headerSlot({ searchToolbar: null })}
          {emptyFallback}
        </div>
      );
    }

    return emptyFallback ? <>{emptyFallback}</> : null;
  }

  const resolvedHeader = headerSlot
    ? headerSlot({
        searchToolbar: (
          <ConversationSearchToolbar
            searchOpen={searchOpen}
            searchInput={searchInput}
            onSearchInputChange={setSearchInput}
            onToggleSearch={onToggleSearch}
            showCloseButton={false}
          />
        ),
      })
    : (showHeader ? (
        <ConversationHeader
          user={user}
          displayName={displayName}
          searchOpen={searchOpen}
          searchInput={searchInput}
          onSearchInputChange={setSearchInput}
          onToggleSearch={onToggleSearch}
          showCloseButton={showCloseButton}
          onClose={() => {
            setPrevConversation(activeConversation);
            setCurrentConversation(null);
          }}
        />
      ) : null);

  return (
    <div className='card d-flex h-100 flex-column min-w-0'>
      {resolvedHeader}
      {searchOpen && activeSearch.trim() !== '' ? (
        <div className='px-5 py-2 border-bottom bg-light-primary'>
          <span className='fs-7 fw-semibold text-gray-600'>
            {loadingMessages
              ? t('Searching...', { defaultValue: 'Searching...' })
              : t('{{count}} results', {
                  count: messages.length,
                  defaultValue: `${messages.length} results`,
                })}
          </span>
        </div>
      ) : null}
      <ConversationMessageList
        messages={messages}
        currentSocketId={currentSocketId}
        highlightTerm={activeSearch}
        loadingMessages={loadingMessages}
        loadingOlder={loadingOlder}
        reachedBeginning={reachedBeginning}
        typingUser={typingUser}
        messagesBoxRef={messagesBox}
        messagesContentRef={messagesContentRef}
        onScroll={() => {
          if (ignoreScrollRef.current || !messagesBox.current) {
            return;
          }
          stickToBottomRef.current = isNearBottom(messagesBox.current);
          onLoadOlderScroll();
        }}
      />
      {showComposer ? (
        <ChatComposer
          content={message.content}
          files={message.files}
          isProcessing={sending}
          errorFileIndexes={errorFileIndexes}
          onContentChange={(content) => {
            setErrorFileIndexes([]);
            setMessage((prev) => ({ ...prev, content }));
            if (content.trim() !== '') {
              notifyTyping();
            }
          }}
          onFilesChange={(files) => {
            setErrorFileIndexes([]);
            setMessage((prev) => ({ ...prev, files }));
          }}
          onSend={() => void sendMessage()}
        />
      ) : null}
    </div>
  );
};

export default ConversationContent;
