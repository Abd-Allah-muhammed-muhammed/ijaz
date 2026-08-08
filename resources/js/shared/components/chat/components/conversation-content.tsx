import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from "react";
import { useConversations } from "@/store/use-chat";
import { Conversation, ConversationMessage, ConversationUser } from "@/shared/types/models";
import axios from "@/shared/helpers/axios";
import ProviderOrderChatController from "@/actions/Modules/Chat/Http/Controllers/Provider/OrderChatController";
import { ChatEventEnum } from "@/Enums/Chat";
import MessageIn from "@/shared/components/chat/components/message-in";
import MessageOut from "@/shared/components/chat/components/message-out";
import ChatComposer from "@/shared/components/chat/components/chat-composer";
import ChatMessagesSkeleton from "@/shared/components/chat/components/chat-messages-skeleton";
import ChatTypingIndicator from "@/shared/components/chat/components/chat-typing-indicator";
import { formatFileSize } from "@/shared/components/chat/components/chat-attachment-utils";
import { useChatTyping } from "@/shared/components/chat/hooks/use-chat-typing";
import { useChatNotificationSound } from "@/shared/components/chat/hooks/use-chat-notification-sound";
import { useChatLoadOlderMessages } from "@/shared/components/chat/hooks/use-chat-load-older-messages";
import { useTranslation } from "react-i18next";
import { Button, Spinner } from "react-bootstrap";
import { KTIcon } from "@/vendor/metronic/helpers";
import type { SingleApiResponse, ConversationMessagePaginationResource } from "@/shared/types/api";
import { toast } from "sonner";
import { isAxiosError } from "axios";

export type ConversationContentEndpoints = {
  messagesUrl: (options?: { search?: string; page?: number }) => string;
  sendUrl: string;
  typingUrl?: string | null;
};

