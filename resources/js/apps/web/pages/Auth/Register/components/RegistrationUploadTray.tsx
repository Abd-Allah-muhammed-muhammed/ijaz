import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
  useRegistrationUploads,
  type RegistrationUploadEntry,
} from '../hooks/registration-uploads-context';
import type { RegistrationUploadField } from '../registration-upload-constants';

/**
 * Collapsed-by-default fixed tray showing registration upload progress.
 * Visible on every wizard step while any upload entry exists.
 */
export default function RegistrationUploadTray() {
  const { t } = useTranslation();
  const { entries, retryUpload } = useRegistrationUploads();
  const [expanded, setExpanded] = useState(false);

  const list = useMemo(
    () => Object.values(entries).filter((entry): entry is RegistrationUploadEntry => Boolean(entry)),
    [entries],
  );

  if (list.length === 0) {
    return null;
  }

  const uploading = list.filter(
    (entry) => entry.status === 'compressing' || entry.status === 'uploading',
  ).length;
  const done = list.filter((entry) => entry.status === 'done').length;
  const failed = list.filter((entry) => entry.status === 'failed').length;

  const summaryParts: string[] = [];
  if (uploading > 0) {
    summaryParts.push(t('provider_registration.tray_uploading', { count: uploading }));
  }
  if (done > 0) {
    summaryParts.push(t('provider_registration.tray_done', { count: done }));
  }
  if (failed > 0) {
    summaryParts.push(t('provider_registration.tray_failed', { count: failed }));
  }

  return (
    <div
      className="position-fixed bottom-0 end-0 m-3 m-md-4"
      style={{ zIndex: 1055, maxWidth: 'min(360px, calc(100vw - 1.5rem))' }}
      data-pan="registration-upload-tray"
    >
      <div className="bg-white border border-gray-300 shadow-sm rounded">
        <button
          type="button"
          className="btn btn-sm btn-light-primary w-100 d-flex justify-content-between align-items-center px-3 py-2"
          onClick={() => setExpanded((value) => ! value)}
          aria-expanded={expanded}
        >
          <span className="fw-semibold text-start">{summaryParts.join(' · ') || t('files')}</span>
          <i className={`ki-duotone ki-${expanded ? 'down' : 'up'} fs-4`}>
            <span className="path1" />
            <span className="path2" />
          </i>
        </button>

        {expanded ? (
          <ul className="list-unstyled mb-0 px-3 py-2 border-top">
            {list.map((entry) => (
              <li
                key={entry.field}
                className="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom border-gray-100"
                data-pan={`registration-upload-tray-row-${entry.field}`}
              >
                <div className="min-w-0">
                  <div className="fw-semibold text-gray-800 text-truncate">{entry.fileName}</div>
                  <div className="text-muted fs-8">
                    {entry.status === 'compressing' && t('provider_registration.status_compressing')}
                    {entry.status === 'uploading' && `${t('provider_registration.status_uploading')} ${entry.progress}%`}
                    {entry.status === 'done' && t('provider_registration.status_done')}
                    {entry.status === 'failed' && t('provider_registration.status_failed')}
                  </div>
                </div>
                <div className="flex-shrink-0">
                  {entry.status === 'done' ? (
                    <span className="text-success fw-bold" aria-label="done">✓</span>
                  ) : null}
                  {entry.status === 'failed' ? (
                    <button
                      type="button"
                      className="btn btn-sm btn-light-danger"
                      onClick={() => {
                        void retryUpload(entry.field as RegistrationUploadField);
                      }}
                    >
                      {t('provider_registration.retry')}
                    </button>
                  ) : null}
                  {(entry.status === 'uploading' || entry.status === 'compressing') ? (
                    <span className="spinner-border spinner-border-sm text-primary" role="status" />
                  ) : null}
                </div>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
    </div>
  );
}
