import { useTranslation } from 'react-i18next';
import MasterLayout from '@/vendor/metronic/layout/MasterLayout';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { Head, useForm } from '@inertiajs/react';
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
  processed_by_admin?: { id: number; name: string };
  created_at: string;
};

type Props = {
  rows: PaginationResource<PayoutRequestRow>;
  prams: Record<string, unknown> | null;
};

type StatusFilter = '' | 'pending' | 'failed' | 'completed';

const Index = ({ rows, prams }: Props) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
  const canConfirm = hasPermission('confirm payouts');

  const activeStatus = (prams?.status as StatusFilter | undefined) ?? '';

  const [confirmTarget, setConfirmTarget] = useState<PayoutRequestRow | null>(null);
  const [failTarget, setFailTarget] = useState<PayoutRequestRow | null>(null);
  const [proofTarget, setProofTarget] = useState<PayoutRequestRow | null>(null);

  const confirmForm = useForm<{ gateway_reference: string; proof_image: File | null }>({
    gateway_reference: '',
    proof_image: null,
  });
  const failForm = useForm({ failure_reason: '' });

  const changeStatusFilter = (status: StatusFilter) => {
    const next = applyFilterParam(
      { ...(prams ?? {}) } as Record<string, unknown>,
      'status',
      status === '' ? null : status,
    );
    visitWithFilters(PayoutRequestController.index().url, next, { only: ['rows', 'prams'] });
  };

  const submitConfirm = () => {
    if (!confirmTarget) {
      return;
    }

    confirmForm.put(PayoutRequestController.confirm(confirmTarget).url, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        setConfirmTarget(null);
        confirmForm.reset();
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

  const isActionableRow = (row: PayoutRequestRow) =>
    row.status.value === 'pending' || row.status.value === 'failed';

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
                title: t('created_at'),
                property: 'created_at',
                render: (row) => build_date(row.created_at),
              },
              ...(canConfirm
                ? [
                    {
                      title: t('actions'),
                      property: 'id' as const,
                      render: (row: PayoutRequestRow) => (
                        <div className="d-flex gap-2 flex-wrap">
                          {isActionableRow(row) && (
                            <>
                              <Button
                                size="sm"
                                variant="success"
                                onClick={() => {
                                  setConfirmTarget(row);
                                  confirmForm.reset();
                                }}
                              >
                                {t('confirm_transfer')}
                              </Button>
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
                            </>
                          )}
                          {row.status.value === 'completed' && row.transfer_proof_url && (
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

      <Modal show={confirmTarget !== null} onHide={() => setConfirmTarget(null)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{t('confirm_transfer')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group className="mb-3">
            <Form.Label>{t('gateway_reference')}</Form.Label>
            <Form.Control
              value={confirmForm.data.gateway_reference}
              onChange={(e) => confirmForm.setData('gateway_reference', e.target.value)}
              isInvalid={!!confirmForm.errors.gateway_reference}
            />
            <Form.Control.Feedback type="invalid">{confirmForm.errors.gateway_reference}</Form.Control.Feedback>
          </Form.Group>
          <Form.Group>
            <Form.Label>{t('transfer_proof')}</Form.Label>
            <Form.Control
              type="file"
              accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
              onChange={(e) => {
                const file = e.currentTarget.files?.[0] ?? null;
                confirmForm.setData('proof_image', file);
              }}
              isInvalid={!!confirmForm.errors.proof_image}
            />
            <Form.Text className="text-muted">{t('transfer_proof_hint')}</Form.Text>
            <Form.Control.Feedback type="invalid">{confirmForm.errors.proof_image}</Form.Control.Feedback>
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setConfirmTarget(null)}>
            {t('cancel')}
          </Button>
          <Button variant="success" onClick={submitConfirm} disabled={confirmForm.processing}>
            {t('confirm_transfer')}
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
