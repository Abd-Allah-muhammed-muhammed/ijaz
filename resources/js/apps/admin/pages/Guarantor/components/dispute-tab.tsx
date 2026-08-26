import { KTIcon } from '@/vendor/metronic/helpers';
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
  image?: string;
};

export type HistoryItem = {
  id: string;
  from_status?: StatusOption | null;
  to_status: StatusOption;
  reason?: string | null;
  notes?: string | null;
  actor?: Participant;
  created_at: string;
};

type Props = {
  statusHistories: HistoryItem[];
};

const RESOLUTION_REASON_PREFIXES = [
  'dispute_resolved_full_requester',
  'dispute_resolved_full_counterparty',
  'dispute_escalated_to_court',
  'dispute_resolved_percentage_split',
] as const;

const CLOSED_BY_ADMIN_CANCEL_REASON = 'dispute_closed_by_admin_cancel';

const isResolutionReason = (reason?: string | null): boolean => {
  if (!reason) {
    return false;
  }

  if (reason === CLOSED_BY_ADMIN_CANCEL_REASON) {
    return true;
  }

  return RESOLUTION_REASON_PREFIXES.some(
    (prefix) => reason === prefix || reason.startsWith(`${prefix}:`),
  );
};

const formatResolutionOutcome = (
  reason: string,
  t: (key: string, options?: Record<string, unknown>) => string,
): string => {
  if (reason === 'dispute_resolved_full_requester') {
    return t('guarantor.dispute_outcome_full_requester');
  }

  if (reason === 'dispute_resolved_full_counterparty') {
    return t('guarantor.dispute_outcome_full_counterparty');
  }

  if (reason === 'dispute_escalated_to_court') {
    return t('guarantor.dispute_outcome_escalated');
  }

  if (reason.startsWith('dispute_resolved_percentage_split')) {
    const ratio = reason.includes(':') ? reason.split(':')[1] : null;
    if (ratio) {
      const [requester, counterparty] = ratio.split('/');
      return t('guarantor.dispute_outcome_percentage_split_detail', {
        requester,
        counterparty,
        defaultValue: `Percentage split — requester ${requester}%, counterparty ${counterparty}%`,
      });
    }

    return t('guarantor.dispute_outcome_percentage_split');
  }

  if (reason === CLOSED_BY_ADMIN_CANCEL_REASON) {
    return t('guarantor.dispute_outcome_admin_cancel');
  }

  return reason;
};

