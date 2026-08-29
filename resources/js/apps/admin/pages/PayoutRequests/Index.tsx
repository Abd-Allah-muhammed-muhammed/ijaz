import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { Head, useForm, usePage } from '@inertiajs/react';
import { KTCard } from '@/vendor/metronic/helpers';
import Table from '@/shared/components/Table';
import usePermissions from '@/shared/hooks/use-permissions';
import { PaginationResource } from '@/shared/types';
import { ReactElement, useState } from 'react';
import PayoutRequestController from '@/actions/Modules/Payout/Http/Controllers/Dashboard/PayoutRequestController';
import { Button, Form, Modal, Nav } from 'react-bootstrap';
import { build_date } from '@/shared/helpers/general';
import { applyFilterParam, visitWithFilters } from '@/shared/lib/filters';

type PayoutRequestRow = {
  id: string;
  amount: number;
  status: { label: string; color: string; value: string };
  gateway_reference: string | null;
  transfer_proof_url: string | null;
  failure_reason: string | null;
  operation_type: string;
  recipient?: { name: string };
  maker_admin?: { id: number; name: string };
  submitted_by_admin?: { id: number; name: string };
  processed_by_admin?: { id: number; name: string };
  created_at: string;
};

type Props = {
  rows: PaginationResource<PayoutRequestRow>;
  prams: Record<string, unknown> | null;
};

type StatusFilter = '' | 'pending' | 'submitted' | 'processing' | 'failed' | 'completed';