type Props = {
  /** Controlled conversation (Admin Orders). When omitted, uses Provider chat store. */
  conversation?: Conversation | null;
  /** Override API URLs. Defaults to Provider OrderChatController. */
  endpoints?: ConversationContentEndpoints;
  /** Hide the inbox close (X) button — used for embedded Admin views. */
  showCloseButton?: boolean;
  /**
   * When false, hides the peer name/avatar/online block (Admin Tickets already
   * shows that context in the page sidebar). Search toolbar remains. Default true.
   */
  showHeader?: boolean;
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

/** px from bottom — treat as "still following the live end". */
const NEAR_BOTTOM_THRESHOLD_PX = 120;

function echoSocketId(): string | undefined {
  try {
    return window.Echo?.socketId() ?? undefined;
  } catch {
    return undefined;
  }
}

/**
 * Parse MMAE-style validation envelopes (and PostTooLargeException, which uses
 * the same shape with `errors.files` when PHP rejects the body before Laravel
 * can attribute a specific file index).
 *
 * Returns empty `messages` when there is no response body, no field errors, or
 * only the envelope title "Validation Failed" — callers must toast a generic
 * fallback in that case (network drop / unexpected 5xx / empty payload).
 */
function extractValidationErrors(
  error: unknown,
  attachedFileCount = 0,
): {
  messages: string[];
  fileIndexes: number[];
} {
  if (!isAxiosError(error) || !error.response?.data) {
    return { messages: [], fileIndexes: [] };
  }

  const data = error.response.data as {
    message?: string;
    errors?: Record<string, string[] | string>;
  };

  const messages: string[] = [];
  const fileIndexes: number[] = [];

  if (data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)) {
    for (const [key, value] of Object.entries(data.errors)) {
      const list = Array.isArray(value) ? value : [String(value)];
      const usable = list.map(String).map((s) => s.trim()).filter(Boolean);
      messages.push(...usable);

      const match = key.match(/^files(?:\.|\[)(\d+)/);
      if (match) {
        fileIndexes.push(Number(match[1]));
        continue;
      }

      // PostTooLarge / bag-level "files" — highlight every attached preview.
      if (key === 'files' && attachedFileCount > 0) {
        for (let i = 0; i < attachedFileCount; i++) {
          fileIndexes.push(i);
        }
      }
    }
  }

  // Envelope title alone is not a real validation detail — leave messages empty
  // so the UI shows the generic fallback instead of "Validation Failed".
  if (messages.length === 0 && typeof data.message === 'string') {
    const trimmed = data.message.trim();
    if (trimmed !== '' && trimmed.toLowerCase() !== 'validation failed') {
      messages.push(trimmed);
    }
  }

  return { messages, fileIndexes: [...new Set(fileIndexes)] };
}

function buildSidebarPreviewFromMessage(
  message: ConversationMessage,
  conversation: Conversation,
): Conversation {
  const attachmentsCount = message.attachments?.length
    ?? message.attachments_count
    ?? 0;

  return {
    ...conversation,
    last_message: {
      ...message,
      content: message.content,
      has_attachments: Boolean(message.has_attachments) || attachmentsCount > 0,
      attachments_count: attachmentsCount,
    },
    last_message_at: typeof message.created_at === 'string'
      ? message.created_at
      : conversation.last_message_at,
    last_massage_at: typeof message.created_at === 'string'
      ? message.created_at
      : conversation.last_massage_at,
    last_message_at_iso: message.created_at_iso
      ?? (message.created_at instanceof Date ? message.created_at.toISOString() : conversation.last_message_at_iso),
    unread_count: 0,
  };
}

function isNearBottom(el: HTMLElement, thresholdPx = NEAR_BOTTOM_THRESHOLD_PX): boolean {
  return el.scrollHeight - el.scrollTop - el.clientHeight <= thresholdPx;
}

const ConversationContent = ({
  conversation: conversationProp,
  endpoints: endpointsProp,
  showCloseButton = true,
  showHeader = true,
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
    files: []
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

  const sendMessage = async () => {
    if (!activeConversation || !activeEndpoints || (message.content.trim() === '' && !message.files.length)) {
      return;
    }

    setSending(true);
    setErrorFileIndexes([]);
    const formData = new FormData();
    formData.append('content', message.content);
    message.files.forEach(file => {
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
              online: true
            },
            attachments: message.files.map(file => ({
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
      setMessages(prevMessages => [...prevMessages, newMessage]);
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
  }

  const user = activeConversation?.user1?.socket_id !== currentSocketId ? activeConversation?.user1 : activeConversation?.user2;
  const displayName = user?.name ?? t('conversation');
  const avatarInitial = displayName.replace(/[_\-\\/]/i, ' ').split(' ')[0]?.charAt(0)?.toUpperCase() || '?';

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
      window.Echo.leave(`chats.${prevConversation.id}`)
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
        if (messagesBox.current && !ignoreScrollRef.current) {
          stickToBottomRef.current = isNearBottom(messagesBox.current);
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

  }, [activeConversation?.id, isControlled]);

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
  }, [searchInput, searchOpen, activeConversation?.id, fetchMessages]);

  if (!activeConversation) {
    return emptyFallback ? <>{emptyFallback}</> : null;
  }

  // Recalculate each render — scoped ref for .joining() optimistic receipts (not module state).
  unreadMessageIdsRef.current = new Set(
    messages.filter((m) => !m.read_at).map((m) => String(m.id)),
  );

  return (
    <div className='card d-flex h-100 flex-column min-w-0'>
      <div
        className={`card-header${showHeader ? '' : ' border-0 min-h-auto py-3 justify-content-end'}`}
        id='kt_chat_messenger_header'
      >
        {showHeader ? (
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
        ) : null}

        <div className='card-toolbar'>
          <div className='d-flex align-items-center gap-1 me-n1'>
            {searchOpen ? (
              <div className='d-flex align-items-center position-relative me-2' style={{ minWidth: 160, maxWidth: 240 }}>
                <KTIcon iconName='magnifier' className='fs-3 position-absolute ms-3 text-gray-500' />
                <input
                  type='search'
                  className='form-control form-control-sm form-control-solid ps-10'
                  placeholder={t('Search messages', { defaultValue: 'Search messages' })}
                  value={searchInput}
                  autoFocus
                  onChange={(e) => setSearchInput(e.target.value)}
                  aria-label={t('Search messages', { defaultValue: 'Search messages' })}
                />
              </div>
            ) : null}
            <Button
              variant={searchOpen ? 'light-primary' : 'outline-secondary'}
              size='sm'
              className='btn-icon'
              aria-label={searchOpen
                ? t('Close search', { defaultValue: 'Close search' })
                : t('Search messages', { defaultValue: 'Search messages' })}
              onClick={() => {
                if (searchOpen) {
                  setSearchOpen(false);
                  setSearchInput('');
                  setActiveSearch('');
                  activeSearchRef.current = '';
                  if (!activeConversation) {
                    return;
                  }
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
                  return;
                }
                setSearchOpen(true);
              }}
            >
              <KTIcon iconName='magnifier' className="fs-2" />
            </Button>
            {showCloseButton ? (
              <Button variant={'outline-secondary'} size='sm' onClick={() => {
                setPrevConversation(activeConversation);
                setCurrentConversation(null)
              }}>
                <KTIcon iconName='cross' className="fs-2" />
              </Button>
            ) : null}
          </div>
        </div>
      </div>
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
      <div
        ref={messagesBox}
        className='card-body d-flex flex-column flex-grow-1 scroll-y me-n5 pe-5 mb-5 min-w-0'
        onScroll={() => {
          if (ignoreScrollRef.current || !messagesBox.current) {
            return;
          }
          stickToBottomRef.current = isNearBottom(messagesBox.current);
          onLoadOlderScroll();
        }}
      >
        <div ref={messagesContentRef} className="d-flex flex-column w-100 min-w-0">
          {loadingOlder ? (
            <div className="d-flex justify-content-center align-items-center py-3" aria-live="polite">
              <Spinner animation="border" size="sm" className="text-primary" />
            </div>
          ) : null}
          {reachedBeginning && messages.length > 0 && activeSearch.trim() === '' ? (
            <div className="text-center py-3">
              <span className="fs-8 fw-semibold text-gray-500">
                {t('Beginning of conversation', { defaultValue: 'Beginning of conversation' })}
              </span>
            </div>
          ) : null}
          {loadingMessages && messages.length === 0 ? (
            <ChatMessagesSkeleton />
          ) : null}
          {!loadingMessages && messages.length === 0 && activeSearch.trim() !== '' ? (
            <div className="text-center py-10">
              <div className="fw-semibold text-gray-600 fs-6">
                {t('No messages found', { defaultValue: 'No messages found' })}
              </div>
            </div>
          ) : null}
          {messages.map((messageItem) => {
            const sender = messageItem.sender as ConversationUser;

            if (sender.socket_id !== currentSocketId) {
              return (
                <MessageIn
                  conversationMessage={messageItem}
                  key={messageItem.id}
                  highlightTerm={activeSearch.trim() || null}
                />
              );
            }
            return (
              <MessageOut
                conversationMessage={messageItem}
                key={messageItem.id}
                highlightTerm={activeSearch.trim() || null}
              />
            );
          })}
          <ChatTypingIndicator user={typingUser} />
        </div>
      </div>
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
export default ConversationContent
