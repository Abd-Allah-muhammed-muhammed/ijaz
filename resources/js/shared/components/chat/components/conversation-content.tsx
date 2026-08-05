import React, { useEffect, useRef, useState } from "react";
import { useConversations } from "@/store/use-chat";
import { ConversationMessage, ConversationUser } from "@/shared/types/models";
import axios from "@/shared/helpers/axios";
import ProviderOrderChatController from "@/actions/Modules/Chat/Http/Controllers/Provider/OrderChatController";
import { ChatEventEnum } from "@/Enums/Chat";
import MessageIn from "@/shared/components/chat/components/message-in";
import MessageOut from "@/shared/components/chat/components/message-out";
import ActionButton from "@/shared/components/action-button";
import { useTranslation } from "react-i18next";
import { Button } from "react-bootstrap";
import { KTIcon } from "@/vendor/metronic/helpers";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPaperclip } from '@fortawesome/free-solid-svg-icons';
import type { SingleApiResponse, ConversationMessagePaginationResource } from "@/shared/types/api";

type Props = {
  // Define any props if needed
}

type ChatMessage = {
  content: string;
  files: File[];
};

function echoSocketId(): string | undefined {
  try {
    return window.Echo?.socketId() ?? undefined;
  } catch {
    return undefined;
  }
}

let unreadMessageIndex: number[] = [];
const ConversationContent = ({ }: Props) => {
  const { t } = useTranslation();
  const {
    currentConversation,
    currentSocketId,
    prevConversation,
    setCurrentConversation,
    setPrevConversation
  } = useConversations();
  const [messages, setMessages] = useState<ConversationMessage[]>([]);
  const [loadingMessages, setLoadingMessages] = useState<boolean>(false);
  const [sending, setSending] = useState<boolean>(false);
  const messagesBox = useRef<HTMLDivElement>(null);
  const fileRef = useRef<HTMLInputElement>(null);
  const scrollToMessageEnd = () => {
    if (messagesBox.current) {
      messagesBox.current.scrollTop = messagesBox.current.scrollHeight;
    }
  }

  useEffect(() => {
    scrollToMessageEnd();
  }, [messages])

  const sendMessage = async () => {
    if (!currentConversation || (message.content.trim() === '' && !message.files.length)) {
      return;
    }

    setSending(true);
    const formData = new FormData();
    formData.append('content', message.content);
    message.files.forEach(file => {
      formData.append('files[]', file);
    });

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
              id: 0,
              url: URL.createObjectURL(file),
              name: file.name,
              size: file.size
            })),
          } as ConversationMessage;

      setMessages(prevMessages => [...prevMessages, newMessage]);
      setMessage({ content: '', files: [] });
    } catch {
      // Keep composer content so the user can retry.
    } finally {
      setSending(false);
    }
  }

  const onEnterPress = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      void sendMessage();
    }
  }
  const [message, setMessage] = useState<ChatMessage>({
    content: '',
    files: []
  });

  const user = currentConversation?.user1?.socket_id !== currentSocketId ? currentConversation?.user1 : currentConversation?.user2;
  useEffect(() => {
    if (!currentConversation) {
      return;
    }

    if (currentConversation.id === prevConversation?.id) {
      return;
    }

    setMessages([]);
    setLoadingMessages(true);

    if (prevConversation) {
      window.Echo.leave(`chats.${prevConversation.id}`)
    }

    window.Echo.join(`chats.${currentConversation.id}`).listen(`.${ChatEventEnum.New_Message}`, (incoming: ConversationMessage) => {
      setMessages((prevMessages) => [...prevMessages, incoming]);
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

        const items = response?.data?.items;
        setMessages(Array.isArray(items) ? items : []);
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
    <div className='card d-flex h-100 flex-column'>
      <div className='card-header' id='kt_chat_messenger_header'>
        <div className='card-title'>
          <div className='symbol-group symbol-hover'></div>
          <div className='d-flex justify-content-center flex-column me-3'>
            <a
              href='#'
              className='fs-4 fw-bolder text-gray-900 text-hover-primary me-1 mb-2 lh-1'
            >
              {user?.name}
            </a>

            <div className={`mb-0 lh-1 ${user?.online ? '' : 'd-none'} ${user?.socket_id}`}>
              <span className='badge badge-success badge-circle w-10px h-10px me-1'></span>
              <span className='fs-7 fw-bold text-gray-500'>Active</span>
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
      <div ref={messagesBox} className='card-body d-flex flex-column flex-grow-1 scroll-y me-n5 pe-5  mb-5'>
        {loadingMessages && messages.length === 0 ? (
          <div className="text-center text-muted py-10">{t('Please wait...')}</div>
        ) : null}
        {messages.map((message, index) => {
          const sender = message.sender as ConversationUser;

          if (!message.read_at) {
            unreadMessageIndex.push(index);
          }

          if (sender.socket_id !== currentSocketId) {
            return <MessageIn conversationMessage={message} key={message.id} />;
          }
          return <MessageOut conversationMessage={message} key={message.id} />;
        })}
      </div>
      <div className='card-footer pt-4'>
        <textarea
          className='form-control form-control-flush mb-3'
          rows={1}
          value={message.content}
          data-kt-element='input'
          placeholder={t('Type a message')}
          onChange={(e) => setMessage(prevState => ({
            ...prevState,
            content: e.target.value
          }))}
          onKeyDown={onEnterPress}
        />
        <div className='d-flex justify-content-end gap-1'>
          <input type="file" className="d-none"
            ref={fileRef}
            accept=".jpg,.jpeg,.png,.gif,.pdf,image/jpeg,image/png,image/gif,application/pdf"
            onChange={(e) => setMessage(prevState => ({
              ...prevState,
              files: Array.from(e.target.files || []).map(i => i as File)
            }))}
            multiple
          />
          <div className="btn-group" role="group">
            <a className="btn p-3 shadow-xs"
              onClick={(e) => {
                e.preventDefault();
                if (fileRef.current?.disabled) return;
                fileRef.current?.click();
              }}
            >
              {message.files.length ||
                <FontAwesomeIcon icon={faPaperclip} />}
            </a>
            {Boolean(message.files.length) &&
              <a className="btn p-3 shadow-xs"
                onClick={(e) => {
                  e.preventDefault();
                  setMessage(prevState => ({
                    ...prevState,
                    files: [],
                  }))
                }}
              >X</a>
            }
          </div>
          <ActionButton isProcessing={sending} type="button" onClick={() => void sendMessage()} text={t('send')} />
        </div>
      </div>
    </div>
  );
};
export default ConversationContent