const DisputeTab = ({ statusHistories }: Props) => {
  const { t } = useTranslation();

  const opening = statusHistories.find((history) => history.to_status?.value === 'disputed');
  const resolution = statusHistories.find((history) => isResolutionReason(history.reason));
  const isEscalated = Boolean(resolution?.reason?.startsWith('dispute_escalated'));
  const isAdminCancelClosed = resolution?.reason === CLOSED_BY_ADMIN_CANCEL_REASON;

  if (!opening) {
    return <p className="text-muted fst-italic mb-0">{t('guarantor.no_dispute')}</p>;
  }

  return (
    <div className="d-flex flex-column" style={{ maxWidth: 720 }}>
      {/* Opening event */}
      <div className="d-flex gap-4">
        <div className="d-flex flex-column align-items-center">
          <div className="symbol symbol-45px symbol-circle">
            <span className="symbol-label bg-light-warning text-warning">
              <KTIcon iconName="information-5" className="fs-2" />
            </span>
          </div>
          <div className="flex-grow-1 w-2px bg-gray-200 my-2" style={{ minHeight: 28 }} />
        </div>
        <div className="card border-0 shadow-sm rounded-4 flex-grow-1 mb-4 bg-light-warning bg-opacity-10">
          <div className="card-body p-5">
            <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <h4 className="fw-bolder text-gray-900 mb-0">{t('guarantor.dispute_opened')}</h4>
              <span className="badge badge-light-danger rounded-pill px-3 py-2 fw-bold">
                {t('guarantor.status.disputed')}
              </span>
            </div>
            <div className="d-flex align-items-center gap-3 mb-4">
              <div className="symbol symbol-35px symbol-circle">
                {opening.actor?.image ? (
                  <img src={opening.actor.image} alt="" />
                ) : (
                  <span className="symbol-label bg-white text-warning fw-bold">
                    {opening.actor?.name?.charAt(0)?.toUpperCase() ?? '?'}
                  </span>
                )}
              </div>
              <div>
                <div className="fw-bold text-gray-900 fs-6">
                  {opening.actor?.name ?? t('guarantor.system')}
                </div>
                <div className="text-muted fs-8">
                  {new Date(opening.created_at).toLocaleString()}
                </div>
              </div>
            </div>
            <div className="bg-white rounded-3 border border-warning border-opacity-25 p-4">
              <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                {t('guarantor.dispute_opened_reason')}
              </div>
              <p className="fs-6 text-gray-800 mb-0 fw-semibold">
                {opening.reason || t('guarantor.no_dispute_reason')}
              </p>
              {opening.notes && (
                <p className="fs-7 text-muted mt-2 mb-0">
                  <span className="fw-bold">{t('guarantor.notes')}: </span>
                  {opening.notes}
                </p>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Resolution or awaiting */}
      <div className="d-flex gap-4">
        <div className="d-flex flex-column align-items-center">
          <div className="symbol symbol-45px symbol-circle">
            <span
              className={`symbol-label ${
                resolution
                  ? isEscalated
                    ? 'bg-light-dark text-gray-700'
                    : isAdminCancelClosed
                      ? 'bg-light-secondary text-gray-700'
                      : 'bg-light-success text-success'
                  : 'bg-light-warning text-warning'
              }`}
            >
              <KTIcon
                iconName={
                  resolution ? (isEscalated ? 'abstract-26' : isAdminCancelClosed ? 'cross-circle' : 'check-circle') : 'timer'
                }
                className="fs-2"
              />
            </span>
          </div>
        </div>

        {resolution ? (
          <div
            className={`card border-0 shadow-sm rounded-4 flex-grow-1 ${
              isEscalated
                ? 'bg-light bg-opacity-75'
                : isAdminCancelClosed
                  ? 'bg-light-secondary bg-opacity-10'
                  : 'bg-light-success bg-opacity-10'
            }`}
          >
            <div className="card-body p-5">
              <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h4 className="fw-bolder text-gray-900 mb-0">
                  {isAdminCancelClosed ? t('guarantor.dispute_closed') : t('guarantor.dispute_resolved')}
                </h4>
                <span
                  className={`badge rounded-pill px-3 py-2 fw-bold ${
                    isEscalated
                      ? 'badge-light-dark'
                      : isAdminCancelClosed
                        ? 'badge-light-secondary'
                        : 'badge-light-success'
                  }`}
                >
                  {resolution.to_status?.label ?? t('guarantor.dispute_resolved')}
                </span>
              </div>
              <div className="d-flex align-items-center gap-3 mb-4">
                <div className="symbol symbol-35px symbol-circle">
                  {resolution.actor?.image ? (
                    <img src={resolution.actor.image} alt="" />
                  ) : (
                    <span className="symbol-label bg-white text-success fw-bold">
                      {resolution.actor?.name?.charAt(0)?.toUpperCase() ?? '?'}
                    </span>
                  )}
                </div>
                <div>
                  <div className="fw-bold text-gray-900 fs-6">
                    {resolution.actor?.name ?? t('guarantor.system')}
                  </div>
                  <div className="text-muted fs-8">
                    {new Date(resolution.created_at).toLocaleString()}
                  </div>
                </div>
              </div>
              <div className="bg-white rounded-3 border border-success border-opacity-25 p-4">
                <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                  {t('guarantor.dispute_outcome')}
                </div>
                <p className="fs-6 text-gray-800 mb-0 fw-semibold">
                  {formatResolutionOutcome(resolution.reason ?? '', t)}
                </p>
                {resolution.notes && (
                  <p className="fs-7 text-muted mt-2 mb-0">
                    <span className="fw-bold">{t('guarantor.notes')}: </span>
                    {resolution.notes}
                  </p>
                )}
              </div>
            </div>
          </div>
        ) : (
          <div className="card border-0 shadow-sm rounded-4 flex-grow-1 bg-light-warning bg-opacity-25">
            <div className="card-body p-5 d-flex align-items-center gap-4">
              <div className="symbol symbol-50px">
                <span className="symbol-label bg-warning bg-opacity-15 text-warning rounded-3">
                  <KTIcon iconName="timer" className="fs-2x" />
                </span>
              </div>
              <div>
                <div className="fw-bolder text-gray-900 fs-5">
                  {t('guarantor.awaiting_admin_resolution')}
                </div>
                <div className="text-muted fs-7 mt-1">
                  {t('guarantor.awaiting_admin_resolution_hint')}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default DisputeTab;
