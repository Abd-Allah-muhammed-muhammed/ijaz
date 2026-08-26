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

const TERMINAL_STATUSES = ['rejected_by_admin', 'rejected', 'ended', 'ended_via_dispute', 'cancelled', 'cancelled_via_dispute', 'escalated', 'settled'];

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
  ended_via_dispute: 'badge-light-success',
  cancelled: 'badge-light-secondary',
  cancelled_via_dispute: 'badge-light-secondary',
  escalated: 'badge-light-dark',
  settled: 'badge-light-info',
};

const installmentBadgeClass: Record<string, string> = {
  pending: 'badge-light-warning',
  paid: 'badge-light-success',
  released: 'badge-light-primary',
  overdue: 'badge-light-danger',
  voided: 'badge-light-secondary',
  reversed: 'badge-light-dark',
};

const PartyChip = ({
  label,
  participant,
}: {
  label: string;
  participant?: Participant;
}) => (
  <div className="card border-0 shadow-xs rounded-4 h-100 bg-light">
    <div className="card-body p-5">
      <div className="text-muted fs-8 text-uppercase fw-bold mb-3">{label}</div>
      <div className="d-flex align-items-center gap-3">
        <div className="symbol symbol-50px symbol-circle">
          {participant?.image ? (
            <img src={participant.image} alt="" />
          ) : (
            <span className="symbol-label bg-light-primary text-primary fw-bolder fs-4">
              {participant?.name?.charAt(0)?.toUpperCase() ?? '?'}
            </span>
          )}
        </div>
        <div className="min-w-0">
          <div className="fw-bolder text-gray-900 fs-5 text-truncate">
            {participant?.name ?? '—'}
          </div>
          {participant?.phone ? (
            <div className="text-muted fs-7" dir="ltr">{participant.phone}</div>
          ) : (
            <div className="text-muted fs-7">—</div>
          )}
        </div>
      </div>
    </div>
  </div>
);

