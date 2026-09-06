import { useCallback, useMemo } from 'react';
import { uploadProfileFile } from '@/actions/App/Http/Controllers/Provider/AuthController';
import apiClient, { FORM_DATA_TIMEOUT_MS } from '@/shared/lib/api-client';
import type { SingleApiResponse } from '@/shared/types/api';
import type { ProviderTypeFileKeys } from '@/shared/types/models';
import type { BackgroundUploadTrayStatus } from '@/shared/components/uploads/BackgroundUploadTray';
import {
  useEagerFileUpload,
  type EagerUploadEntry,
} from '@/shared/hooks/use-eager-file-upload';
import { isPdfFile } from '@/apps/web/pages/Auth/Register/compress-registration-image';
import {
  PROFILE_TYPE_FILE_ACCEPT,
  PROFILE_TYPE_FILE_MAX_BYTES,
} from '@/apps/provider/pages/Profile/constants';

export type ProfileFileUploadEntry = {
  field: ProviderTypeFileKeys;
  fileName: string;
  status: BackgroundUploadTrayStatus;
  progress: number;
  error: string | null;
  selectedFile: File | null;
  url: string | null;
};

type UploadApiPayload = {
  field: string;
  file_name: string;
  url: string;
  media_uuid: string;
};

type ProfileUploadMeta = {
  url: string | null;
};

export type UseProfileFileUploadsResult = {
  entries: Partial<Record<ProviderTypeFileKeys, ProfileFileUploadEntry>>;
  selectAndUpload: (field: ProviderTypeFileKeys, file: File) => Promise<void>;
  retryUpload: (field: ProviderTypeFileKeys) => Promise<void>;
  getFieldUrl: (field: ProviderTypeFileKeys, fallbackUrl: string | null | undefined) => string | null;
  isFieldUploaded: (field: ProviderTypeFileKeys, hasExisting: boolean) => boolean;
  isUploading: (field: ProviderTypeFileKeys) => boolean;
};

const EMPTY_META: ProfileUploadMeta = { url: null };

function toProfileEntry(
  entry: EagerUploadEntry<ProviderTypeFileKeys, ProfileUploadMeta>,
): ProfileFileUploadEntry {
  return {
    field: entry.field,
    fileName: entry.fileName,
    status: entry.status,
    progress: entry.progress,
    error: entry.error,
    selectedFile: entry.selectedFile,
    url: entry.meta.url,
  };
}

/**
 * Authenticated background uploads for provider profile required files.
 * Transport posts to AuthController.uploadProfileFile; state machine is shared.
 */
export function useProfileFileUploads(): UseProfileFileUploadsResult {
  const upload = useCallback(
    async ({
      field,
      file,
      signal,
      onProgress,
    }: {
      field: ProviderTypeFileKeys;
      file: File;
      signal: AbortSignal;
      onProgress: (percent: number) => void;
    }) => {
      const formData = new FormData();
      formData.append('field', field);
      formData.append('file', file);

      const response = await apiClient.post<SingleApiResponse<UploadApiPayload>>(
        uploadProfileFile.url(),
        formData,
        {
          signal,
          timeout: FORM_DATA_TIMEOUT_MS,
          onUploadProgress: (event) => {
            const total = event.total ?? file.size;
            const progress =
              total > 0 ? Math.min(100, Math.round((event.loaded / total) * 100)) : 0;
            onProgress(progress);
          },
        },
      );

      const payload = response.data.data;

      return {
        fileName: payload.file_name,
        meta: { url: payload.url } satisfies ProfileUploadMeta,
      };
    },
    [],
  );

  const eager = useEagerFileUpload<ProviderTypeFileKeys, ProfileUploadMeta>({
    validateFile: (_field, file) => (isPdfFile(file) ? null : 'invalid_type'),
    maxFileBytes: PROFILE_TYPE_FILE_MAX_BYTES,
    upload,
    emptyMeta: EMPTY_META,
    keepSelectedFileOnSuccess: false,
    retainMetaWhileInFlight: true,
  });

  const entries = useMemo(() => {
    const mapped: Partial<Record<ProviderTypeFileKeys, ProfileFileUploadEntry>> = {};
    (Object.keys(eager.entries) as ProviderTypeFileKeys[]).forEach((field) => {
      const entry = eager.entries[field];
      if (entry) {
        mapped[field] = toProfileEntry(entry);
      }
    });

    return mapped;
  }, [eager.entries]);

  const getFieldUrl = useCallback(
    (field: ProviderTypeFileKeys, fallbackUrl: string | null | undefined): string | null => {
      return entries[field]?.url ?? fallbackUrl ?? null;
    },
    [entries],
  );

  const isFieldUploaded = useCallback(
    (field: ProviderTypeFileKeys, hasExisting: boolean): boolean => {
      if (entries[field]?.status === 'done') {
        return true;
      }
      if (entries[field]?.status === 'uploading' || entries[field]?.status === 'failed') {
        return false;
      }

      return hasExisting;
    },
    [entries],
  );

  const isUploading = useCallback(
    (field: ProviderTypeFileKeys): boolean => {
      return entries[field]?.status === 'uploading';
    },
    [entries],
  );

  return {
    entries,
    selectAndUpload: eager.selectAndUpload,
    retryUpload: eager.retryUpload,
    getFieldUrl,
    isFieldUploaded,
    isUploading,
  };
}

export const PROFILE_FILE_INPUT_ACCEPT = PROFILE_TYPE_FILE_ACCEPT;
