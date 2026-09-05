import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import apiClient, { FORM_DATA_TIMEOUT_MS } from '@/shared/lib/api-client';
import type { SingleApiResponse } from '@/shared/types/api';
import ProviderRegistrationUploadController from '@/actions/App/Http/Controllers/Frontend/ProviderRegistrationUploadController';
import { compressRegistrationFile } from '../compress-registration-image';
import {
  REGISTRATION_MAX_FILE_SIZE_BYTES,
  type RegistrationUploadField,
} from '../registration-upload-constants';
import { readOrCreateRegistrationUploadToken } from '../registration-step-storage';

export type RegistrationUploadStatus = 'idle' | 'compressing' | 'uploading' | 'done' | 'failed';

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

export function RegistrationUploadsProvider({ children }: { children: ReactNode }) {
  const [token] = useState(() => readOrCreateRegistrationUploadToken());
  const [entries, setEntries] = useState<Partial<Record<RegistrationUploadField, RegistrationUploadEntry>>>({});
  const abortControllersRef = useRef<Partial<Record<RegistrationUploadField, AbortController>>>({});
  const inFlightResolversRef = useRef<Map<RegistrationUploadField, Array<() => void>>>(new Map());
  const entriesRef = useRef(entries);
  entriesRef.current = entries;

  const notifySettled = useCallback((field: RegistrationUploadField) => {
    const resolvers = inFlightResolversRef.current.get(field) ?? [];
    resolvers.forEach((resolve) => resolve());
    inFlightResolversRef.current.delete(field);
  }, []);

  const waitForField = useCallback((field: RegistrationUploadField) => {
    return new Promise<void>((resolve) => {
      const current = entriesRef.current[field];
      if (! current || (current.status !== 'compressing' && current.status !== 'uploading')) {
        resolve();

        return;
      }

      const list = inFlightResolversRef.current.get(field) ?? [];
      list.push(resolve);
      inFlightResolversRef.current.set(field, list);
    });
  }, []);

  const abortField = useCallback((field: RegistrationUploadField) => {
    const controller = abortControllersRef.current[field];
    if (controller) {
      controller.abort();
      delete abortControllersRef.current[field];
    }
  }, []);

  const deleteRemote = useCallback(async (uploadId: number | null) => {
    if (! uploadId) {
      return;
    }

    try {
      await apiClient.delete(ProviderRegistrationUploadController.destroy.url({
        token,
        upload: uploadId,
      }));
    } catch {
      // Best-effort; 48h cleanup handles orphans.
    }
  }, [token]);

  const runUpload = useCallback(async (field: RegistrationUploadField, file: File) => {
    abortField(field);

    const previousId = entriesRef.current[field]?.uploadId ?? null;
    void deleteRemote(previousId);

    setEntries((prev) => ({
      ...prev,
      [field]: {
        field,
        fileName: file.name,
        status: 'compressing',
        progress: 0,
        uploadId: null,
        error: null,
        selectedFile: file,
      },
    }));

    let prepared = file;

    try {
      prepared = await compressRegistrationFile(file, field);
    } catch {
      setEntries((prev) => ({
        ...prev,
        [field]: {
          field,
          fileName: file.name,
          status: 'failed',
          progress: 0,
          uploadId: null,
          error: 'compression_failed',
          selectedFile: file,
        },
      }));
      notifySettled(field);

      return;
    }

    if (prepared.size > REGISTRATION_MAX_FILE_SIZE_BYTES) {
      setEntries((prev) => ({
        ...prev,
        [field]: {
          field,
          fileName: file.name,
          status: 'failed',
          progress: 0,
          uploadId: null,
          error: 'file_too_large',
          selectedFile: file,
        },
      }));
      notifySettled(field);

      return;
    }

    const controller = new AbortController();
    abortControllersRef.current[field] = controller;

    setEntries((prev) => ({
      ...prev,
      [field]: {
        field,
        fileName: file.name,
        status: 'uploading',
        progress: 0,
        uploadId: null,
        error: null,
        selectedFile: prepared,
      },
    }));

    const formData = new FormData();
    formData.append('field', field);
    formData.append('file', prepared);

    try {
      const response = await apiClient.post<SingleApiResponse<UploadApiPayload>>(
        ProviderRegistrationUploadController.store.url(token),
        formData,
        {
          signal: controller.signal,
          timeout: FORM_DATA_TIMEOUT_MS,
          onUploadProgress: (event) => {
            const total = event.total ?? prepared.size;
            const progress = total > 0 ? Math.min(100, Math.round((event.loaded / total) * 100)) : 0;
            setEntries((prev) => {
              const current = prev[field];
              if (! current || current.status !== 'uploading') {
                return prev;
              }

              return {
                ...prev,
                [field]: { ...current, progress },
              };
            });
          },
        },
      );

      const payload = response.data.data;

      setEntries((prev) => ({
        ...prev,
        [field]: {
          field,
          fileName: payload.original_name || file.name,
          status: 'done',
          progress: 100,
          uploadId: payload.id,
          error: null,
          selectedFile: prepared,
        },
      }));
    } catch (error) {
      if (controller.signal.aborted) {
        notifySettled(field);

        return;
      }

      const message = error instanceof Error ? error.message : 'upload_failed';

      setEntries((prev) => ({
        ...prev,
        [field]: {
          field,
          fileName: file.name,
          status: 'failed',
          progress: 0,
          uploadId: null,
          error: message,
          selectedFile: prepared,
        },
      }));
    } finally {
      delete abortControllersRef.current[field];
      notifySettled(field);
    }
  }, [abortField, deleteRemote, notifySettled, token]);

  const selectAndUpload = useCallback(async (field: RegistrationUploadField, file: File) => {
    await runUpload(field, file);
  }, [runUpload]);

  const retryUpload = useCallback(async (field: RegistrationUploadField) => {
    const entry = entriesRef.current[field];
    if (! entry?.selectedFile) {
      return;
    }

    await runUpload(field, entry.selectedFile);
  }, [runUpload]);

  const clearField = useCallback(async (field: RegistrationUploadField) => {
    abortField(field);
    const uploadId = entriesRef.current[field]?.uploadId ?? null;
    await deleteRemote(uploadId);
    setEntries((prev) => {
      const next = { ...prev };
      delete next[field];

      return next;
    });
    notifySettled(field);
  }, [abortField, deleteRemote, notifySettled]);

  const awaitInFlightUploads = useCallback(async () => {
    const fields = Object.keys(entriesRef.current) as RegistrationUploadField[];
    await Promise.all(fields.map((field) => waitForField(field)));

    const settled = entriesRef.current;
    const failed = Object.values(settled).some((entry) => entry?.status === 'failed');
    const stillInFlight = Object.values(settled).some(
      (entry) => entry?.status === 'compressing' || entry?.status === 'uploading',
    );

    return { failed, stillInFlight };
  }, [waitForField]);

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

  const resetAll = useCallback(() => {
    (Object.keys(abortControllersRef.current) as RegistrationUploadField[]).forEach(abortField);
    setEntries({});
  }, [abortField]);

  const hasInFlightUploads = useMemo(
    () => Object.values(entries).some(
      (entry) => entry?.status === 'compressing' || entry?.status === 'uploading',
    ),
    [entries],
  );

  const hasFailedUploads = useMemo(
    () => Object.values(entries).some((entry) => entry?.status === 'failed'),
    [entries],
  );

  useEffect(() => {
    if (! hasInFlightUploads) {
      return;
    }

    const onBeforeUnload = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = '';
    };

    window.addEventListener('beforeunload', onBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', onBeforeUnload);
    };
  }, [hasInFlightUploads]);

  const value = useMemo<RegistrationUploadsContextValue>(() => ({
    token,
    entries,
    hasInFlightUploads,
    hasFailedUploads,
    selectAndUpload,
    retryUpload,
    clearField,
    awaitInFlightUploads,
    getUploadIds,
    resetAll,
  }), [
    token,
    entries,
    hasInFlightUploads,
    hasFailedUploads,
    selectAndUpload,
    retryUpload,
    clearField,
    awaitInFlightUploads,
    getUploadIds,
    resetAll,
  ]);

  return (
    <RegistrationUploadsContext.Provider value={value}>
      {children}
    </RegistrationUploadsContext.Provider>
  );
}

export function useRegistrationUploads(): RegistrationUploadsContextValue {
  const context = useContext(RegistrationUploadsContext);

  if (! context) {
    throw new Error('useRegistrationUploads must be used within RegistrationUploadsProvider');
  }

  return context;
}
