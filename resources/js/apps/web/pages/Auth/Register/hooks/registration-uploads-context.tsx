import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import apiClient, { FORM_DATA_TIMEOUT_MS } from '@/shared/lib/api-client';
import type { SingleApiResponse } from '@/shared/types/api';
import ProviderRegistrationUploadController from '@/actions/App/Http/Controllers/Frontend/ProviderRegistrationUploadController';
import {
  compressUploadFile,
  useEagerFileUpload,
  type EagerUploadEntry,
  type EagerUploadStatus,
  UPLOAD_FIELD_LOGO,
  UPLOAD_MAX_FILE_SIZE_BYTES,
} from '@/shared/uploads';
import {
  type RegistrationUploadField,
} from '../registration-upload-constants';
import { readOrCreateRegistrationUploadToken } from '../registration-step-storage';

export type RegistrationUploadStatus = EagerUploadStatus;

export type RegistrationUploadEntry = {
  field: RegistrationUploadField;
  fileName: string;
  status: RegistrationUploadStatus;
  progress: number;
  uploadId: number | null;
  error: string | null;
  selectedFile: File | null;
};

type UploadApiPayload = {
  id: number;
  token: string;
  field: string;
  original_name: string;
  mime_type: string;
  size: number;
};

type RegistrationUploadMeta = {
  uploadId: number | null;
};

type RegistrationUploadsContextValue = {
  token: string;
  entries: Partial<Record<RegistrationUploadField, RegistrationUploadEntry>>;
  hasInFlightUploads: boolean;
  hasFailedUploads: boolean;
  selectAndUpload: (field: RegistrationUploadField, file: File) => Promise<void>;
  retryUpload: (field: RegistrationUploadField) => Promise<void>;
  clearField: (field: RegistrationUploadField) => Promise<void>;
  awaitInFlightUploads: () => Promise<{ failed: boolean; stillInFlight: boolean }>;
  getUploadIds: () => Partial<Record<RegistrationUploadField, number>>;
  resetAll: () => void;
};

const RegistrationUploadsContext = createContext<RegistrationUploadsContextValue | null>(null);

const EMPTY_META: RegistrationUploadMeta = { uploadId: null };

function toRegistrationEntry(
  entry: EagerUploadEntry<RegistrationUploadField, RegistrationUploadMeta>,
): RegistrationUploadEntry {
  return {
    field: entry.field,
    fileName: entry.fileName,
    status: entry.status,
    progress: entry.progress,
    uploadId: entry.meta.uploadId,
    error: entry.error,
    selectedFile: entry.selectedFile,
  };
}

export function RegistrationUploadsProvider({ children }: { children: ReactNode }) {
  const [token] = useState(() => readOrCreateRegistrationUploadToken());

  const deleteRemote = useCallback(
    async (meta: RegistrationUploadMeta) => {
      if (!meta.uploadId) {
        return;
      }

      try {
        await apiClient.delete(
          ProviderRegistrationUploadController.destroy.url({
            token,
            upload: meta.uploadId,
          }),
        );
      } catch {
        // Best-effort; 48h cleanup handles orphans.
      }
    },
    [token],
  );

  const upload = useCallback(
    async ({
      field,
      file,
      signal,
      onProgress,
    }: {
      field: RegistrationUploadField;
      file: File;
      signal: AbortSignal;
      onProgress: (percent: number) => void;
    }) => {
      const formData = new FormData();
      formData.append('field', field);
      formData.append('file', file);

      const response = await apiClient.post<SingleApiResponse<UploadApiPayload>>(
        ProviderRegistrationUploadController.store.url(token),
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
        fileName: payload.original_name,
        meta: { uploadId: payload.id } satisfies RegistrationUploadMeta,
      };
    },
    [token],
  );

  const eager = useEagerFileUpload<RegistrationUploadField, RegistrationUploadMeta>({
    prepareFile: (field, file) =>
      compressUploadFile(file, field === UPLOAD_FIELD_LOGO ? 'logo' : 'document'),
    maxFileBytes: UPLOAD_MAX_FILE_SIZE_BYTES,
    upload,
    deletePrevious: deleteRemote,
    emptyMeta: EMPTY_META,
    warnOnUnload: true,
    keepSelectedFileOnSuccess: true,
  });

  const entries = useMemo(() => {
    const mapped: Partial<Record<RegistrationUploadField, RegistrationUploadEntry>> = {};
    (Object.keys(eager.entries) as RegistrationUploadField[]).forEach((field) => {
      const entry = eager.entries[field];
      if (entry) {
        mapped[field] = toRegistrationEntry(entry);
      }
    });

    return mapped;
  }, [eager.entries]);

  const getUploadIds = useCallback(() => {
    const ids: Partial<Record<RegistrationUploadField, number>> = {};

    (Object.keys(entries) as RegistrationUploadField[]).forEach((field) => {
      const entry = entries[field];
      if (entry?.status === 'done' && entry.uploadId) {
        ids[field] = entry.uploadId;
      }
    });

    return ids;
  }, [entries]);

  const value = useMemo<RegistrationUploadsContextValue>(
    () => ({
      token,
      entries,
      hasInFlightUploads: eager.hasInFlightUploads,
      hasFailedUploads: eager.hasFailedUploads,
      selectAndUpload: eager.selectAndUpload,
      retryUpload: eager.retryUpload,
      clearField: eager.clearField,
      awaitInFlightUploads: eager.awaitInFlightUploads,
      getUploadIds,
      resetAll: eager.resetAll,
    }),
    [
      token,
      entries,
      eager.hasInFlightUploads,
      eager.hasFailedUploads,
      eager.selectAndUpload,
      eager.retryUpload,
      eager.clearField,
      eager.awaitInFlightUploads,
      getUploadIds,
      eager.resetAll,
    ],
  );

  return (
    <RegistrationUploadsContext.Provider value={value}>
      {children}
    </RegistrationUploadsContext.Provider>
  );
}

export function useRegistrationUploads(): RegistrationUploadsContextValue {
  const context = useContext(RegistrationUploadsContext);

  if (!context) {
    throw new Error('useRegistrationUploads must be used within RegistrationUploadsProvider');
  }

  return context;
}