type SearchPrams = {
  per_page?: number;
  search?: string;
  status?: StatusFilter;
};

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canSubmit = hasPermission('request payouts');
  const canReview = hasPermission('confirm payouts');
  const currentAdminId = usePage<{ auth: { user?: { id?: number } } }>().props.auth.user?.id;

  const activeStatus = (prams?.status as StatusFilter | undefined) ?? '';
  const searchPrams: SearchPrams = {
    per_page: (prams?.per_page as number | undefined) ?? 10,
    search: (prams?.search as string | undefined) ?? '',
    status: activeStatus,
  };

  const [submitTarget, setSubmitTarget] = useState<PayoutRequestRow | null>(null);
  const [approveTarget, setApproveTarget] = useState<PayoutRequestRow | null>(null);
  const [rejectTarget, setRejectTarget] = useState<PayoutRequestRow | null>(null);
  const [failTarget, setFailTarget] = useState<PayoutRequestRow | null>(null);
  const [proofTarget, setProofTarget] = useState<PayoutRequestRow | null>(null);

  const submitForm = useForm<{ gateway_reference: string; proof_image: File | null }>({
    gateway_reference: '',
    proof_image: null,
  });
  const approveForm = useForm({});
  const rejectForm = useForm({ failure_reason: '' });
  const failForm = useForm({ failure_reason: '' });

  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam({ ...searchPrams } as Record<string, unknown>, name, value);
    visitWithFilters(PayoutRequestController.index().url, next, { only: ['rows', 'prams'] });
  };

  const changeStatusFilter = (status: StatusFilter) => {
    const next = applyFilterParam(
      { ...searchPrams } as Record<string, unknown>,
      'status',
      status === '' ? null : status,
    );
    visitWithFilters(PayoutRequestController.index().url, next, { only: ['rows', 'prams'] });
  };

  const submitTransfer = () => {
    if (!submitTarget) {
      return;
    }

    submitForm.put(PayoutRequestController.submit(submitTarget).url, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        setSubmitTarget(null);
        submitForm.reset();
      },
    });
  };

  const submitApprove = () => {
    if (!approveTarget) {
      return;
    }

    approveForm.put(PayoutRequestController.confirm(approveTarget).url, {
      preserveScroll: true,
      onSuccess: () => {
        setApproveTarget(null);
        approveForm.reset();
      },
    });
  };

  const submitReject = () => {
    if (!rejectTarget) {
      return;
    }

    rejectForm.put(PayoutRequestController.reject(rejectTarget).url, {
      preserveScroll: true,
      onSuccess: () => {
        setRejectTarget(null);
        rejectForm.reset();
      },
    });
  };

  const submitFail = () => {
    if (!failTarget) {
      return;
    }

    failForm.put(PayoutRequestController.fail(failTarget).url, {
      preserveScroll: true,
      onSuccess: () => {
        setFailTarget(null);
        failForm.reset();
      },
    });
  };

  const isOwnSubmission = (row: PayoutRequestRow) =>
    currentAdminId !== undefined && row.submitted_by_admin?.id === currentAdminId;

  return (
    <>
      <Head title={t('payout_requests')} />
      <PageTitle
        breadcrumbs={[
          {
            title: '',
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}
      >
        {t('payout_requests')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <Nav variant="tabs" className="nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold mb-5">
          <Nav.Item>
            <Nav.Link
              active={activeStatus === ''}
              onClick={() => changeStatusFilter('')}
              role="button"
            >
              {t('payout_active_queue')}
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link
              active={activeStatus === 'pending'}
              onClick={() => changeStatusFilter('pending')}
              role="button"
            >
              {t('pending')}
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link
              active={activeStatus === 'submitted'}
              onClick={() => changeStatusFilter('submitted')}
              role="button"
            >
              {t('submitted')}
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link
              active={activeStatus === 'processing'}
              onClick={() => changeStatusFilter('processing')}
              role="button"
            >
              {t('processing')}
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link
              active={activeStatus === 'failed'}
              onClick={() => changeStatusFilter('failed')}
              role="button"
            >
              {t('failed')}
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link
              active={activeStatus === 'completed'}
              onClick={() => changeStatusFilter('completed')}
              role="button"
            >
              {t('completed')}
            </Nav.Link>
          </Nav.Item>
        </Nav>
        <KTCard>
          <Table<PayoutRequestRow>
            name="payout-requests"
            rows={rows}
            search={{
              value: (prams?.search as string | undefined) || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: t('recipient'),
                property: 'recipient',
                render: (row) => row.recipient?.name ?? '—',
              },
              {
                title: t('amount'),
                property: 'amount',
              },
              {
                title: t('status'),
                property: 'status',
                render: (row) => (
                  <span className={`badge badge-light-${row.status.color}`}>{row.status.label}</span>
                ),
              },
              {
                title: t('maker'),
                property: 'maker_admin',
                render: (row) => row.maker_admin?.name ?? '—',
              },
              {
                title: t('submitter'),
                property: 'submitted_by_admin',
                render: (row) => row.submitted_by_admin?.name ?? '—',
              },
              {
                title: t('created_at'),
                property: 'created_at',
                render: (row) => build_date(row.created_at),
              },
              ...(canSubmit || canReview
                ? [
                    {
                      title: t('actions'),
                      property: 'id' as const,
                      render: (row: PayoutRequestRow) => (
                        <div className="d-flex gap-2 flex-wrap">
                          {canSubmit && row.status.value === 'pending' && (
                            <Button
                              size="sm"
                              variant="primary"
                              onClick={() => {
                                setSubmitTarget(row);
                                submitForm.reset();
                              }}
                            >
                              {t('submit_transfer')}
                            </Button>
                          )}
                          {canSubmit && row.status.value === 'failed' && (
                            <Button
                              size="sm"
                              variant="primary"
                              onClick={() => {
                                setSubmitTarget(row);
                                submitForm.reset();
                              }}
                            >
                              {t('resubmit_transfer')}
                            </Button>
                          )}
                          {canReview && row.status.value === 'pending' && (
                            <Button
                              size="sm"
                              variant="danger"
                              onClick={() => {
                                setFailTarget(row);
                                failForm.reset();
                              }}
                            >
                              {t('mark_failed')}
                            </Button>
                          )}
                          {canReview && row.status.value === 'submitted' && !isOwnSubmission(row) && (
                            <>
                              <Button
                                size="sm"
                                variant="success"
                                onClick={() => setApproveTarget(row)}
                              >
                                {t('approve_transfer')}
                              </Button>
                              <Button
                                size="sm"
                                variant="danger"
                                onClick={() => {
                                  setRejectTarget(row);
                                  rejectForm.reset();
                                }}
                              >
                                {t('reject_transfer')}
                              </Button>
                            </>
                          )}
                          {(row.status.value === 'completed' || row.status.value === 'submitted') &&
                            row.transfer_proof_url && (
                              <Button
                                size="sm"
                                variant="light-primary"
                                onClick={() => setProofTarget(row)}
                              >
                                {t('view_proof')}
                              </Button>
                            )}
                        </div>
                      ),
                    },
                  ]
                : []),
            ]}
          />
        </KTCard>
      </Content>

      <Modal show={submitTarget !== null} onHide={() => setSubmitTarget(null)} centered>
        <Modal.Header closeButton>
          <Modal.Title>
            {submitTarget?.status.value === 'failed' ? t('resubmit_transfer') : t('submit_transfer')}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group className="mb-3">
            <Form.Label>{t('gateway_reference')}</Form.Label>
            <Form.Control
              value={submitForm.data.gateway_reference}
              onChange={(e) => submitForm.setData('gateway_reference', e.target.value)}
              isInvalid={!!submitForm.errors.gateway_reference}
            />
            <Form.Control.Feedback type="invalid">{submitForm.errors.gateway_reference}</Form.Control.Feedback>
          </Form.Group>
          <Form.Group>
            <Form.Label>{t('transfer_proof')}</Form.Label>
            <Form.Control
              type="file"
              accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
              onChange={(e) => {
                const file = e.currentTarget.files?.[0] ?? null;
                submitForm.setData('proof_image', file);
              }}
              isInvalid={!!submitForm.errors.proof_image}
            />
            <Form.Text className="text-muted">{t('transfer_proof_hint')}</Form.Text>
            <Form.Control.Feedback type="invalid">{submitForm.errors.proof_image}</Form.Control.Feedback>
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setSubmitTarget(null)}>
            {t('cancel')}
          </Button>
          <Button variant="primary" onClick={submitTransfer} disabled={submitForm.processing}>
            {submitTarget?.status.value === 'failed' ? t('resubmit_transfer') : t('submit_transfer')}
          </Button>
        </Modal.Footer>
      </Modal>

      <Modal show={approveTarget !== null} onHide={() => setApproveTarget(null)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{t('approve_transfer')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {approveTarget && (
            <>
              <p className="mb-3">
                <strong>{t('gateway_reference')}:</strong> {approveTarget.gateway_reference ?? '—'}
              </p>
              {approveTarget.transfer_proof_url ? (
                <img
                  src={approveTarget.transfer_proof_url}
                  alt={t('transfer_proof')}
                  className="img-fluid rounded border"
                />
              ) : (
                <p className="text-muted mb-0">{t('transfer_proof_unavailable')}</p>
              )}
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setApproveTarget(null)}>
            {t('cancel')}
          </Button>
          <Button variant="success" onClick={submitApprove} disabled={approveForm.processing}>
            {t('approve_transfer')}
          </Button>
        </Modal.Footer>
      </Modal>

      <Modal show={rejectTarget !== null} onHide={() => setRejectTarget(null)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{t('reject_transfer')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group>
            <Form.Label>{t('failure_reason')}</Form.Label>
            <Form.Control
              as="textarea"
              rows={3}
              value={rejectForm.data.failure_reason}
              onChange={(e) => rejectForm.setData('failure_reason', e.target.value)}
              isInvalid={!!rejectForm.errors.failure_reason}
            />
            <Form.Control.Feedback type="invalid">{rejectForm.errors.failure_reason}</Form.Control.Feedback>
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setRejectTarget(null)}>
            {t('cancel')}
          </Button>
          <Button variant="danger" onClick={submitReject} disabled={rejectForm.processing}>
            {t('reject_transfer')}
          </Button>
        </Modal.Footer>
      </Modal>

      <Modal show={failTarget !== null} onHide={() => setFailTarget(null)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{t('mark_failed')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group>
            <Form.Label>{t('failure_reason')}</Form.Label>
            <Form.Control
              as="textarea"
              rows={3}
              value={failForm.data.failure_reason}
              onChange={(e) => failForm.setData('failure_reason', e.target.value)}
              isInvalid={!!failForm.errors.failure_reason}
            />
            <Form.Control.Feedback type="invalid">{failForm.errors.failure_reason}</Form.Control.Feedback>
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setFailTarget(null)}>
            {t('cancel')}
          </Button>
          <Button variant="danger" onClick={submitFail} disabled={failForm.processing}>
            {t('mark_failed')}
          </Button>
        </Modal.Footer>
      </Modal>

      <Modal show={proofTarget !== null} onHide={() => setProofTarget(null)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title>{t('view_proof')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {proofTarget && (
            <>
              <p className="mb-3">
                <strong>{t('gateway_reference')}:</strong> {proofTarget.gateway_reference ?? '—'}
              </p>
              {proofTarget.transfer_proof_url ? (
                <img
                  src={proofTarget.transfer_proof_url}
                  alt={t('transfer_proof')}
                  className="img-fluid rounded border"
                />
              ) : (
                <p className="text-muted mb-0">{t('transfer_proof_unavailable')}</p>
              )}
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setProofTarget(null)}>
            {t('close')}
          </Button>
        </Modal.Footer>
      </Modal>
    </>
  );
};

Index.layout = (page: ReactElement) => <MasterLayout>{page}</MasterLayout>;

export default Index;
