import { useMemo } from 'react';
import {
  useRegistrationUploads,
  type RegistrationUploadEntry,
} from '../hooks/registration-uploads-context';
import BackgroundUploadTray from '@/shared/components/uploads/BackgroundUploadTray';
import type { RegistrationUploadField } from '../registration-upload-constants';

/**
 * Collapsed-by-default fixed tray showing registration upload progress.
 * Visible on every wizard step while any upload entry exists.
 */
export default function RegistrationUploadTray() {
  const { entries, retryUpload } = useRegistrationUploads();

  const list = useMemo(
    () =>
      Object.values(entries)
        .filter((entry): entry is RegistrationUploadEntry => Boolean(entry))
        .map((entry) => ({
          id: entry.field,
          fileName: entry.fileName,
          status: entry.status,
          progress: entry.progress,
        })),
    [entries],
  );

  return (
    <BackgroundUploadTray
      entries={list}
      panPrefix="registration-upload-tray"
      onRetry={(id) => {
        void retryUpload(id as RegistrationUploadField);
      }}
    />
  );
}
