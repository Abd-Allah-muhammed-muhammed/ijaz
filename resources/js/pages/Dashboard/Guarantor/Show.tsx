import GuarantorDashboardController from '@/actions/Modules/Guarantor/Http/Controllers/Dashboard/GuarantorController';
import { KTIcon, KTCard, KTCardBody } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { PageTitle } from '@/_metronic/layout/core';
import usePermissions from '@/hooks/use-permissions';
import { Head, Link, router, useForm } from '@inertiajs/react';
import clsx from 'clsx';
import { ReactElement, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';

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

type MediaItem = {
  uuid: string;
  url: string;
  mime_type: string;
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
  overdue_at?: string | null;
  ended_at?: string | null;
  cancelled_at?: string | null;
  created_at: string;
};

type Props = {
  guarantorRequest: GuarantorResource;
  selects: { statuses: StatusOption[] };
};

const statusBadgeClass: Record<string, string> = {
  new: 'badge-light-primary',
  approved: 'badge-light-info',
  rejected: 'badge-light-warning',
  in_progress: 'badge-light-success',
  overdue: 'badge-light-danger',
  ended: 'badge-light-success',
  cancelled: 'badge-light-danger',
  refunded: 'badge-light-secondary',
};

const Show = ({ guarantorRequest, selects }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const [activeTab, setActiveTab] = useState('overview');
  const [showStatusModal, setShowStatusModal] = useState(false);
  const canManage = hasPermission('manage guarantors');

  const statusForm = useForm({
    status: guarantorRequest.status?.value ?? '',
    reason: '',
    notes: '',
  });

  const badgeClass = statusBadgeClass[guarantorRequest.status?.value] ?? 'badge-light-secondary';
  const isCompany = guarantorRequest.type?.value === 'company';

  const confirmDelete = () => {
    if (window.confirm(t('are_you_sure_delete'))) {
      router.delete(GuarantorDashboardController.destroy(guarantorRequest.id).url);
    }
  };

  const submitStatusChange = () => {
    statusForm.post(GuarantorDashboardController.updateStatus(guarantorRequest.id).url, {
      preserveScroll: true,
      onSuccess: () => {
        setShowStatusModal(false);
        statusForm.reset('reason', 'notes');
      },
    });
  };

  const releaseInstallment = (installmentId: string) => {
    if (window.confirm(t('are_you_sure'))) {
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

  return (
    <Content>
      <Head title={`${t('guarantor')} #${guarantorRequest.id}`} />
      <PageTitle
        breadcrumbs={[
          { title: t('guarantors'), path: GuarantorDashboardController.index().url, isSeparator: false, isActive: false },
        ]}
      >
        {t('guarantor')}
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
                  {guarantorRequest.status?.value === 'overdue' && (
                    <span className="badge badge-danger fw-bold px-3 py-2">{t('guarantor_status_overdue')}</span>
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
                {canManage && (
                  <button type="button" className="btn btn-sm btn-light-primary" onClick={() => setShowStatusModal(true)}>
                    {t('change_status')}
                  </button>
                )}
                <button type="button" className="btn btn-sm btn-light-danger" onClick={confirmDelete}>
                  {t('delete')}
                </button>
              </div>
            </div>

            <div className="d-flex flex-wrap gap-6">
              <div className="min-w-125px rounded border border-dashed border-gray-300 px-4 py-3">
                <div className="fs-2 fw-bolder text-primary">
                  {Number(guarantorRequest.total).toLocaleString()} <span className="fs-6 text-gray-600">{t('SAR')}</span>
                </div>
                <div className="fw-bold fs-6 text-gray-500">{t('total_amount')}</div>
              </div>
              <div className="min-w-100px rounded border border-dashed border-gray-300 px-4 py-3">
                <div className="fs-2 fw-bolder text-gray-900">{guarantorRequest.installments?.length ?? 0}</div>
                <div className="fw-bold fs-6 text-gray-500">{t('installments')}</div>
              </div>
            </div>
          </KTCardBody>
        </KTCard>

        <KTCard className="border-0 shadow-sm">
          <div className="card-header border-0 pt-6">
            <ul className="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
              {[
                { key: 'overview', label: t('overview'), icon: 'element-11' },
                { key: 'installments', label: t('installments'), icon: 'wallet' },
                { key: 'history', label: t('status_history'), icon: 'time' },
                ...(isCompany ? [{ key: 'company', label: t('company_details'), icon: 'office-bag' }] : []),
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
                    <h4 className="fw-bold mb-2">{t('requester')}</h4>
                    <p className="mb-0">{guarantorRequest.requester?.name ?? '—'}</p>
                    {guarantorRequest.requester?.phone && (
                      <p className="text-muted mb-0">{guarantorRequest.requester.phone}</p>
                    )}
                  </div>
                  <div className="col-md-6">
                    <h4 className="fw-bold mb-2">{t('counterparty')}</h4>
                    <p className="mb-0">{guarantorRequest.counterparty?.name ?? '—'}</p>
                    {guarantorRequest.counterparty?.phone && (
                      <p className="text-muted mb-0">{guarantorRequest.counterparty.phone}</p>
                    )}
                  </div>
                </div>
                <div className="row g-4">
                  <div className="col-md-4">
                    <span className="text-muted d-block">{t('amount')}</span>
                    <span className="fw-bold">{Number(guarantorRequest.amount).toLocaleString()} {t('SAR')}</span>
                  </div>
                  <div className="col-md-4">
                    <span className="text-muted d-block">{t('fees')}</span>
                    <span className="fw-bold">{Number(guarantorRequest.fees).toLocaleString()} {t('SAR')}</span>
                  </div>
                  {guarantorRequest.admin_notes && (
                    <div className="col-md-12">
                      <span className="text-muted d-block">{t('admin_notes')}</span>
                      <span className="fw-semibold">{guarantorRequest.admin_notes}</span>
                    </div>
                  )}
                </div>
                {guarantorRequest.media && guarantorRequest.media.length > 0 && (
                  <div>
                    <h4 className="fw-bold mb-3">{t('media')}</h4>
                    <div className="d-flex flex-wrap gap-3">
                      {guarantorRequest.media.map((med) => (
                        <a key={med.uuid} href={med.url} target="_blank" rel="noreferrer" className="btn btn-light-primary btn-sm">
                          {t('download')}
                        </a>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'installments' && (
              <>
                {!guarantorRequest.installments?.length ? (
                  <p className="text-muted fst-italic mb-0">{t('no_installments')}</p>
                ) : (
                  <div className="table-responsive">
                    <table className="table table-row-bordered align-middle gs-0 gy-4">
                      <thead>
                        <tr className="fw-bold text-muted bg-light">
                          <th>#</th>
                          <th>{t('amount')}</th>
                          <th>{t('due_date')}</th>
                          <th>{t('status')}</th>
                          <th>{t('paid_at')}</th>
                          <th>{t('released_at')}</th>
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
                                    {t('release_installment')}
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
                {!guarantorRequest.status_histories?.length ? (
                  <p className="text-muted fst-italic mb-0">—</p>
                ) : (
                  <div className="d-flex flex-column gap-4">
                    {guarantorRequest.status_histories.map((history) => (
                      <div key={history.id} className="border border-dashed border-gray-300 rounded p-4">
                        <div className="d-flex justify-content-between flex-wrap gap-2 mb-2">
                          <div className="fw-bold">
                            {history.from_status?.label ?? '—'} → {history.to_status?.label}
                          </div>
                          <span className="text-muted fs-7">{new Date(history.created_at).toLocaleString()}</span>
                        </div>
                        {history.actor && (
                          <div className="text-muted fs-7 mb-2">{history.actor.name}</div>
                        )}
                        {history.reason && (
                          <div className="mb-1">
                            <span className="fw-semibold">{t('reason')}:</span> {history.reason}
                          </div>
                        )}
                        {history.notes && (
                          <div>
                            <span className="fw-semibold">{t('notes')}:</span> {history.notes}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}

            {activeTab === 'company' && guarantorRequest.company_detail && (
              <div className="row g-4">
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('company_name')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.company_name}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('commercial_register')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.commercial_register}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('authorized_name')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.authorized_name}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('authorized_id_number')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.authorized_id_number}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('requester_iban')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.requester_iban ?? '—'}</span>
                </div>
                <div className="col-md-6">
                  <span className="text-muted d-block">{t('counterparty_iban')}</span>
                  <span className="fw-bold">{guarantorRequest.company_detail.counterparty_iban ?? '—'}</span>
                </div>
              </div>
            )}
          </KTCardBody>
        </KTCard>
      </div>

      <Modal show={showStatusModal} onHide={() => setShowStatusModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{t('change_status')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="mb-4">
            <label className="form-label required">{t('status')}</label>
            <select
              className="form-select form-select-solid"
              value={statusForm.data.status}
              onChange={(e) => statusForm.setData('status', e.target.value)}
            >
              {selects.statuses.map((status) => (
                <option key={status.value} value={status.value}>
                  {status.label}
                </option>
              ))}
            </select>
          </div>
          <div className="mb-4">
            <label className="form-label required">{t('reason')}</label>
            <textarea
              className="form-control form-control-solid"
              rows={3}
              value={statusForm.data.reason}
              onChange={(e) => statusForm.setData('reason', e.target.value)}
            />
            {statusForm.errors.reason && <div className="text-danger fs-7 mt-1">{statusForm.errors.reason}</div>}
          </div>
          <div className="mb-0">
            <label className="form-label">{t('notes')}</label>
            <textarea
              className="form-control form-control-solid"
              rows={3}
              value={statusForm.data.notes}
              onChange={(e) => statusForm.setData('notes', e.target.value)}
            />
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowStatusModal(false)}>
            {t('cancel')}
          </Button>
          <Button variant="primary" onClick={submitStatusChange} disabled={statusForm.processing}>
            {t('confirm')}
          </Button>
        </Modal.Footer>
      </Modal>
    </Content>
  );
};

Show.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Show;