const Field = ({ label, value }: { label: string; value?: string | null }) => (
  <div>
    <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{label}</div>
    <div className="fw-semibold text-gray-900 fs-6">{value || '—'}</div>
  </div>
);

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

  const installments = guarantorRequest.installments ?? [];
  const paidInstallments = installments.filter((item) =>
    ['paid', 'released'].includes(item.status?.value ?? ''),
  ).length;
  const documentsCount =
    (guarantorRequest.media?.length ?? 0) +
    (guarantorRequest.company_detail?.media?.length ?? 0);

  const createdDate = new Date(guarantorRequest.created_at).toLocaleDateString();
  const subtitle = isCompany
    ? t('guarantor.subtitle_company', { date: createdDate })
    : t('guarantor.subtitle_individual', { date: createdDate });

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

  const tabs = [
    { key: 'overview', label: t('guarantor.overview'), icon: 'element-11' },
    ...(isCompany ? [{ key: 'installments', label: t('guarantor.installments'), icon: 'wallet' }] : []),
    { key: 'documents', label: t('guarantor.documents'), icon: 'file' },
    ...(hasDisputeHistory ? [{ key: 'dispute', label: t('guarantor.dispute'), icon: 'information-5' }] : []),
    { key: 'history', label: t('guarantor.timeline'), icon: 'time' },
    ...(isCompany ? [{ key: 'company_details', label: t('guarantor.company_details'), icon: 'office-bag' }] : []),
    ...(canViewChat ? [{ key: 'chat', label: t('guarantor.chat'), icon: 'message-text-2' }] : []),
  ];

  return (
    <Content>
      <Head title={`${t('guarantor.module_title_show')} #${guarantorRequest.id}`} />
      <PageTitle
        breadcrumbs={[
          { title: t('guarantor.module_title'), path: GuarantorDashboardController.index().url, isSeparator: false, isActive: false },
        ]}
      >
        {t('guarantor.module_title_show')}
      </PageTitle>

      <div className="d-flex flex-column gap-7 gap-lg-10">
        {/* Header card */}
        <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div className="card-body p-6 p-lg-8 bg-light-primary bg-opacity-10">
            <div className="d-flex justify-content-between align-items-start flex-wrap gap-5 mb-6">
              <div className="d-flex align-items-start gap-4 min-w-0">
                <div className="symbol symbol-55px symbol-circle flex-shrink-0">
                  <span className="symbol-label bg-white text-primary shadow-sm">
                    <KTIcon iconName={isCompany ? 'office-bag' : 'profile-user'} className="fs-2x" />
                  </span>
                </div>
                <div className="min-w-0">
                  <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <h1 className="fs-2 fw-bolder text-gray-900 mb-0 text-truncate">
                      {guarantorRequest.title}
                    </h1>
                    <span className={`badge ${badgeClass} rounded-pill fw-bold px-3 py-2`}>
                      {guarantorRequest.status?.label}
                    </span>
                    <span className="badge badge-light-info rounded-pill fw-bold px-3 py-2">
                      {guarantorRequest.type?.label}
                    </span>
                  </div>
                  <div className="text-muted fw-semibold fs-6">{subtitle}</div>
                </div>
              </div>

              <div className="d-flex gap-2 flex-wrap align-items-center">
                <Link
                  href={GuarantorDashboardController.index().url}
                  className="btn btn-sm btn-light btn-active-light-primary rounded-pill"
                >
                  <KTIcon iconName="arrow-left" className="fs-6" />
                  {t('back')}
                </Link>
                {canApproveReject && (
                  <>
                    <button
                      type="button"
                      className="btn btn-sm btn-light-success rounded-pill"
                      onClick={() => setAdminAction('approve')}
                    >
                      {t('guarantor.approve')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-sm btn-light-danger rounded-pill"
                      onClick={() => setAdminAction('reject')}
                    >
                      {t('guarantor.reject')}
                    </button>
                  </>
                )}
                {canCancel && (
                  <button
                    type="button"
                    className="btn btn-sm btn-light-warning rounded-pill"
                    onClick={() => setAdminAction('cancel')}
                  >
                    {t('guarantor.cancel')}
                  </button>
                )}
                {canManage && (
                  <button
                    type="button"
                    className="btn btn-sm btn-light-danger rounded-pill"
                    onClick={confirmDelete}
                  >
                    {t('delete')}
                  </button>
                )}
                {canResolveDispute && (
                  <button
                    type="button"
                    className="btn btn-sm btn-danger rounded-pill fw-bold"
                    onClick={() => setAdminAction('resolve')}
                  >
                    <KTIcon iconName="shield-tick" className="fs-5" />
                    {t('guarantor.resolve_dispute')}
                  </button>
                )}
              </div>
            </div>

            <div className="row g-4">
              <div className="col-md-4">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                    {t('guarantor.total_amount')}
                  </div>
                  <div className="fs-3 fw-bolder text-gray-900">
                    {Number(guarantorRequest.total).toLocaleString()}{' '}
                    <span className="fs-6 text-muted fw-semibold">{t('SAR')}</span>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                    {t('guarantor.installments')}
                  </div>
                  <div className="fs-3 fw-bolder text-gray-900">
                    {isCompany
                      ? `${paidInstallments} / ${installments.length}`
                      : '—'}
                  </div>
                  {isCompany && (
                    <div className="text-muted fs-8 mt-1">
                      {t('guarantor.installments_paid_of_total', {
                        paid: paidInstallments,
                        total: installments.length,
                        defaultValue: `${paidInstallments} of ${installments.length} paid`,
                      })}
                    </div>
                  )}
                </div>
              </div>
              <div className="col-md-4">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                    {t('guarantor.documents')}
                  </div>
                  <div className="fs-3 fw-bolder text-gray-900">{documentsCount}</div>
                </div>
              </div>
            </div>
          </div>

          {/* Pill tab bar */}
          <div className="card-body pt-0 pb-5 px-6 px-lg-8 bg-white border-top border-gray-100">
            <div className="d-flex flex-wrap gap-2 pt-5">
              {tabs.map((tab) => (
                <button
                  key={tab.key}
                  type="button"
                  className={clsx(
                    'btn btn-sm rounded-pill d-inline-flex align-items-center gap-2 px-4 py-2 fw-bold',
                    activeTab === tab.key
                      ? 'btn-primary'
                      : 'btn-light text-gray-600 btn-active-light-primary',
                  )}
                  onClick={() => setActiveTab(tab.key)}
                >
                  <KTIcon iconName={tab.icon} className="fs-4" />
                  {tab.label}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Tab content */}
        <KTCard className="border-0 shadow-sm rounded-4">
          <KTCardBody className="p-6 p-lg-9">
            {activeTab === 'overview' && (
              <div className="d-flex flex-column gap-6">
                <div>
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-2">
                    {t('description')}
                  </div>
                  <p className={clsx('fs-6 mb-0 lh-lg', guarantorRequest.description ? 'text-gray-800' : 'text-muted')}>
                    {guarantorRequest.description || '—'}
                  </p>
                </div>

                <div className="row g-4">
                  <div className="col-md-6">
                    <PartyChip label={t('guarantor.requester')} participant={guarantorRequest.requester} />
                  </div>
                  <div className="col-md-6">
                    <PartyChip label={t('guarantor.counterparty')} participant={guarantorRequest.counterparty} />
                  </div>
                </div>

                <div className="separator separator-dashed my-1" />

                <div className="row g-4">
                  <div className="col-md-4">
                    <div className="bg-light rounded-3 p-4 h-100">
                      <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('guarantor.amount')}</div>
                      <div className="fw-bolder fs-4 text-gray-900">
                        {Number(guarantorRequest.amount).toLocaleString()} {t('SAR')}
                      </div>
                    </div>
                  </div>
                  <div className="col-md-4">
                    <div className="bg-light rounded-3 p-4 h-100">
                      <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('guarantor.fees')}</div>
                      <div className="fw-bolder fs-4 text-gray-900">
                        {Number(guarantorRequest.fees).toLocaleString()} {t('SAR')}
                      </div>
                    </div>
                  </div>
                  <div className="col-md-4">
                    <div className="bg-light-primary bg-opacity-50 rounded-3 p-4 h-100">
                      <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('guarantor.total')}</div>
                      <div className="fw-bolder fs-4 text-primary">
                        {Number(guarantorRequest.total).toLocaleString()} {t('SAR')}
                      </div>
                    </div>
                  </div>
                </div>

                {guarantorRequest.admin_notes && (
                  <div className="bg-light rounded-3 p-4">
                    <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                      {t('guarantor.admin_notes')}
                    </div>
                    <div className="fw-semibold text-gray-800">{guarantorRequest.admin_notes}</div>
                  </div>
                )}
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
                {!installments.length ? (
                  <p className="text-muted fst-italic mb-0">{t('guarantor.no_installments')}</p>
                ) : (
                  <div className="table-responsive">
                    <table className="table align-middle table-row-dashed gs-0 gy-4">
                      <thead>
                        <tr className="text-muted fw-bold fs-7 text-uppercase">
                          <th className="min-w-50px">#</th>
                          <th className="min-w-125px text-end">{t('guarantor.amount')}</th>
                          <th className="min-w-125px">{t('guarantor.due_date')}</th>
                          <th className="min-w-100px">{t('status')}</th>
                          <th className="min-w-125px">{t('guarantor.paid_at')}</th>
                          <th className="min-w-125px">{t('guarantor.released_at')}</th>
                          {canManage && <th className="min-w-100px text-end">{t('actions')}</th>}
                        </tr>
                      </thead>
                      <tbody>
                        {installments.map((installment) => {
                          const statusValue = installment.status?.value ?? '';
                          return (
                            <tr key={installment.id}>
                              <td className="fw-bold text-gray-800">{installment.order}</td>
                              <td className="text-end fw-bolder text-gray-900">
                                {Number(installment.amount).toLocaleString()} {t('SAR')}
                              </td>
                              <td className="text-gray-700">
                                {installment.due_date
                                  ? new Date(installment.due_date).toLocaleDateString()
                                  : '—'}
                              </td>
                              <td>
                                <span
                                  className={clsx(
                                    'badge rounded-pill fw-bold px-3 py-2',
                                    installmentBadgeClass[statusValue] ?? 'badge-light',
                                  )}
                                >
                                  {installment.status?.label}
                                </span>
                              </td>
                              <td className="text-muted fs-7">
                                {installment.paid_at
                                  ? new Date(installment.paid_at).toLocaleString()
                                  : '—'}
                              </td>
                              <td className="text-muted fs-7">
                                {installment.released_at
                                  ? new Date(installment.released_at).toLocaleString()
                                  : '—'}
                              </td>
                              {canManage && (
                                <td className="text-end">
                                  {!TERMINAL_STATUSES.includes(currentStatus) && statusValue === 'paid' && (
                                    <button
                                      type="button"
                                      className="btn btn-sm btn-light-success rounded-pill"
                                      onClick={() => releaseInstallment(installment.id)}
                                    >
                                      <KTIcon iconName="check" className="fs-5" />
                                      {t('guarantor.release')}
                                    </button>
                                  )}
                                </td>
                              )}
                            </tr>
                          );
                        })}
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
                  <div className="d-flex flex-column">
                    {statusHistories.map((history, index) => (
                      <div key={history.id} className="d-flex gap-4">
                        <div className="d-flex flex-column align-items-center">
                          <div
                            className="rounded-circle border border-3 border-white shadow-sm"
                            style={{
                              width: 14,
                              height: 14,
                              backgroundColor: history.to_status.color || '#7239ea',
                              marginTop: 6,
                            }}
                          />
                          {index < statusHistories.length - 1 && (
                            <div className="flex-grow-1 w-2px bg-gray-200 my-1" style={{ minHeight: 40 }} />
                          )}
                        </div>
                        <div className="pb-6 flex-grow-1">
                          <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <span className="badge badge-light rounded-pill px-3 py-2 fw-bold">
                              {history.from_status?.label ?? t('guarantor.created')}
                            </span>
                            <i className={`bi ${isRTL ? 'bi-arrow-left' : 'bi-arrow-right'} text-muted fs-8`} />
                            <span
                              className="badge rounded-pill px-3 py-2 fw-bold text-white"
                              style={{ backgroundColor: history.to_status.color || '#7239ea' }}
                            >
                              {history.to_status.label}
                            </span>
                          </div>
                          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                            <span className="fw-semibold text-gray-800 fs-7">
                              {history.actor?.name ?? t('guarantor.system')}
                            </span>
                            <span className="text-muted fs-8">
                              {new Date(history.created_at).toLocaleString()}
                            </span>
                          </div>
                          {history.reason && (
                            <div className="text-muted fs-7">
                              <span className="fw-bold text-gray-600">{t('guarantor.reason')}: </span>
                              {history.reason}
                            </div>
                          )}
                          {history.notes && (
                            <div className="text-muted fs-7">
                              <span className="fw-bold text-gray-600">{t('guarantor.notes')}: </span>
                              {history.notes}
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}

            {activeTab === 'company_details' && isCompany && guarantorRequest.company_detail && (
              <div className="d-flex flex-column gap-6">
                <div className="card border-0 bg-light rounded-4">
                  <div className="card-body p-5">
                    <h4 className="fw-bolder text-gray-900 mb-5">{t('guarantor.company_details')}</h4>
                    <div className="row g-5">
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.company_name')}
                          value={guarantorRequest.company_detail.company_name}
                        />
                      </div>
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.commercial_register')}
                          value={guarantorRequest.company_detail.commercial_register}
                        />
                      </div>
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.authorized_name')}
                          value={guarantorRequest.company_detail.authorized_name}
                        />
                      </div>
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.authorized_id_number')}
                          value={guarantorRequest.company_detail.authorized_id_number}
                        />
                      </div>
                      {guarantorRequest.company_detail.authorization_type && (
                        <div className="col-md-6">
                          <Field
                            label={t('guarantor.authorization_type_label', {
                              defaultValue: 'Authorization type',
                            })}
                            value={guarantorRequest.company_detail.authorization_type.label}
                          />
                        </div>
                      )}
                      {(guarantorRequest.company_detail.region?.title || guarantorRequest.company_detail.city?.title) && (
                        <div className="col-md-6">
                          <Field
                            label={t('guarantor.location', { defaultValue: 'Location' })}
                            value={[
                              guarantorRequest.company_detail.city?.title,
                              guarantorRequest.company_detail.region?.title,
                            ].filter(Boolean).join(', ')}
                          />
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                <div className="card border border-dashed border-gray-300 rounded-4 bg-white">
                  <div className="card-body p-5">
                    <div className="d-flex align-items-center gap-2 mb-5">
                      <KTIcon iconName="bank" className="fs-2 text-primary" />
                      <h4 className="fw-bolder text-gray-900 mb-0">
                        {t('guarantor.requester_account')} / {t('guarantor.counterparty_account')}
                      </h4>
                    </div>
                    <div className="row g-5">
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.requester_iban')}
                          value={guarantorRequest.company_detail.requester_iban}
                        />
                      </div>
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.account_holder')}
                          value={guarantorRequest.company_detail.requester_account_holder}
                        />
                      </div>
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.counterparty_iban')}
                          value={guarantorRequest.company_detail.counterparty_iban}
                        />
                      </div>
                      <div className="col-md-6">
                        <Field
                          label={t('guarantor.account_holder')}
                          value={guarantorRequest.company_detail.counterparty_account_holder}
                        />
                      </div>
                    </div>
                  </div>
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
              <div className="mb-4 rounded-3 border border-dashed border-gray-300 bg-light p-4">
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
