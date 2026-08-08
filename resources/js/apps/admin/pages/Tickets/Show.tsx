import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Conversation, ConversationMessage, Order, TicketSupport } from '@/shared/types/models';
import { KTCard } from '@/vendor/metronic/helpers';
import React, { ReactNode, useEffect, useMemo } from 'react';
import SupportController from '@/actions/Modules/Support/Http/Controllers/Dashboard/SupportController';
import SupportChatController from '@/actions/Modules/Support/Http/Controllers/Dashboard/SupportChatController';
import ConversationContent from '@/shared/components/chat/components/conversation-content';
import usePermissions from '@/shared/hooks/use-permissions';
import { useConversations } from '@/store/use-chat';
import { TicketSupportStatusEnum } from '@/Enums/SupportTickets';

type Props = {
  row: TicketSupport<Order>;
  chat?: Conversation;
  chatMessages?: ConversationMessage[];
};

const Show = ({ row, chat, chatMessages }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canEdit = hasPermission('edit supportTicket');
  const { setCurrentSocketId } = useConversations();
  const { auth } = usePage<{ auth: { user?: { socket_id?: string } } }>().props;

  const formatDate = (date: string | Date) => {
    return new Date(date).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  useEffect(() => {
    if (auth.user?.socket_id) {
      setCurrentSocketId(auth.user.socket_id);
    }
  }, [auth.user?.socket_id, setCurrentSocketId]);

  const endpoints = useMemo(() => {
    const ticketId = row.id as string | number;

    // Defensive: stale Wayfinder actions without typing must not crash chat.
    const typingFn = SupportChatController.typing;
    const typingUrl =
      typeof typingFn === 'function' ? typingFn(ticketId).url : null;

    return {
      messagesUrl: (options?: { search?: string; page?: number }) => {
        const query: Record<string, string | number> = { per_page: 20 };
        if (options?.search && options.search.trim() !== '') {
          query.search = options.search.trim();
        }
        if (options?.page && options.page > 1) {
          query.page = options.page;
        }

        return SupportChatController.show(ticketId, { query }).url;
      },
      sendUrl: SupportChatController.send(ticketId).url,
      typingUrl,
    };
  }, [row.id]);

  const conversation = (chat ?? null) as Conversation | null;
  const seededMessageCount = chatMessages?.length ?? 0;

  return (
    <>
      <Head title={t('tickets')} />
      <PageTitle
        breadcrumbs={[
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
        ]}
      >
        {t('support_ticket')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="row g-5 g-xl-8">
          {/* Column 1: Ticket Information & User Information */}
          <div className="col-xl-3">
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
                          <img src={row.user.image} alt={row.user.name} />
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

            {row.operation && (
              <KTCard className="mb-5 mb-xl-8">
                <div className="card-header border-0 pt-5">
                  <h3 className="card-title align-items-start flex-column">
                    <span className="card-label fw-bold fs-3 mb-1">{t('operation_information')}</span>
                  </h3>
                  <div className="card-toolbar">
                    <Link href={row.operation.show_url} className="btn btn-sm btn-light-primary">
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

                  {row.operation.data && (
                    <>
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
                            className={`badge badge-light-${typeof row.operation.data.status === 'string' ? 'primary' : row.operation.data.status.color} fs-7 fw-bold`}
                          >
                            {typeof row.operation.data.status === 'string'
                              ? row.operation.data.status
                              : row.operation.data.status.label}
                          </span>
                        </div>
                      )}

                      {'budget_start' in row.operation.data && row.operation.data.budget_start && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('budget_range')}</label>
                          <span className="fw-bold fs-6 text-gray-800">
                            {row.operation.data.budget_start as string} -{' '}
                            {row.operation.data.budget_end as string}
                          </span>
                        </div>
                      )}

                      {'price' in row.operation.data && row.operation.data.price && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('price')}</label>
                          <span className="fw-bold fs-6 text-gray-800">
                            {row.operation.data.price as string}
                          </span>
                        </div>
                      )}

                      {row.operation.data.created_at && (
                        <div className="mt-7">
                          <label className="fw-semibold text-muted d-block mb-2">{t('created_at')}</label>
                          <span className="fw-normal fs-6 text-gray-700">
                            {formatDate(row.operation.data.created_at)}
                          </span>
                        </div>
                      )}
                    </>
                  )}
                </div>
              </KTCard>
            )}
          </div>

          {/* Column 2: Chat — page-owned header + shared ConversationContent */}
          <div className="col-xl-6">
            <KTCard className="mb-5 mb-xl-8">
              <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                  <span className="card-label fw-bold fs-3 mb-1">
                    {t('support_ticket')} #{row.id}
                  </span>
                  <span className="text-muted mt-1 fw-semibold fs-7 d-flex flex-wrap align-items-center gap-2">
                    <span className={`badge badge-light-${row.status.color} fs-8 fw-bold`}>
                      {row.status.label}
                    </span>
                    <span>{formatDate(row.created_at)}</span>
                    {row.title ? (
                      <span className="text-truncate" style={{ maxWidth: 280 }} title={row.title}>
                        · {row.title}
                      </span>
                    ) : null}
                  </span>
                </h3>
              </div>
              <div className="card-body p-0">
                <div
                  style={{ minHeight: 520, height: 'min(70vh, 720px)' }}
                  className="overflow-hidden"
                >
                  <ConversationContent
                    conversation={conversation}
                    endpoints={endpoints}
                    showCloseButton={false}
                    showHeader={false}
                    showComposer={canEdit}
                    syncSidebar={false}
                    emptyFallback={
                      <div className="d-flex flex-column h-100">
                        <div className="flex-grow-1 d-flex align-items-center justify-content-center py-10">
                          <div className="text-center">
                            <div className="mb-5">
                              <i className="bi bi-chat-dots fs-3x text-gray-400"></i>
                            </div>
                            <div className="fw-semibold text-gray-600 fs-6 mb-5">
                              {t('no_messages_yet')}
                            </div>
                          </div>
                        </div>
                        {canEdit ? (
                          <div className="card-footer pt-4 border-top">
                            <button
                              type="button"
                              className="btn btn-primary w-100"
                              onClick={() => {
                                router.post(SupportController.openChat(row.id as number).url);
                              }}
                            >
                              <i className="bi bi-chat-left-text me-2"></i>
                              {t('open_chat')}
                            </button>
                          </div>
                        ) : null}
                      </div>
                    }
                  />
                </div>
              </div>
            </KTCard>
          </div>

          {/* Column 3: Actions & Status Management */}
          <div className="col-xl-3">
            <KTCard className="mb-5 mb-xl-8">
              <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                  <span className="card-label fw-bold fs-3 mb-1">{t('ticket_management')}</span>
                </h3>
              </div>
              <div className="card-body py-3">
                {canEdit && (
                  <div className="d-flex flex-column gap-3">
                    <button
                      className="btn btn-light-success w-100"
                      onClick={() => {
                        router.put(SupportController.updateStatus(row.id as number).url, {
                          status: TicketSupportStatusEnum.Open,
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
                          status: TicketSupportStatusEnum.Pending,
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
                          status: TicketSupportStatusEnum.Closed,
                        });
                      }}
                    >
                      <i className="bi bi-x-circle me-2"></i>
                      {t('close_ticket')}
                    </button>
                  </div>
                )}
              </div>
            </KTCard>

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
                    <span className="text-muted fw-semibold fs-8">
                      {seededMessageCount} {t('messages')}
                    </span>
                  </div>
                </div>
              </div>
            </KTCard>
          </div>
        </div>
      </Content>
    </>
  );
};
Show.layout = (page: ReactNode) => <MasterLayout children={page} />;

export default Show;
