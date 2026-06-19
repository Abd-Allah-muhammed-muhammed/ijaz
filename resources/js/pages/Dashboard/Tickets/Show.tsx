import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {Head, Link, router, useForm} from "@inertiajs/react";
import {
  Conversation,
  ConversationMessage,
  ConversationUser,
  Order,
  TicketSupport
} from "@/types/models";
import {KTCard} from "@/_metronic/helpers";
import React, {ReactNode, useEffect, useRef, useState} from "react";
import SupportChatController from "@/actions/Modules/Chat/Http/Controllers/Dashboard/SupportChatController";
import MessageIn from "@/components/chat/components/message-in";
import MessageOut from "@/components/chat/components/message-out";
import ActionButton from "@/components/action-button";
import {useConversations} from "@/store/use-chat";
import {ChatEventEnum} from "@/Enums/Chat";
import {TicketSupportStatusEnum} from "@/Enums/SupportTickets";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPaperclip } from '@fortawesome/free-solid-svg-icons';


type Props = {
  row: TicketSupport<Order>,
  chat?: Conversation
  chatMessages?: ConversationMessage[]
};

type ChatMessage = {
  content: string;
  files: File[];
};
let unreadMessageIndex: number[] = [];
const Show = ({row, chat, chatMessages}: Props) => {
  const { t } = useTranslation();
  const formatDate = (date: string | Date) => {
    return new Date(date).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const messageForm = useForm<ChatMessage>({
    content: '',
    files: []
  })
  const {currentSocketId} = useConversations();
  const messagesBox = useRef<HTMLDivElement>(null);
  const [messages, setMessages] = useState<ConversationMessage[]>(chatMessages || []);

  const fileRef = useRef<HTMLInputElement>(null);
  const scrollToMessageEnd = () => {
    if (messagesBox.current) {
      messagesBox.current.scrollTop = messagesBox.current.scrollHeight;
    }
  }

  // Sync messages state with chatMessages prop when it updates
  useEffect(() => {
    if (chatMessages) {
      setMessages(chatMessages);
    }
  }, [chatMessages]);

  useEffect(() => {
    scrollToMessageEnd();
  }, [messages])

  // Connect to conversation channel via Echo
  useEffect(() => {
    if (chat) {
      window.Echo.join(`chats.${chat.id}`)
        .joining((user: ConversationUser) => {
          if (user.socket_id !== currentSocketId) {
            setMessages((prevMessages) => {
              unreadMessageIndex.forEach(index => {
                prevMessages[index].read_at = new Date().toISOString();
              })
              unreadMessageIndex = [];
              return [...prevMessages];
            })

          }
        })
        .listen(`.${ChatEventEnum.New_Message}`, (message: ConversationMessage) => {
          setMessages((prevMessages) => [...prevMessages, message]);
        });

      return () => {
        window.Echo.leave(`chats.${chat.id}`);
      };
    }
  }, [chat])
  const sendMessage = () => {
    if (chat && (messageForm.data.content.trim() !== '' || Boolean(messageForm.data.files.length))) {
      messageForm.submit(SupportChatController.send(row.id as number), {
        onSuccess: (response) => {
          messageForm.setData('content', '');
          messageForm.setData('files', []);
        }
      });
    }
  }
  const onEnterPress = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  }
  return (
    <>
      <Head title={t('tickets')}/>
      <PageTitle breadcrumbs={[
        {
          title: t('tickets'),
          path: SupportController.index().url,
          isSeparator: false,
          isActive: false,
        },
        {
          title: t('show'),
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('support_ticket')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <div className="row g-5 g-xl-8">
          {/* Column 1: Ticket Information & User Information */}
          <div className="col-xl-3">
            {/* Ticket Information Card */}
            <KTCard className="mb-5 mb-xl-8">
              <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                  <span className="card-label fw-bold fs-3 mb-1">{t('ticket_information')}</span>
                </h3>
                <div className="card-toolbar">
                  <span className={`badge badge-light-${row.status.color} fs-7 fw-bold`}>
                    {row.status.label}
                  </span>
                </div>
              </div>
              <div className="card-body py-3">
                <div className="mb-7">
                  <label className="fw-semibold text-muted d-block mb-2">{t('ticket_id')}</label>
                  <span className="fw-bold fs-6 text-gray-800">#{row.id}</span>
                </div>
                <div className="mb-7">
                  <label className="fw-semibold text-muted d-block mb-2">{t('title')}</label>
                  <span className="fw-bold fs-6 text-gray-800">{row.title}</span>
                </div>
                <div className="mb-7">
                  <label className="fw-semibold text-muted d-block mb-2">{t('message')}</label>
                  <span className="fw-normal fs-6 text-gray-700">{row.message}</span>
                </div>
                <div className="mb-0">
                  <label className="fw-semibold text-muted d-block mb-2">{t('created_at')}</label>
                  <span className="fw-normal fs-6 text-gray-700">{formatDate(row.created_at)}</span>
                </div>
              </div>
            </KTCard>

            {/* User Information Card */}
            {row.user && (
              <KTCard className="mb-5 mb-xl-8">
                <div className="card-header border-0 pt-5">
                  <h3 className="card-title align-items-start flex-column">
                    <span className="card-label fw-bold fs-3 mb-1">{t('user_information')}</span>
                  </h3>
                </div>
                <div className="card-body py-3">
                  <div className="mb-7">
                    <label className="fw-semibold text-muted d-block mb-2">{t('name')}</label>
                    <div className="d-flex align-items-center">
                      {row.user.image && (
                        <div className="symbol symbol-35px symbol-circle me-3">
                          <img src={row.user.image} alt={row.user.name}/>
                        </div>
                      )}
                      <span className="fw-bold fs-6 text-gray-800">{row.user.name}</span>
                    </div>
                  </div>
                  <div className="mb-7">
                    <label className="fw-semibold text-muted d-block mb-2">{t('email')}</label>
                    <span className="fw-normal fs-6 text-gray-700">{row.user.email}</span>
                  </div>
                  {row.user.type && (
                    <div className="mb-0">
                      <label className="fw-semibold text-muted d-block mb-2">{t('user_type')}</label>
                      <span className="fw-normal fs-6 text-gray-700">{row.user.type}</span>
                    </div>
                  )}
                </div>
              </KTCard>
            )}

            {/* Operation Information Card */}
            {row.operation && (
              <KTCard className="mb-5 mb-xl-8">
                <div className="card-header border-0 pt-5">
                  <h3 className="card-title align-items-start flex-column">
                    <span className="card-label fw-bold fs-3 mb-1">{t('operation_information')}</span>
                  </h3>
                  <div className="card-toolbar">
                    <Link
                      href={row.operation.show_url}
                      className="btn btn-sm btn-light-primary"
                    >
                      {t('view_operation')}
                    </Link>
                  </div>
                </div>
                <div className="card-body py-3">
                  <div className="mb-7">
                    <label className="fw-semibold text-muted d-block mb-2">{t('operation_type')}</label>
                    <span className="badge badge-light-info fs-7 fw-bold">{row.operation.type}</span>
                  </div>
                  <div className="mb-0">
                    <label className="fw-semibold text-muted d-block mb-2">{t('operation_id')}</label>
                    <span className="fw-bold fs-6 text-gray-800">#{row.operation.id}</span>
                  </div>

                  {/* Display operation-specific data */}
                  {row.operation.data && (
                    <>
                      {/* Common operation fields */}
                      {row.operation.data.title && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('title')}</label>
                          <span className="fw-bold fs-6 text-gray-800">{row.operation.data.title}</span>
                        </div>
                      )}

                      {row.operation.data.description && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('description')}</label>
                          <span className="fw-normal fs-6 text-gray-700">{row.operation.data.description}</span>
                        </div>
                      )}

                      {row.operation.data.status && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('status')}</label>
                          <span
                            className={`badge badge-light-${typeof row.operation.data.status === 'string' ? 'primary' : row.operation.data.status.color} fs-7 fw-bold`}>
                            {typeof row.operation.data.status === 'string' ? row.operation.data.status : row.operation.data.status.label}
                          </span>
                        </div>
                      )}

                      {/* Order-specific fields */}
                      {'budget_start' in row.operation.data && row.operation.data.budget_start && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('budget_range')}</label>
                          <span className="fw-bold fs-6 text-gray-800">
                            {row.operation.data.budget_start as string} - {row.operation.data.budget_end as string}
                          </span>
                        </div>
                      )}

                      {'price' in row.operation.data && row.operation.data.price && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('price')}</label>
                          <span className="fw-bold fs-6 text-gray-800">{row.operation.data.price as string}</span>
                        </div>
                      )}

                      {row.operation.data.created_at && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('created_at')}</label>
                          <span
                            className="fw-normal fs-6 text-gray-700">{formatDate(row.operation.data.created_at)}</span>
                        </div>
                      )}
                    </>
                  )}
                </div>
              </KTCard>
            )}
          </div>

          {/* Column 2: Chat Section */}
          <div className="col-xl-6">
            <KTCard className="mb-5 mb-xl-8">
              <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                  <span className="card-label fw-bold fs-3 mb-1">{t('conversation')}</span>
                  <span className="text-muted mt-1 fw-semibold fs-7">{t('ticket_chat_messages')}</span>
                </h3>
              </div>
              <div ref={messagesBox} className='card-body d-flex flex-column flex-grow-1 scroll-y me-n5 pe-5  mb-5'
                   style={{
                     height: 'calc(100vh - 400px)',
                   }}>
                {messages && messages.length > 0 ? (
                  messages.map((message, index) => {
                    const sender = message.sender as ConversationUser;

                    if (!message.read_at) {
                      unreadMessageIndex.push(index);
                    }

                    if (sender.socket_id !== currentSocketId) {
                      return <MessageIn conversationMessage={message} key={message.id}/>;
                    }
                    return <MessageOut conversationMessage={message} key={message.id}/>;
                  })
                ) : (
                  <div className="text-center py-10">
                    <div className="mb-5">
                      <i className="bi bi-chat-dots fs-3x text-gray-400"></i>
                    </div>
                    <div className="fw-semibold text-gray-600 fs-6">
                      {t('no_messages_yet')}
                    </div>
                  </div>
                )}
              </div>

              {/* Chat Input Area */}
              {!chat ? (
                  <div className="card-footer pt-4 border-top">
                    <button className="btn btn-primary w-100" onClick={() => {
                      router.post(SupportController.openChat(row.id as number).url);
                    }}
                    >
                      <i className="bi bi-chat-left-text me-2"></i>
                      {t('open_chat')}
                    </button>
                  </div>
                )
                : (
                  <div className='card-footer pt-4'>
                    <textarea
                      className='form-control form-control-flush mb-3'
                      rows={1}
                      value={messageForm.data.content}
                      data-kt-element='input'
                      placeholder='Type a message'
                      onChange={(e) => messageForm.setData(prevState => ({
                        ...prevState,
                        content: e.target.value
                      }))}
                      onKeyDown={onEnterPress}
                    />
                    <div className='d-flex justify-content-end gap-1'>
                      <input type="file" className="d-none"
                             ref={fileRef}
                             accept=".jpg,.jpeg,.png,.gif,.pdf,image/jpeg,image/png,image/gif,application/pdf"
                             onChange={(e) => {
                               messageForm.setData('files', Array.from(e.currentTarget.files || []).map(i => i as File));
                             }}
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
                          {messageForm.data.files.length ||
                          <FontAwesomeIcon icon={faPaperclip} />}
                        </a>
                        {Boolean(messageForm.data.files.length) &&
                          <a className="btn p-3 shadow-xs"
                             onClick={(e) => {
                               e.preventDefault();
                               messageForm.setData('files', []);
                             }}
                          >X</a>
                        }
                      </div>
                      <ActionButton isProcessing={messageForm.processing} type="button" onClick={sendMessage}
                                    text={t('send')}/>
                    </div>
                  </div>
                )}
            </KTCard>
          </div>

          {/* Column 3: Actions & Status Management */}
          <div className="col-xl-3">
            {/* Status Management Card */}
            <KTCard className="mb-5 mb-xl-8">
              <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                  <span className="card-label fw-bold fs-3 mb-1">{t('ticket_management')}</span>
                </h3>
              </div>
              <div className="card-body py-3">
                {/* Action Buttons */}
                <div className="d-flex flex-column gap-3">
                  <button
                    className="btn btn-light-success w-100"
                    onClick={() => {
                      router.put(SupportController.updateStatus(row.id as number).url, {
                        status: TicketSupportStatusEnum.Open
                      });
                    }}
                  >
                    <i className="bi bi-check-circle me-2"></i>
                    {t('mark_as_open')}
                  </button>

                  <button
                    className="btn btn-light-warning w-100"
                    onClick={() => {
                      router.put(SupportController.updateStatus(row.id as number).url, {
                        status: TicketSupportStatusEnum.Pending
                      });
                    }}
                  >
                    <i className="bi bi-clock-history me-2"></i>
                    {t('mark_as_pending')}
                  </button>

                  <button
                    className="btn btn-light-danger w-100"
                    onClick={() => {
                      router.put(SupportController.updateStatus(row.id as number).url, {
                        status: TicketSupportStatusEnum.Closed
                      });
                    }}
                  >
                    <i className="bi bi-x-circle me-2"></i>
                    {t('close_ticket')}
                  </button>
                </div>
              </div>
            </KTCard>

            {/* Quick Info Card */}
            <KTCard className="mb-5 mb-xl-8 card-flush">
              <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                  <span className="card-label fw-bold fs-3 mb-1">{t('quick_info')}</span>
                </h3>
              </div>
              <div className="card-body py-3">
                <div className="d-flex align-items-center mb-5">
                  <div className="symbol symbol-40px me-3">
                    <span className="symbol-label bg-light-primary">
                      <i className="bi bi-calendar-event text-primary fs-4"></i>
                    </span>
                  </div>
                  <div className="d-flex flex-column">
                    <span className="text-gray-800 fw-bold fs-7">{t('created')}</span>
                    <span className="text-muted fw-semibold fs-8">{formatDate(row.created_at)}</span>
                  </div>
                </div>

                <div className="d-flex align-items-center mb-5">
                  <div className="symbol symbol-40px me-3">
                    <span className="symbol-label bg-light-warning">
                      <i className="bi bi-hourglass-split text-warning fs-4"></i>
                    </span>
                  </div>
                  <div className="d-flex flex-column">
                    <span className="text-gray-800 fw-bold fs-7">{t('response_time')}</span>
                    <span className="text-muted fw-semibold fs-8">{t('not_available')}</span>
                  </div>
                </div>

                <div className="d-flex align-items-center">
                  <div className="symbol symbol-40px me-3">
                    <span className="symbol-label bg-light-info">
                      <i className="bi bi-chat-dots text-info fs-4"></i>
                    </span>
                  </div>
                  <div className="d-flex flex-column">
                    <span className="text-gray-800 fw-bold fs-7">{t('messages_count')}</span>
                    <span className="text-muted fw-semibold fs-8">0 {t('messages')}</span>
                  </div>
                </div>
              </div>
            </KTCard>
          </div>
        </div>
      </Content>
    </>
  );
}
Show.layout = (page: ReactNode) => <MasterLayout children={page}/>

export default Show;
