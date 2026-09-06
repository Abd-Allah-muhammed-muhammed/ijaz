import { useCallback, useRef, useState } from 'react';
import { uploadProfileFile } from '@/actions/App/Http/Controllers/Provider/AuthController';
import apiClient, { FORM_DATA_TIMEOUT_MS } from '@/shared/lib/api-client';
import type { SingleApiResponse } from '@/shared/types/api';
import type { ProviderTypeFileKeys } from '@/shared/types/models';
import type { BackgroundUploadTrayStatus } from '@/shared/components/uploads/BackgroundUploadTray';
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

export type UseProfileFileUploadsResult = {
  entries: Partial<Record<ProviderTypeFileKeys, ProfileFileUploadEntry>>;
  selectAndUpload: (field: ProviderTypeFileKeys, file: File) => Promise<void>;
  retryUpload: (field: ProviderTypeFileKeys) => Promise<void>;
  getFieldUrl: (field: ProviderTypeFileKeys, fallbackUrl: string | null | undefined) => string | null;
  isFieldUploaded: (field: ProviderTypeFileKeys, hasExisting: boolean) => boolean;
  isUploading: (field: ProviderTypeFileKeys) => boolean;
};

function isPdf(file: File): boolean {
  return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

/**
 * Authenticated background uploads for provider profile required files.
 * Posts one file per field to AuthController.uploadProfileFile (no temp token).
 */
export function useProfileFileUploads(): UseProfileFileUploadsResult {
  const [entries, setEntries] = useState<
    Partial<Record<ProviderTypeFileKeys, ProfileFileUploadEntry>>
  >({});
  const abortControllersRef = useRef<
    Partial<Record<ProviderTypeFileKeys, AbortController>>
  >({});
  const entriesRef = useRef(entries);
  entriesRef.current = entries;

  const abortField = useCallback((field: ProviderTypeFileKeys) => {
    const controller = abortControllersRef.current[field];
    if (controller) {
      controller.abort();
    }
    delete abortControllersRef.current[field];
  }, []);

  const runUpload = useCallback(
    async (field: ProviderTypeFileKeys, file: File) => {
      abortField(field);

      if (!isPdf(file)) {
        setEntries((prev) => ({
          ...prev,
          [field]: {
            field,
            fileName: file.name,
            status: 'failed',
            progress: 0,
            error: 'invalid_type',
            selectedFile: file,
            url: prev[field]?.url ?? null,
          },
        }));

        return;
      }

      if (file.size > PROFILE_TYPE_FILE_MAX_BYTES) {
        setEntries((prev) => ({
          ...prev,
          [field]: {
            field,
            fileName: file.name,
            status: 'failed',
            progress: 0,
            error: 'file_too_large',
            selectedFile: file,
            url: prev[field]?.url ?? null,
          },
        }));

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
          error: null,
          selectedFile: file,
          url: prev[field]?.url ?? null,
        },
      }));

      const formData = new FormData();
      formData.append('field', field);
      formData.append('file', file);

      try {
        const response = await apiClient.post<SingleApiResponse<UploadApiPayload>>(
          uploadProfileFile.url(),
          formData,
          {
            signal: controller.signal,
            timeout: FORM_DATA_TIMEOUT_MS,
            onUploadProgress: (event) => {
              const total = event.total ?? file.size;
              const progress =
                total > 0 ? Math.min(100, Math.round((event.loaded / total) * 100)) : 0;
              setEntries((prev) => {
                const current = prev[field];
                if (!current || current.status !== 'uploading') {
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
            fileName: payload.file_name || file.name,
            status: 'done',
            progress: 100,
            error: null,
            selectedFile: null,
            url: payload.url,
          },
        }));
      } catch (error) {
        if (
          error &&
          typeof error === 'object' &&
          'code' in error &&
          (error as { code?: string }).code === 'ERR_CANCELED'
        ) {
          return;
        }

        setEntries((prev) => ({
          ...prev,
          [field]: {
            field,
            fileName: file.name,
            status: 'failed',
            progress: 0,
            error: 'upload_failed',
            selectedFile: file,
            url: prev[field]?.url ?? null,
          },
        }));
      } finally {
        delete abortControllersRef.current[field];
      }
    },
    [abortField],
  );

  const selectAndUpload = useCallback(
    async (field: ProviderTypeFileKeys, file: File) => {
      await runUpload(field, file);
    },
    [runUpload],
  );

  const retryUpload = useCallback(
    async (field: ProviderTypeFileKeys) => {
      const selected = entriesRef.current[field]?.selectedFile;
      if (!selected) {
        return;
      }
      await runUpload(field, selected);
    },
    [runUpload],
  );

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
    selectAndUpload,
    retryUpload,
    getFieldUrl,
    isFieldUploaded,
    isUploading,
  };
}

export const PROFILE_FILE_INPUT_ACCEPT = PROFILE_TYPE_FILE_ACCEPT;
