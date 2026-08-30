import CommentController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/CommentController';
import OfferController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OfferController';
import OpportunityController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OpportunityController';
import usePermissions from '@/shared/hooks/use-permissions';
import { KTIcon, KTCard, KTCardBody } from '@/vendor/metronic/helpers';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ReactElement, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import {
  OPPORTUNITY_PAGE_TITLE_KEY,
  canApproveRejectOpportunity,
  canSubmitOpportunityReject,
  getOpportunityStatusBadgeClass,
  shouldRenderRejectionReason,
} from './opportunity-admin-utils';

type Author = {
  id: string | number;
  name: string;
  type: 'user' | 'provider';
};

type OfferItem = {
  id: string;
  price: string | number;
  description?: string | null;
  status: { value: string; label: string; color: string };
  author?: Author;
  created_at: string;
};

type CommentItem = {
  id: string;
  body: string;
  author?: Author;
  created_at: string;
};

type MediaItem = {
  uuid: string;
  url: string;
  mime_type: string;
};

type OpportunityResource = {
  id: string;
  title: string;
  description: string;
  budget: string | number;
  status: { value: string; label: string; color: string };
  rejection_reason?: string | null;
  author?: Author;
  region?: { title?: string };
  city?: { title?: string };
  offers?: OfferItem[];
  comments?: CommentItem[];
  accepted_offer?: OfferItem | null;
  media?: MediaItem[];
  offers_count?: number;
  comments_count?: number;
  expires_at?: string | null;
  created_at: string;
};

type Props = {
  opportunity: OpportunityResource;
};

type AdminAction = 'approve' | 'reject' | null;

const offerStatusBadgeClass: Record<string, string> = {
  pending: 'badge-light-primary',
  accepted: 'badge-light-success',
  rejected: 'badge-light-danger',
  cancelled: 'badge-light-danger',
};

