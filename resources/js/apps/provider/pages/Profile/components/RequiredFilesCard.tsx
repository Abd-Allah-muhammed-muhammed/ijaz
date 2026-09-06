import { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import InputError from '@/shared/components/inputs/InputError';
import {
  SectionCard,
  SECONDARY_BUTTON_CLASS,
} from '@/shared/components/ui';
import BackgroundUploadTray from '@/shared/uploads/components/BackgroundUploadTray';
import {
  PROFILE_CARD_CLASS,
  PROFILE_FIELD_LABEL_REQUIRED_CLASS,
} from '@/apps/provider/pages/Profile/constants';
import {
  PROFILE_FILE_INPUT_ACCEPT,
  type UseProfileFileUploadsResult,
} from '@/apps/provider/pages/Profile/hooks/use-profile-file-uploads';
import type { ProfileRequiredFiles } from '@/apps/provider/pages/Profile/types';
import type { Provider, ProviderTypeFileKeys } from '@/shared/types/models';

export type RequiredFilesCardProps = {
  requiredFiles: ProfileRequiredFiles;
  provider: Provider;
  uploads: UseProfileFileUploadsResult;
  fieldErrors?: Partial<Record<ProviderTypeFileKeys, string>>;
};

export default function RequiredFilesCard({
  requiredFiles,
  provider,
  uploads,
  fieldErrors = {},
}: RequiredFilesCardProps) {
  const { t } = useTranslation();
  const T = t as (key: string, options?: Record<string, string>) => string;
  const inputRefs = useRef<Partial<Record<ProviderTypeFileKeys, HTMLInputElement | null>>>(
    {},
  );

  const visibleKeys = (
    Object.entries(requiredFiles) as [ProviderTypeFileKeys, boolean][]
  )
    .filter(([, enabled]) => enabled)
    .map(([key]) => key);

  const trayEntries = visibleKeys
    .map((key) => uploads.entries[key])
    .filter((entry): entry is NonNullable<typeof entry> => Boolean(entry))
    .map((entry) => ({
      id: entry.field,
      fileName: entry.fileName,
      status: entry.status,
      progress: entry.progress,
    }));

  return (
    <>
      <SectionCard title={T('required files')} className={PROFILE_CARD_CLASS}>
        {visibleKeys.length === 0 ? (
          <p className="text-muted fs-7 mb-0">{T('no_required_files_for_type')}</p>
        ) : (
          <ul className="list-unstyled mb-0" data-pan="profile-required-files">
            {visibleKeys.map((key) => {
              const existing = provider.media?.find(
                (media) => media.collection_name === key,
              );
              const hasExisting = Boolean(existing?.url);
              const url = uploads.getFieldUrl(key, existing?.url);
              const uploaded = uploads.isFieldUploaded(key, hasExisting);
              const uploading = uploads.isUploading(key);
              const entry = uploads.entries[key];
              const failed = entry?.status === 'failed';

              return (
                <li
                  key={`file-${key}`}
                  className="d-flex align-items-center justify-content-between gap-3 py-3 border-bottom border-gray-100"
                  data-pan={`profile-required-file-${key}`}
                >
                  <div className="min-w-0">
                    <div className={`${PROFILE_FIELD_LABEL_REQUIRED_CLASS} mb-1`}>
                      {T(key)}
                    </div>
                    {uploaded && !uploading ? (
                      <div className="d-flex align-items-center gap-2 flex-wrap">
                        <span className="text-success fw-semibold fs-7">
                          ✓ {T('uploads.status_done')}
                        </span>
                        {url ? (
                          <a
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="d-inline-flex align-items-center gap-1 fs-7"
                          >
                            <KTIcon iconName="document" className="fs-3" />
                            {T('download existing file')}
                          </a>
                        ) : null}
                      </div>
                    ) : null}
                    {uploading ? (
                      <span className="text-muted fs-7">
                        {T('uploads.status_uploading')}{' '}
                        {entry?.progress ?? 0}%
                      </span>
                    ) : null}
                    {failed ? (
                      <span className="text-danger fs-7">
                        {T('uploads.status_failed')}
                      </span>
                    ) : null}
                    {!uploaded && !uploading && !failed ? (
                      <span className="text-muted fs-7">{T('no_file_uploaded')}</span>
                    ) : null}
                    <InputError message={fieldErrors[key]} />
                  </div>

                  <div className="flex-shrink-0 d-flex gap-2">
                    <button
                      type="button"
                      className={SECONDARY_BUTTON_CLASS}
                      disabled={uploading}
                      onClick={() => inputRefs.current[key]?.click()}
                      aria-label={
                        uploaded ? T('replace_file') : T('upload')
                      }
                    >
                      {uploaded ? T('replace_file') : T('upload')}
                    </button>
                    <input
                      ref={(node) => {
                        inputRefs.current[key] = node;
                      }}
                      type="file"
                      className="d-none"
                      accept={PROFILE_FILE_INPUT_ACCEPT}
                      onChange={(event) => {
                        const file = event.currentTarget.files?.[0];
                        event.currentTarget.value = '';
                        if (!file) {
                          return;
                        }
                        void uploads.selectAndUpload(key, file);
                      }}
                    />
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </SectionCard>

      <BackgroundUploadTray
        entries={trayEntries}
        panPrefix="profile-upload-tray"
        onRetry={(id) => {
          void uploads.retryUpload(id as ProviderTypeFileKeys);
        }}
      />
    </>
  );
}
