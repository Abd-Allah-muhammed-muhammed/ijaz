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

const isResolutionReason = (reason?: string | null): boolean => {
  if (!reason) {
    return false;
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

  return reason;
};

const DisputeTab = ({ statusHistories }: Props) => {
  const { t } = useTranslation();

  const opening = statusHistories.find((history) => history.to_status?.value === 'disputed');
  const resolution = statusHistories.find((history) => isResolutionReason(history.reason));

  if (!opening) {
    return <p className="text-muted fst-italic mb-0">{t('guarantor.no_dispute')}</p>;
  }

  return (
    <div className="d-flex flex-column gap-6">
      <div className="card card-bordered shadow-sm">
        <div className="card-body">
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h3 className="fw-bolder mb-0">{t('guarantor.dispute_opened')}</h3>
            <span className="badge badge-light-danger">{t('guarantor.status.disputed')}</span>
          </div>
          <div className="d-flex align-items-center gap-3 mb-3">
            <div className="symbol symbol-40px">
              {opening.actor?.image ? (
                <img src={opening.actor.image} className="rounded-circle" alt="" />
              ) : (
                <div className="symbol-label bg-light-danger text-danger fw-bold">
                  {opening.actor?.name?.charAt(0)?.toUpperCase() ?? '?'}
                </div>
              )}
            </div>
            <div>
              <div className="fw-bold text-gray-900">
                {opening.actor?.name ?? t('guarantor.system')}
              </div>
              <div className="text-muted fs-7">
                {new Date(opening.created_at).toLocaleString()}
              </div>
            </div>
          </div>
          <div className="bg-light-danger rounded p-4">
            <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
              {t('guarantor.dispute_opened_reason')}
            </div>
            <p className="fs-6 text-gray-800 mb-0">
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

      {resolution ? (
        <div className="card card-bordered shadow-sm">
          <div className="card-body">
            <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <h3 className="fw-bolder mb-0">{t('guarantor.dispute_resolved')}</h3>
              <span className="badge badge-light-success">
                {resolution.to_status?.label ?? t('guarantor.dispute_resolved')}
              </span>
            </div>
            <div className="d-flex align-items-center gap-3 mb-3">
              <div className="symbol symbol-40px">
                {resolution.actor?.image ? (
                  <img src={resolution.actor.image} className="rounded-circle" alt="" />
                ) : (
                  <div className="symbol-label bg-light-success text-success fw-bold">
                    {resolution.actor?.name?.charAt(0)?.toUpperCase() ?? '?'}
                  </div>
                )}
              </div>
              <div>
                <div className="fw-bold text-gray-900">
                  {resolution.actor?.name ?? t('guarantor.system')}
                </div>
                <div className="text-muted fs-7">
                  {new Date(resolution.created_at).toLocaleString()}
                </div>
              </div>
            </div>
            <div className="bg-light-success rounded p-4">
              <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                {t('guarantor.dispute_outcome')}
              </div>
              <p className="fs-6 text-gray-800 mb-0">
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
        <div className="alert alert-warning d-flex align-items-center mb-0">
          <i className="bi bi-hourglass-split fs-2 me-3" />
          <div>
            <div className="fw-bold">{t('guarantor.awaiting_admin_resolution')}</div>
            <div className="fs-7">{t('guarantor.awaiting_admin_resolution_hint')}</div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DisputeTab;