const Show = ({ opportunity }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canDelete = hasPermission('delete opportunities');
  const canManage = hasPermission('manage opportunities');
  const currentStatus = opportunity.status?.value ?? '';
  const canApproveReject = canApproveRejectOpportunity(currentStatus, canManage);
  const badgeClass = getOpportunityStatusBadgeClass(currentStatus);
  const [adminAction, setAdminAction] = useState<AdminAction>(null);

  const approveForm = useForm({ notes: '' });
  const rejectForm = useForm({ reason: '', notes: '' });

  const createdDate = new Date(opportunity.created_at).toLocaleDateString();
  const authorLabel = opportunity.author
    ? `${opportunity.author.name} (${opportunity.author.type === 'user' ? t('user') : t('provider')})`
    : null;
  const subtitle = authorLabel
    ? t('opportunity.subtitle', {
        author: authorLabel,
        date: createdDate,
        defaultValue: `${authorLabel} · ${createdDate}`,
      })
    : createdDate;

  const confirmDelete = (callback: () => void) => {
    if (window.confirm(t('are_you_sure_delete'))) {
      callback();
    }
  };

  const closeAdminModal = () => {
    setAdminAction(null);
    approveForm.reset();
    rejectForm.reset();
  };

  const submitAdminAction = () => {
    const options = {
      preserveScroll: true,
      onSuccess: () => closeAdminModal(),
    };

    if (adminAction === 'approve') {
      approveForm.post(OpportunityController.approveByAdmin(opportunity.id).url, options);
      return;
    }

    if (adminAction === 'reject') {
      if (!canSubmitOpportunityReject(rejectForm.data.reason)) {
        rejectForm.setError('reason', t('opportunity.enter_reason'));
        return;
      }

      rejectForm.post(OpportunityController.rejectByAdmin(opportunity.id).url, options);
    }
  };

  const activeForm = adminAction === 'approve' ? approveForm : rejectForm;

  return (
    <Content>
      <Head title={`${t(OPPORTUNITY_PAGE_TITLE_KEY)} #${opportunity.id}`} />
      <PageTitle
        breadcrumbs={[
          { title: t('opportunities'), path: OpportunityController.index().url, isSeparator: false, isActive: false },
        ]}
      >
        {t(OPPORTUNITY_PAGE_TITLE_KEY)}
      </PageTitle>

      <div className="d-flex flex-column gap-7 gap-lg-10">
        {/* Header card — mirrors Guarantor / CarAdvisement Show */}
        <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div className="card-body p-6 p-lg-8 bg-light-primary bg-opacity-10">
            <div className="d-flex justify-content-between align-items-start flex-wrap gap-5 mb-6">
              <div className="d-flex align-items-start gap-4 min-w-0">
                <div className="symbol symbol-55px symbol-circle flex-shrink-0">
                  <span className="symbol-label bg-white text-primary shadow-sm">
                    <KTIcon iconName="briefcase" className="fs-2x" />
                  </span>
                </div>
                <div className="min-w-0">
                  <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <h1 className="fs-2 fw-bolder text-gray-900 mb-0 text-truncate">{opportunity.title}</h1>
                    <span className={`badge ${badgeClass} rounded-pill fw-bold px-3 py-2`}>
                      {opportunity.status?.label}
                    </span>
                    {opportunity.author && (
                      <span className="badge badge-light-info rounded-pill fw-bold px-3 py-2">
                        {opportunity.author.type === 'user' ? t('user') : t('provider')}
                      </span>
                    )}
                  </div>
                  <div className="text-muted fw-semibold fs-6">{subtitle}</div>
                </div>
              </div>

              <div className="d-flex gap-2 flex-wrap align-items-center">
                <Link
                  href={OpportunityController.index().url}
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
                      {t('opportunity.approve')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-sm btn-light-danger rounded-pill"
                      onClick={() => setAdminAction('reject')}
                    >
                      {t('opportunity.reject')}
                    </button>
                  </>
                )}
                {canDelete && (
                  <button
                    type="button"
                    className="btn btn-sm btn-light-danger rounded-pill"
                    onClick={() =>
                      confirmDelete(() => router.delete(OpportunityController.destroy(opportunity.id).url))
                    }
                  >
                    <KTIcon iconName="trash" className="fs-5" />
                    {t('delete')}
                  </button>
                )}
              </div>
            </div>

            {shouldRenderRejectionReason(currentStatus, opportunity.rejection_reason) && (
              <div className="bg-light rounded-3 p-4 mb-6">
                <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                  {t('opportunity.reason', { defaultValue: 'Rejection reason' })}
                </div>
                <div className="fw-semibold text-gray-800">{opportunity.rejection_reason}</div>
              </div>
            )}

            <div className="row g-4">
              <div className="col-md-4">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('budget')}</div>
                  <div className="fs-3 fw-bolder text-gray-900">
                    {Number(opportunity.budget).toLocaleString()}{' '}
                    <span className="fs-6 text-muted fw-semibold">{t('SAR')}</span>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">{t('offers')}</div>
                  <div className="fs-3 fw-bolder text-gray-900">{opportunity.offers_count ?? 0}</div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="bg-white rounded-3 p-4 border border-gray-100 h-100">
                  <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                    {t('opportunity.comments', { defaultValue: 'Comments' })}
                  </div>
                  <div className="fs-3 fw-bolder text-gray-900">{opportunity.comments_count ?? 0}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <KTCard className="border-0 shadow-sm rounded-4">
          <KTCardBody className="p-6 p-lg-9">
            <div className="text-muted fs-8 text-uppercase fw-bold mb-2">{t('description')}</div>
            <p className={`fs-6 mb-0 lh-lg ${opportunity.description ? 'text-gray-800' : 'text-muted'}`}>
              {opportunity.description || t('no_description')}
            </p>
          </KTCardBody>
        </KTCard>

        {opportunity.media && opportunity.media.length > 0 && (
          <KTCard className="border-0 shadow-sm rounded-4">
            <KTCardBody className="p-6 p-lg-9">
              <h3 className="fw-bolder text-gray-900 mb-5">{t('media')}</h3>
              <div className="row g-4">
                {opportunity.media.map((med) => (
                  <div className="col-md-4 col-sm-6" key={med.uuid}>
                    {med.mime_type?.startsWith('image/') ? (
                      <a href={med.url} target="_blank" rel="noreferrer">
                        <div
                          className="rounded-3"
                          style={{
                            height: '150px',
                            background: `url(${med.url}) center/cover no-repeat`,
                            border: '1px solid #e4e6ef',
                          }}
                        />
                      </a>
                    ) : (
                      <a href={med.url} target="_blank" rel="noreferrer" className="btn btn-light-primary btn-sm rounded-pill">
                        {t('download')}
                      </a>
                    )}
                  </div>
                ))}
              </div>
            </KTCardBody>
          </KTCard>
        )}

        {opportunity.accepted_offer && (
          <KTCard className="border-0 shadow-sm rounded-4">
            <KTCardBody className="p-6 p-lg-9">
              <h3 className="fw-bolder text-gray-900 mb-5">{t('accepted_offer')}</h3>
              <div className="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                  <div className="fs-4 fw-bolder text-gray-900 mb-1">
                    {Number(opportunity.accepted_offer.price).toLocaleString()} {t('SAR')}
                  </div>
                  {opportunity.accepted_offer.author && (
                    <div className="text-muted fw-semibold">{opportunity.accepted_offer.author.name}</div>
                  )}
                  {opportunity.accepted_offer.description && (
                    <p className="text-gray-700 mt-3 mb-0">{opportunity.accepted_offer.description}</p>
                  )}
                </div>
                <span
                  className={`badge ${offerStatusBadgeClass[opportunity.accepted_offer.status?.value] ?? 'badge-light-secondary'} rounded-pill fw-bold px-3 py-2`}
                >
                  {opportunity.accepted_offer.status?.label}
                </span>
              </div>
            </KTCardBody>
          </KTCard>
        )}

        <KTCard className="border-0 shadow-sm rounded-4">
          <KTCardBody className="p-6 p-lg-9">
            <h3 className="fw-bolder text-gray-900 mb-5">{t('offers')}</h3>
            {!opportunity.offers?.length ? (
              <p className="text-muted fst-italic mb-0">{t('no_offers')}</p>
            ) : (
              <div className="d-flex flex-column gap-4">
                {opportunity.offers.map((offer) => (
                  <div
                    key={offer.id}
                    className="d-flex justify-content-between align-items-start flex-wrap gap-3 border border-dashed border-gray-300 rounded-3 p-4"
                  >
                    <div>
                      <div className="fs-5 fw-bolder text-gray-900 mb-1">
                        {Number(offer.price).toLocaleString()} {t('SAR')}
                      </div>
                      {offer.author && <div className="text-muted fw-semibold fs-7">{offer.author.name}</div>}
                      {offer.description && <p className="text-gray-700 mt-2 mb-0 fs-7">{offer.description}</p>}
                      <div className="text-muted fs-8 mt-2">{new Date(offer.created_at).toLocaleString()}</div>
                    </div>
                    <div className="d-flex align-items-center gap-2">
                      <span
                        className={`badge ${offerStatusBadgeClass[offer.status?.value] ?? 'badge-light-secondary'} rounded-pill fw-bold px-3 py-2`}
                      >
                        {offer.status?.label}
                      </span>
                      {canDelete && (
                        <button
                          type="button"
                          className="btn btn-sm btn-light-danger rounded-pill"
                          onClick={() =>
                            confirmDelete(() =>
                              router.delete(OfferController.destroy(offer.id).url, { preserveScroll: true }),
                            )
                          }
                        >
                          {t('delete')}
                        </button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </KTCardBody>
        </KTCard>

        <KTCard className="border-0 shadow-sm rounded-4">
          <KTCardBody className="p-6 p-lg-9">
            <h3 className="fw-bolder text-gray-900 mb-5">
              {t('opportunity.comments', { defaultValue: 'Comments' })}
            </h3>
            {!opportunity.comments?.length ? (
              <p className="text-muted fst-italic mb-0">{t('no_comments')}</p>
            ) : (
              <div className="d-flex flex-column gap-4">
                {opportunity.comments.map((comment) => (
                  <div
                    key={comment.id}
                    className="d-flex justify-content-between align-items-start flex-wrap gap-3 border border-dashed border-gray-300 rounded-3 p-4"
                  >
                    <div>
                      {comment.author && <div className="fw-bold text-gray-900 mb-1">{comment.author.name}</div>}
                      <p className="text-gray-700 mb-1">{comment.body}</p>
                      <div className="text-muted fs-8">{new Date(comment.created_at).toLocaleString()}</div>
                    </div>
                    {canDelete && (
                      <button
                        type="button"
                        className="btn btn-sm btn-light-danger rounded-pill"
                        onClick={() =>
                          confirmDelete(() =>
                            router.delete(CommentController.destroy(comment.id).url, { preserveScroll: true }),
                          )
                        }
                      >
                        {t('delete')}
                      </button>
                    )}
                  </div>
                ))}
              </div>
            )}
          </KTCardBody>
        </KTCard>
      </div>

      <Modal show={adminAction !== null} onHide={closeAdminModal} centered>
        <Modal.Header closeButton>
          <Modal.Title>
            {adminAction === 'approve' && t('opportunity.approve')}
            {adminAction === 'reject' && t('opportunity.reject')}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {adminAction === 'approve' && (
            <div className="mb-0">
              <label className="form-label">{t('opportunity.notes')}</label>
              <textarea
                className="form-control"
                rows={3}
                value={approveForm.data.notes}
                onChange={(e) => approveForm.setData('notes', e.target.value)}
                placeholder={t('opportunity.enter_notes')}
              />
              {approveForm.errors.notes && (
                <div className="text-danger fs-7 mt-1">{approveForm.errors.notes}</div>
              )}
            </div>
          )}
          {adminAction === 'reject' && (
            <>
              <div className="mb-4">
                <label className="form-label required">{t('opportunity.reason')}</label>
                <textarea
                  className="form-control"
                  rows={3}
                  value={rejectForm.data.reason}
                  onChange={(e) => rejectForm.setData('reason', e.target.value)}
                  placeholder={t('opportunity.enter_reason')}
                />
                {rejectForm.errors.reason && (
                  <div className="text-danger fs-7 mt-1">{rejectForm.errors.reason}</div>
                )}
              </div>
              <div className="mb-0">
                <label className="form-label">{t('opportunity.notes')}</label>
                <textarea
                  className="form-control"
                  rows={2}
                  value={rejectForm.data.notes}
                  onChange={(e) => rejectForm.setData('notes', e.target.value)}
                  placeholder={t('opportunity.enter_notes')}
                />
                {rejectForm.errors.notes && (
                  <div className="text-danger fs-7 mt-1">{rejectForm.errors.notes}</div>
                )}
              </div>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={closeAdminModal}>
            {t('cancel')}
          </Button>
          <Button
            variant={adminAction === 'reject' ? 'danger' : 'success'}
            onClick={submitAdminAction}
            disabled={activeForm.processing}
          >
            {adminAction === 'approve' ? t('opportunity.approve') : t('opportunity.reject')}
          </Button>
        </Modal.Footer>
      </Modal>
    </Content>
  );
};

Show.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Show;
