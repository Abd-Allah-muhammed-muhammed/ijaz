import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import { useConversations } from "@/store/use-chat";
import { Conversation, ConversationMessage, ConversationUser } from "@/shared/types/models";
import axios from "@/shared/helpers/axios";
import ProviderOrderChatController from "@/actions/Modules/Chat/Http/Controllers/Provider/OrderChatController";
import { ChatEventEnum } from "@/Enums/Chat";
import MessageIn from "@/shared/components/chat/components/message-in";
import MessageOut from "@/shared/components/chat/components/message-out";
import ChatComposer from "@/shared/components/chat/components/chat-composer";
import ChatMessagesSkeleton from "@/shared/components/chat/components/chat-messages-skeleton";
import { formatFileSize } from "@/shared/components/chat/components/chat-attachment-utils";
import { useTranslation } from "react-i18next";
import { Button } from "react-bootstrap";
import { KTIcon } from "@/vendor/metronic/helpers";
import type { SingleApiResponse, ConversationMessagePaginationResource } from "@/shared/types/api";
import { toast } from "sonner";
import { isAxiosError } from "axios";

type Props = {
  // Define any props if needed
}

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
    errors?: Record<string, string[]>;
  };

  const messages: string[] = [];
  const fileIndexes: number[] = [];

  if (data.errors && typeof data.errors === 'object') {
    for (const [key, value] of Object.entries(data.errors)) {
      const list = Array.isArray(value) ? value : [String(value)];
      messages.push(...list.filter(Boolean));

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

  if (messages.length === 0 && data.message) {
    messages.push(data.message);
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
    unread_count: 0,
  };
}

function isNearBottom(el: HTMLElement, thresholdPx = NEAR_BOTTOM_THRESHOLD_PX): boolean {
  return el.scrollHeight - el.scrollTop - el.clientHeight <= thresholdPx;
}

let unreadMessageIndex: number[] = [];
const ConversationContent = ({ }: Props) => {
  const { t } = useTranslation();
  const {
    currentConversation,
    currentSocketId,
    prevConversation,
    setCurrentConversation,
    setPrevConversation,
    updateConversationForNewMessages,
  } = useConversations();
  const [messages, setMessages] = useState<ConversationMessage[]>([]);
  const [loadingMessages, setLoadingMessages] = useState<boolean>(false);
  const [sending, setSending] = useState<boolean>(false);
  const [errorFileIndexes, setErrorFileIndexes] = useState<number[]>([]);
  const [message, setMessage] = useState<ChatMessage>({
    content: '',
    files: []
  });
  const messagesBox = useRef<HTMLDivElement>(null);
  const messagesContentRef = useRef<HTMLDivElement>(null);
  /** User is following the live end — auto-scroll on new messages / height growth. */
  const stickToBottomRef = useRef(true);
  /** Ignore onScroll while we programmatically jump to bottom. */
  const ignoreScrollRef = useRef(false);

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
  }, [currentConversation?.id]);

  useLayoutEffect(() => {
    if (loadingMessages && messages.length === 0) {
      return;
    }

    if (stickToBottomRef.current) {
      scrollToBottom();
    }
  }, [messages, loadingMessages]);

  const sendMessage = async () => {
    if (!currentConversation || (message.content.trim() === '' && !message.files.length)) {
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
        ProviderOrderChatController.send(currentConversation.id).url,
        formData,
        { headers },
      );

      const newMessage: ConversationMessage = response.success
        ? response.data
        : {
            id: '0',
            content: message.content,
            created_at: new Date(),
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

      // Own send — always jump to latest.
      stickToBottomRef.current = true;
      setMessages(prevMessages => [...prevMessages, newMessage]);
      updateConversationForNewMessages(
        buildSidebarPreviewFromMessage(newMessage, currentConversation),
      );
      setMessage({ content: '', files: [] });
      setErrorFileIndexes([]);
    } catch (error) {
      const { messages: validationMessages, fileIndexes } = extractValidationErrors(
        error,
        message.files.length,
      );
      setErrorFileIndexes(fileIndexes);

      if (validationMessages.length > 0) {
        validationMessages.forEach((msg) => toast.error(msg));
      } else {
        toast.error(t('Validation Failed'));
      }
      // Keep composer content so the user can retry.
    } finally {
      setSending(false);
    }
  }

  const user = currentConversation?.user1?.socket_id !== currentSocketId ? currentConversation?.user1 : currentConversation?.user2;
  const displayName = user?.name ?? t('conversation');
  const avatarInitial = displayName.replace(/[_\-\\/]/i, ' ').split(' ')[0]?.charAt(0)?.toUpperCase() || '?';

  useEffect(() => {
    if (!currentConversation) {
      return;
    }

    if (currentConversation.id === prevConversation?.id) {
      return;
    }

    setMessages([]);
    setLoadingMessages(true);
    setErrorFileIndexes([]);
    stickToBottomRef.current = true;

    if (prevConversation) {
      window.Echo.leave(`chats.${prevConversation.id}`)
    }

    window.Echo.join(`chats.${currentConversation.id}`).listen(`.${ChatEventEnum.New_Message}`, (incoming: ConversationMessage) => {
      // Only follow if the user was already at/near the bottom.
      if (messagesBox.current && !ignoreScrollRef.current) {
        stickToBottomRef.current = isNearBottom(messagesBox.current);
      }
      setMessages((prevMessages) => [...prevMessages, incoming]);
      updateConversationForNewMessages(
        buildSidebarPreviewFromMessage(incoming, currentConversation),
      );
    }).joining((joiningUser: ConversationUser) => {
      if (joiningUser.socket_id !== currentSocketId) {
        setMessages((prevMessages) => {
          unreadMessageIndex.forEach(index => {
            prevMessages[index].read_at = new Date();
          })
          unreadMessageIndex = [];
          return [...prevMessages];
        })
      }
    });

    let cancelled = false;

    axios.get<SingleApiResponse<ConversationMessagePaginationResource>>(
      ProviderOrderChatController.show(currentConversation.id).url,
    )
      .then(({ data: response }) => {
        if (cancelled) {
          return;
        }

        // API paginates newest-first (listForConversation ->latest()). Chat UI
        // needs chronological within the page (oldest → newest, newest at bottom).
        // Same pattern as ConversationMessageRepository::listRecentForConversation.
        const items = response?.data?.items;
        stickToBottomRef.current = true;
        setMessages(Array.isArray(items) ? [...items].reverse() : []);
      })
      .catch(() => {
        if (!cancelled) {
          setMessages([]);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoadingMessages(false);
        }
      });

    return () => {
      cancelled = true;
      window.Echo.leave(`chats.${currentConversation.id}`);
      setMessages([]);
    };

  }, [currentConversation]);
  return (
    <div className='card d-flex h-100 flex-column min-w-0'>
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
          <div className='me-n3'>
            <Button variant={'outline-secondary'} size='sm' onClick={() => {
              setPrevConversation(currentConversation);
              setCurrentConversation(null)
            }}>
              <KTIcon iconName='cross' className="fs-2" />
            </Button>
          </div>
        </div>
      </div>
      <div
        ref={messagesBox}
        className='card-body d-flex flex-column flex-grow-1 scroll-y me-n5 pe-5 mb-5 min-w-0'
        onScroll={() => {
          if (ignoreScrollRef.current || !messagesBox.current) {
            return;
          }
          stickToBottomRef.current = isNearBottom(messagesBox.current);
        }}
      >
        <div ref={messagesContentRef} className="d-flex flex-column w-100 min-w-0">
          {loadingMessages && messages.length === 0 ? (
            <ChatMessagesSkeleton />
          ) : null}
          {messages.map((messageItem, index) => {
            const sender = messageItem.sender as ConversationUser;

            if (!messageItem.read_at) {
              unreadMessageIndex.push(index);
            }

            if (sender.socket_id !== currentSocketId) {
              return <MessageIn conversationMessage={messageItem} key={messageItem.id} />;
            }
            return <MessageOut conversationMessage={messageItem} key={messageItem.id} />;
          })}
        </div>
      </div>
      <ChatComposer
        content={message.content}
        files={message.files}
        isProcessing={sending}
        errorFileIndexes={errorFileIndexes}
        onContentChange={(content) => {
          setErrorFileIndexes([]);
          setMessage((prev) => ({ ...prev, content }));
        }}
        onFilesChange={(files) => {
          setErrorFileIndexes([]);
          setMessage((prev) => ({ ...prev, files }));
        }}
        onSend={() => void sendMessage()}
      />
    </div>
  );
};
export default ConversationContent
