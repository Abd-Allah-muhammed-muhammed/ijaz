import { isDisputeResolutionHistory, type HistoryReason } from '@/apps/admin/pages/Orders/components/dispute-tab-utils';
import { OrderStatusEnum } from '@/Enums/Orders';
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
  image?: string;
};

export type HistoryItem = {
  id: string;
  from_status?: StatusOption | null;
  to_status: StatusOption;
  reason?: HistoryReason | null;
  notes?: string | null;
  actor?: Participant;
  created_at: string;
};

type Props = {
  statusHistories: HistoryItem[];
};

const DisputeTab = ({ statusHistories }: Props) => {
  const { t } = useTranslation();

  const opening = statusHistories.find((history) => history.to_status?.value === 'disputed');
  const resolution = statusHistories.find((history) => isDisputeResolutionHistory(history));
  const isEscalated = resolution?.to_status?.value === OrderStatusEnum.Escalated;

  if (!opening) {
    return <p className="text-muted fst-italic mb-0">{t('orders.no_dispute')}</p>;
  }

  return (
    <div className="d-flex flex-column" style={{ maxWidth: 720 }}>
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
              <h4 className="fw-bolder text-gray-900 mb-0">{t('orders.dispute_opened')}</h4>
              <span className="badge badge-light-danger rounded-pill px-3 py-2 fw-bold">
                {t('disputed')}
              </span>
            </div>
            <div className="d-flex align-items-center gap-3 mb-4">
              <div className="symbol symbol-35px symbol-circle">
                <span className="symbol-label bg-white text-warning fw-bold">
                  {opening.actor?.name?.charAt(0)?.toUpperCase() ?? '?'}
                </span>
              </div>
              <div>
                <div className="fw-bold text-gray-900 fs-6">
                  {opening.actor?.name ?? t('orders.system')}
                </div>
                <div className="text-muted fs-8">
                  {new Date(opening.created_at).toLocaleString()}
                </div>
              </div>
            </div>
            <div className="bg-white rounded-3 border border-warning border-opacity-25 p-4">
              <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                {t('orders.dispute_opened_reason')}
              </div>
              <p className="fs-6 text-gray-800 mb-0 fw-semibold">
                {opening.reason?.label ?? opening.reason?.value ?? t('orders.no_dispute_reason')}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="d-flex gap-4">
        <div className="d-flex flex-column align-items-center">
          <div className="symbol symbol-45px symbol-circle">
            <span
              className={`symbol-label ${
                resolution
                  ? isEscalated
                    ? 'bg-light-dark text-gray-700'
                    : 'bg-light-success text-success'
                  : 'bg-light-warning text-warning'
              }`}
            >
              <KTIcon
                iconName={resolution ? (isEscalated ? 'abstract-26' : 'check-circle') : 'timer'}
                className="fs-2"
              />
            </span>
          </div>
        </div>

        {resolution ? (
          <div
            className={`card border-0 shadow-sm rounded-4 flex-grow-1 ${
              isEscalated ? 'bg-light bg-opacity-75' : 'bg-light-success bg-opacity-10'
            }`}
          >
            <div className="card-body p-5">
              <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h4 className="fw-bolder text-gray-900 mb-0">{t('orders.dispute_resolved')}</h4>
                <span className="badge badge-light-success rounded-pill px-3 py-2 fw-bold">
                  {resolution.to_status?.label ?? t('orders.dispute_resolved')}
                </span>
              </div>
              <div className="bg-white rounded-3 border border-success border-opacity-25 p-4">
                <div className="text-muted fs-8 text-uppercase fw-bold mb-1">
                  {t('orders.dispute_outcome')}
                </div>
                <p className="fs-6 text-gray-800 mb-0 fw-semibold">
                  {resolution.reason?.label ?? ''}
                </p>
                {resolution.notes && (
                  <p className="fs-7 text-muted mt-2 mb-0">
                    <span className="fw-bold">{t('notes')}: </span>
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
                  {t('orders.awaiting_admin_resolution')}
                </div>
                <div className="text-muted fs-7 mt-1">
                  {t('orders.awaiting_admin_resolution_hint')}
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
