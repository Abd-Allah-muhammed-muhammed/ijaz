import GuarantorDashboardController from '@/actions/Modules/Guarantor/Http/Controllers/Dashboard/GuarantorController';
import { KTIcon, KTCard, KTCardBody } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { PageTitle } from '@/vendor/metronic/layout/core';
import usePermissions from '@/shared/hooks/use-permissions';
import { Conversation } from '@/shared/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import clsx from 'clsx';
import { ReactElement, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import ChatTap from './components/chat-tap';
import DocumentsTab, { MediaItem } from './components/documents-tab';
import DisputeTab from './components/dispute-tab';

type StatusOption = {
  value: string;
  label: string;
  color: string;
};

type Participant = {
  id: string | number;
  name: string;
  phone?: string;
  type?: string;
  image?: string;
};

type InstallmentItem = {
  id: string;
  order: number;
  amount: string | number;
  due_date: string;
  status: { value: string; label: string; color: string };
  paid_at?: string | null;
  released_at?: string | null;
};

type HistoryItem = {
  id: string;
  from_status?: StatusOption | null;
  to_status: StatusOption;
  reason?: string | null;
  notes?: string | null;
  actor?: Participant;
  created_at: string;
};

type CompanyDetail = {
  company_name?: string;
  commercial_register?: string;
  authorized_name?: string;
  authorized_id_number?: string;
  authorization_type?: StatusOption;
  requester_account_holder?: string;
  requester_iban?: string;
  counterparty_account_holder?: string;
  counterparty_iban?: string;
  region?: { title?: string };
  city?: { title?: string };
  media?: MediaItem[];
};

type GuarantorResource = {
  id: string;
  type: StatusOption;
  status: StatusOption;
  title: string;
  description?: string;
  amount: string | number;
  fees: string | number;
  total: string | number;
  project_type?: string | null;
  cancellation_reason?: string | null;
  admin_notes?: string | null;
  requester?: Participant;
  counterparty?: Participant;
  installments?: InstallmentItem[];
  company_detail?: CompanyDetail | null;
  status_histories?: HistoryItem[];
  media?: MediaItem[];
  conversation?: Conversation | null;
  overdue_at?: string | null;
  ended_at?: string | null;
  cancelled_at?: string | null;
  created_at: string;
};

type Props = {
  guarantorRequest: GuarantorResource;
};

type AdminAction = 'approve' | 'reject' | 'cancel' | 'resolve' | null;

const RESOLUTION_OPTIONS = [
  'full_requester',
  'full_counterparty',
  'percentage_split',
  'escalate',
] as const;

type ResolutionOption = (typeof RESOLUTION_OPTIONS)[number];

const TERMINAL_STATUSES = ['rejected_by_admin', 'rejected', 'ended', 'cancelled', 'escalated', 'settled'];

const statusBadgeClass: Record<string, string> = {
  new: 'badge-light-secondary',
  pending_admin: 'badge-light-warning',
  approved_by_admin: 'badge-light-info',
  rejected_by_admin: 'badge-light-danger',
  accepted: 'badge-light-primary',
  rejected: 'badge-light-warning',
  in_progress: 'badge-light-success',
  overdue: 'badge-light-danger',
  disputed: 'badge-light-danger',
  ended: 'badge-light-success',
  cancelled: 'badge-light-secondary',
  escalated: 'badge-light-dark',
  settled: 'badge-light-info',
};

const timelineStyles = `
  .timeline { display: flex; flex-direction: column; }
  .timeline-item { display: flex; gap: 12px; }
  .timeline-line { display: flex; flex-direction: column; align-items: center; min-width: 20px; }
  .timeline-badge { border-radius: 50%; flex-shrink: 0; }
  .timeline-connector { flex: 1; width: 2px; background: #e4e6ef; margin: 4px 0; min-height: 24px; }
  .timeline-content { flex: 1; }
`;

const Show = ({ guarantorRequest }: Props) => {
  const { t, i18n } = useTranslation();
  const { hasPermission } = usePermissions();
  const [activeTab, setActiveTab] = useState('overview');
  const [adminAction, setAdminAction] = useState<AdminAction>(null);
  const [resolveClientError, setResolveClientError] = useState<string | null>(null);
  const canManage = hasPermission('manage guarantors');

  const currentStatus = guarantorRequest.status?.value ?? '';
  const canApproveReject = canManage && currentStatus === 'pending_admin';
  const canCancel = canManage && !TERMINAL_STATUSES.includes(currentStatus);
  const canResolveDispute = canManage && currentStatus === 'disputed';

  const approveForm = useForm({ notes: '' });
  const rejectForm = useForm({ reason: '', notes: '' });
  const cancelForm = useForm({ reason: '', notes: '' });
  const resolveForm = useForm({
    resolution: '' as ResolutionOption | '',
    requester_percentage: 0,
    notes: '',
  });

  const badgeClass = statusBadgeClass[currentStatus] ?? 'badge-light-secondary';
  const isCompany = guarantorRequest.type?.value === 'company';
  const isRTL = i18n.dir() === 'rtl';
  const statusHistories = guarantorRequest.status_histories ?? [];
  const hasDisputeHistory = statusHistories.some((history) => history.to_status?.value === 'disputed');
  const canViewChat = hasPermission('show guarantors');
  const disputeReason =
    statusHistories.find((history) => history.to_status?.value === 'disputed')?.reason ?? null;

  const isValidRequesterPercentage = (value: number): boolean =>
    Number.isInteger(value) && value >= 0 && value <= 100;

  const closeAdminModal = () => {
    setAdminAction(null);
    setResolveClientError(null);
    approveForm.reset();
    rejectForm.reset();
    cancelForm.reset();
    resolveForm.reset();
  };

  const confirmDelete = () => {
    if (window.confirm(t('are_you_sure_delete'))) {
      router.delete(GuarantorDashboardController.destroy(guarantorRequest.id).url);
    }
  };

  const submitAdminAction = () => {
    const options = {
      preserveScroll: true,
      onSuccess: () => closeAdminModal(),
    };

    if (adminAction === 'approve') {
      approveForm.post(GuarantorDashboardController.approveByAdmin(guarantorRequest.id).url, options);
      return;
    }

    if (adminAction === 'reject') {
      rejectForm.post(GuarantorDashboardController.rejectByAdmin(guarantorRequest.id).url, options);
      return;
    }

    if (adminAction === 'cancel') {
      cancelForm.post(GuarantorDashboardController.cancel(guarantorRequest.id).url, options);
      return;
    }

    if (adminAction === 'resolve') {
      if (
        resolveForm.data.resolution === 'percentage_split' &&
        !isValidRequesterPercentage(Number(resolveForm.data.requester_percentage))
      ) {
        setResolveClientError(t('guarantor.invalid_requester_percentage'));
        return;
      }

      setResolveClientError(null);
      resolveForm.put(GuarantorDashboardController.resolveDispute(guarantorRequest.id).url, options);
    }
  };

  const releaseInstallment = (installmentId: string) => {
    if (window.confirm(t('guarantor.release_confirm'))) {
      router.post(
        GuarantorDashboardController.releaseInstallment({
          guarantorRequest: guarantorRequest.id,
          installment: installmentId,
        }).url,
        {},
        { preserveScroll: true },
      );
    }
  };

  const activeForm =
    adminAction === 'approve'
      ? approveForm
      : adminAction === 'reject'
        ? rejectForm
        : adminAction === 'resolve'
          ? resolveForm
          : cancelForm;

  const resolveSubmitDisabled =
    activeForm.processing ||
    (adminAction === 'resolve' &&
      (resolveForm.data.resolution === '' ||
        (resolveForm.data.resolution === 'percentage_split' &&
          !isValidRequesterPercentage(Number(resolveForm.data.requester_percentage)))));

  return (
    <Content>
      <style>{timelineStyles}</style>
      <Head title={`${t('guarantor.module_title_show')} #${guarantorRequest.id}`} />
      <PageTitle
        breadcrumbs={[
          { title: t('guarantor.module_title'), path: GuarantorDashboardController.index().url, isSeparator: false, isActive: false },
        ]}
      >
        {t('guarantor.module_title_show')}
      </PageTitle>

      <div className="d-flex flex-column gap-lg-10 gap-7">
        <KTCard className="border-0 shadow-sm">
          <KTCardBody className="p-9">
            <div className="d-flex justify-content-between align-items-start flex-wrap mb-6 gap-4">
              <div>
                <div className="d-flex align-items-center gap-2 mb-3 flex-wrap">
                  <h1 className="fs-2 fw-bolder text-gray-900 mb-0">{guarantorRequest.title}</h1>
                  <span className={`badge ${badgeClass} fw-bold px-3 py-2`}>{guarantorRequest.status?.label}</span>
                  <span className="badge badge-light-info fw-bold px-3 py-2">{guarantorRequest.type?.label}</span>
                  {currentStatus === 'overdue' && (
                    <span className="badge badge-danger fw-bold px-3 py-2">{t('guarantor.status.overdue')}</span>
                  )}
                </div>
                <div className="text-muted fw-semibold fs-6">
                  {new Date(guarantorRequest.created_at).toLocaleString()}
                </div>
              </div>
              <div className="d-flex gap-2 flex-wrap">
                <Link href={GuarantorDashboardController.index().url} className="btn btn-sm btn-light">
                  <KTIcon iconName="arrow-left" className="fs-6 px-1" />
                  {t('back')}
                </Link>
                {canApproveReject && (
                  <>
                    <button type="button" className="btn btn-sm btn-light-success" onClick={() => setAdminAction('approve')}>
                      {t('guarantor.approve')}
                    </button>
                    <button type="button" className="btn btn-sm btn-light-danger" onClick={() => setAdminAction('reject')}>
                      {t('guarantor.reject')}
                    </button>
                  </>
                )}
                {canCancel && (
                  <button type="button" className="btn btn-sm btn-light-warning" onClick={() => setAdminAction('cancel')}>
                    {t('guarantor.cancel')}
                  </button>
                )}
                {canResolveDispute && (
                  <button type="button" className="btn btn-sm btn-light-primary" onClick={() => setAdminAction('resolve')}>
                    {t('guarantor.resolve_dispute')}
                  </button>
                )}
                {canManage && (
                  <button type="button" className="btn btn-sm btn-light-danger" onClick={confirmDelete}>
                    {t('delete')}
                  </button>
                )}
              </div>
            </div>

            <div className="d-flex flex-wrap gap-6">
              <div className="min-w-125px rounded border border-dashed border-gray-300 px-4 py-3">
                <div className="fs-2 fw-bolder text-primary">
                  {Number(guarantorRequest.total).toLocaleString()} <span className="fs-6 text-gray-600">{t('SAR')}</span>
                </div>
                <div className="fw-bold fs-6 text-gray-500">{t('guarantor.total_amount')}</div>
              </div>
              {isCompany && (
                <div className="min-w-100px rounded border border-dashed border-gray-300 px-4 py-3">
                  <div className="fs-2 fw-bolder text-gray-900">{guarantorRequest.installments?.length ?? 0}</div>
                  <div className="fw-bold fs-6 text-gray-500">{t('guarantor.installments')}</div>
                </div>
              )}
            </div>
          </KTCardBody>
        </KTCard>

        <KTCard className="border-0 shadow-sm">
          <div className="card-header border-0 pt-6">
            <ul className="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
              {[
                { key: 'overview', label: t('guarantor.overview'), icon: 'element-11' },
                ...(isCompany ? [{ key: 'installments', label: t('guarantor.installments'), icon: 'wallet' }] : []),
                { key: 'documents', label: t('guarantor.documents'), icon: 'file' },
                ...(hasDisputeHistory ? [{ key: 'dispute', label: t('guarantor.dispute'), icon: 'information-5' }] : []),
                { key: 'history', label: t('timeline'), icon: 'time' },
                ...(isCompany ? [{ key: 'company_details', label: t('guarantor.company_details'), icon: 'office-bag' }] : []),
                ...(canViewChat ? [{ key: 'chat', label: t('guarantor.chat'), icon: 'message-text-2' }] : []),
              ].map((tab) => (
                <li className="nav-item" key={tab.key}>
                  <a
                    href="#"
                    className={clsx('nav-link text-active-primary me-6', activeTab === tab.key && 'active')}
                    onClick={(e) => {
                      e.preventDefault();
                      setActiveTab(tab.key);
                    }}
                  >
                    <KTIcon iconName={tab.icon} className="fs-3 me-2" />
                    {tab.label}
                  </a>
                </li>
              ))}
            </ul>
          </div>
          <KTCardBody className="p-9">
            {activeTab === 'overview' && (
              <div className="d-flex flex-column gap-6">
                <div>
                  <h3 className="fw-bolder mb-3">{t('description')}</h3>
                  <p className="fs-6 text-gray-700 mb-0">{guarantorRequest.description || '—'}</p>
                </div>
                <div className="row g-4">
                  <div className="col-md-6">
                    <h4 className="fw-bold mb-2">{t('guarantor.requester')}</h4>
                    <p className="mb-0">{guarantorRequest.requester?.name ?? '—'}</p>
                    {guarantorRequest.requester?.phone && (
                      <p className="text-muted mb-0">{guarantorRequest.requester.phone}</p>
                    )}
                  </div>
                  <div className="col-md-6">
                    <h4 className="fw-bold mb-2">{t('guarantor.counterparty')}</h4>
                    <p className="mb-0">{guarantorRequest.counterparty?.name ?? '—'}</p>
                    {guarantorRequest.counterparty?.phone && (
                      <p className="text-muted mb-0">{guarantorRequest.counterparty.phone}</p>
                    )}
                  </div>
                </div>
                <div className="row g-4">
                  <div className="col-md-4">
                    <span className="text-muted d-block">{t('guarantor.amount')}</span>
                    <span className="fw-bold">{Number(guarantorRequest.amount).toLocaleString()} {t('SAR')}</span>
                  </div>
                  <div className="col-md-4">
                    <span className="text-muted d-block">{t('guarantor.fees')}</span>
                    <span className="fw-bold">{Number(guarantorRequest.fees).toLocaleString()} {t('SAR')}</span>
                  </div>
                  {guarantorRequest.admin_notes && (
                    <div className="col-md-12">
                      <span className="text-muted d-block">{t('guarantor.admin_notes')}</span>
                      <span className="fw-semibold">{guarantorRequest.admin_notes}</span>
                    </div>
                  )}
                </div>
              </div>
            )}

            {activeTab === 'documents' && (
              <DocumentsTab
                requestMedia={guarantorRequest.media ?? []}
                companyMedia={guarantorRequest.company_detail?.media ?? []}
                isCompany={isCompany}
              />
            )}

            {activeTab === 'dispute' && hasDisputeHistory && (
              <DisputeTab statusHistories={statusHistories} />
            )}

            {activeTab === 'installments' && isCompany && (
              <>
                {!guarantorRequest.installments?.length ? (
                  <p className="text-muted fst-italic mb-0">{t('guarantor.no_installments')}</p>
                ) : (
                  <div className="table-responsive">
                    <table className="table table-row-bordered align-middle gs-0 gy-4">
                      <thead>
                        <tr className="fw-bold text-muted bg-light">
                          <th>#</th>
                          <th>{t('guarantor.amount')}</th>
                          <th>{t('guarantor.due_date')}</th>
                          <th>{t('status')}</th>
                          <th>{t('guarantor.paid_at')}</th>
                          <th>{t('guarantor.released_at')}</th>
                          {canManage && <th>{t('actions')}</th>}
                        </tr>
                      </thead>
                      <tbody>
                        {guarantorRequest.installments.map((installment) => (
                          <tr key={installment.id}>
                            <td>{installment.order}</td>
                            <td>{Number(installment.amount).toLocaleString()} {t('SAR')}</td>
                            <td>{installment.due_date}</td>
                            <td>
                              <span className="badge badge-light fw-bold">{installment.status?.label}</span>
                            </td>
                            <td>{installment.paid_at ? new Date(installment.paid_at).toLocaleString() : '—'}</td>
                            <td>{installment.released_at ? new Date(installment.released_at).toLocaleString() : '—'}</td>
                            {canManage && (
                              <td>
                                {installment.status?.value === 'paid' && (
                                  <button
                                    type="button"
                                    className="btn btn-sm btn-light-success"
                                    onClick={() => releaseInstallment(installment.id)}
                                  >
                                    {t('guarantor.release_installment')}
                                  </button>
                                )}
                              </td>
                            )}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </>
            )}

            {activeTab === 'history' && (
              <>
                {!statusHistories.length ? (
                  <p className="text-muted fst-italic mb-0">{t('guarantor.no_history')}</p>
                ) : (
                  <div className="timeline">
                    {statusHistories.map((history, index) => (
                      <div key={history.id} className="timeline-item mb-4">
                        <div className="timeline-line">
                          <div
                            className="timeline-badge"
                            style={{
                              backgroundColor: history.to_status.color,
                              width: '14px',
                              height: '14px',
                              marginTop: '8px',
                              border: '2px solid white',
                              boxShadow: `0 0 0 2px ${history.to_status.color}`,
                            }}
                          />
                          {index < statusHistories.length - 1 && <div className="timeline-connector" />}
                        </div>

                        <div className="timeline-content card card-bordered mb-3">
                          <div className="card-body py-3 px-4">
                            <div className="d-flex align-items-center gap-2 flex-wrap mb-2">
                              {history.from_status ? (
                                <span
                                  className="badge badge-outline"
                                  style={{
                                    color: history.from_status.color,
                                    border: `1px solid ${history.from_status.color}`,
                                    backgroundColor: `${history.from_status.color}20`,
                                  }}
                                >
                                  {history.from_status.label}
                                </span>
                              ) : (
                                <span className="badge badge-light-secondary">{t('guarantor.created')}</span>
                              )}

                              <i className={`bi ${isRTL ? 'bi-arrow-left' : 'bi-arrow-right'} text-muted fs-7`} />

                              <span
                                className="badge text-white"
                                style={{ backgroundColor: history.to_status.color }}
                              >
                                {history.to_status.label}
                              </span>
                            </div>

                            <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                              <div className="d-flex align-items-center gap-2">
                                <div className="symbol symbol-30px">
                                  {history.actor?.image ? (
                                    <img src={history.actor.image} className="rounded-circle" alt="" />
                                  ) : (
                                    <div className="symbol-label bg-light-primary text-primary fw-bold fs-7">
                                      {history.actor?.name?.charAt(0)?.toUpperCase() ?? '?'}
                                    </div>
                                  )}
                                </div>
                                <span className="text-muted fs-7">
                                  {history.actor?.name ?? t('guarantor.system')}
                                </span>
                              </div>
                              <span className="text-muted fs-8">
                                {new Date(history.created_at).toLocaleString()}
                              </span>
                            </div>

                            {history.reason && (
                              <div className="mt-2 text-muted fs-7">
                                <span className="fw-bold">{t('guarantor.reason')}: </span>
                                {history.reason}
                              </div>
                            )}

                            {history.notes && (
                              <div className="mt-1 text-muted fs-7">
                                <span className="fw-bold">{t('guarantor.notes')}: </span>
                                {history.notes}
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}

            {activeTab === 'company_details' && isCompany && guarantorRequest.company_detail && (
              <div className="row g-4">
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('guarantor.company_name')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.company_name}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('guarantor.commercial_register')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.commercial_register}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('guarantor.authorized_name')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.authorized_name}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('guarantor.authorized_id_number')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.authorized_id_number}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('guarantor.requester_iban')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.requester_iban ?? '—'}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('guarantor.counterparty_iban')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.counterparty_iban ?? '—'}</span>
                </div>
              </div>
            )}

            {activeTab === 'chat' && canViewChat && (
              <ChatTap
                guarantorRequestId={guarantorRequest.id}
                conversation={guarantorRequest.conversation ?? null}
              />
            )}
          </KTCardBody>
        </KTCard>
      </div>

      <Modal show={adminAction !== null} onHide={closeAdminModal} centered>
        <Modal.Header closeButton>
          <Modal.Title>
            {adminAction === 'approve' && t('guarantor.approve')}
            {adminAction === 'reject' && t('guarantor.reject')}
            {adminAction === 'cancel' && t('guarantor.cancel')}
            {adminAction === 'resolve' && t('guarantor.resolve_dispute')}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {adminAction === 'approve' && (
            <div className="mb-0">
              <label className="form-label">{t('guarantor.notes')}</label>
              <textarea
                className="form-control form-control-solid"
                rows={3}
                value={approveForm.data.notes}
                onChange={(e) => approveForm.setData('notes', e.target.value)}
                placeholder={t('guarantor.enter_notes')}
              />
            </div>
          )}
          {(adminAction === 'reject' || adminAction === 'cancel') && (
            <>
              <div className="mb-4">
                <label className="form-label required">{t('guarantor.reason')}</label>
                <textarea
                  className="form-control form-control-solid"
                  rows={3}
                  value={adminAction === 'reject' ? rejectForm.data.reason : cancelForm.data.reason}
                  onChange={(e) =>
                    adminAction === 'reject'
                      ? rejectForm.setData('reason', e.target.value)
                      : cancelForm.setData('reason', e.target.value)
                  }
                  placeholder={t('guarantor.enter_reason')}
                />
                {(adminAction === 'reject' ? rejectForm.errors.reason : cancelForm.errors.reason) && (
                  <div className="text-danger fs-7 mt-1">
                    {adminAction === 'reject' ? rejectForm.errors.reason : cancelForm.errors.reason}
                  </div>
                )}
              </div>
              <div className="mb-0">
                <label className="form-label">{t('guarantor.notes')}</label>
                <textarea
                  className="form-control form-control-solid"
                  rows={3}
                  value={adminAction === 'reject' ? rejectForm.data.notes : cancelForm.data.notes}
                  onChange={(e) =>
                    adminAction === 'reject'
                      ? rejectForm.setData('notes', e.target.value)
                      : cancelForm.setData('notes', e.target.value)
                  }
                  placeholder={t('guarantor.enter_notes')}
                />
              </div>
            </>
          )}
          {adminAction === 'resolve' && (
            <>
              <div className="mb-4 rounded border border-dashed border-gray-300 bg-light p-4">
                <span className="text-muted d-block fs-7 mb-1">{t('guarantor.dispute_opened_reason')}</span>
                <span className="fw-semibold text-gray-800">
                  {disputeReason || t('guarantor.no_dispute_reason')}
                </span>
              </div>

              <div className="mb-4">
                <label className="form-label required">{t('guarantor.select_resolution')}</label>
                <div className="d-flex flex-column gap-3">
                  {RESOLUTION_OPTIONS.map((option) => (
                    <label key={option} className="form-check form-check-custom form-check-solid">
                      <input
                        className="form-check-input"
                        type="radio"
                        name="resolution"
                        value={option}
                        checked={resolveForm.data.resolution === option}
                        onChange={() => {
                          setResolveClientError(null);
                          resolveForm.setData('resolution', option);
                        }}
                      />
                      <span className="form-check-label fw-semibold">
                        {t(`guarantor.dispute_resolution.${option}`)}
                      </span>
                    </label>
                  ))}
                </div>
                {resolveForm.errors.resolution && (
                  <div className="text-danger fs-7 mt-1">{resolveForm.errors.resolution}</div>
                )}
              </div>

              {resolveForm.data.resolution === 'percentage_split' && (
                <div className="mb-4">
                  <label className="form-label required">{t('guarantor.requester_percentage')}</label>
                  <input
                    type="number"
                    className="form-control form-control-solid"
                    min={0}
                    max={100}
                    step={1}
                    value={resolveForm.data.requester_percentage}
                    onChange={(e) => {
                      setResolveClientError(null);
                      resolveForm.setData('requester_percentage', Number(e.target.value));
                    }}
                  />
                  <div className="text-muted fs-7 mt-2">
                    {t('guarantor.percentage_split_helper', {
                      requester: Number(resolveForm.data.requester_percentage) || 0,
                      counterparty: 100 - (Number(resolveForm.data.requester_percentage) || 0),
                    })}
                  </div>
                  {(resolveClientError || resolveForm.errors.requester_percentage) && (
                    <div className="text-danger fs-7 mt-1">
                      {resolveClientError || resolveForm.errors.requester_percentage}
                    </div>
                  )}
                </div>
              )}

              <div className="mb-0">
                <label className="form-label">{t('guarantor.notes')}</label>
                <textarea
                  className="form-control form-control-solid"
                  rows={3}
                  value={resolveForm.data.notes}
                  onChange={(e) => resolveForm.setData('notes', e.target.value)}
                  placeholder={t('guarantor.enter_notes')}
                />
              </div>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={closeAdminModal}>
            {t('close')}
          </Button>
          <Button
            variant="primary"
            onClick={submitAdminAction}
            disabled={adminAction === 'resolve' ? resolveSubmitDisabled : activeForm.processing}
          >
            {adminAction === 'resolve' ? t('guarantor.resolve_dispute_confirm') : t('confirm')}
          </Button>
        </Modal.Footer>
      </Modal>
    </Content>
  );
};

Show.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Show;
