import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import type { BackgroundUploadTrayStatus } from '@/shared/components/uploads/BackgroundUploadTray';

export type EagerUploadStatus = BackgroundUploadTrayStatus;

export type EagerUploadEntry<TField extends string, TMeta> = {
  field: TField;
  fileName: string;
  status: EagerUploadStatus;
  progress: number;
  error: string | null;
  selectedFile: File | null;
  meta: TMeta;
};

export type EagerUploadFnArgs<TField extends string> = {
  field: TField;
  file: File;
  signal: AbortSignal;
  onProgress: (percent: number) => void;
};

export type EagerUploadSuccess<TMeta> = {
  fileName?: string;
  meta: TMeta;
};

export type EagerUploadFn<TField extends string, TMeta> = (
  args: EagerUploadFnArgs<TField>,
) => Promise<EagerUploadSuccess<TMeta>>;

export type UseEagerFileUploadOptions<TField extends string, TMeta> = {
  /**
   * Optional pre-upload transform (e.g. image compression).
   * Throw or reject to mark the entry failed with `compression_failed`.
   */
  prepareFile?: (field: TField, file: File) => Promise<File>;
  /**
   * Optional early validation. Return an error code string to fail without uploading.
   */
  validateFile?: (field: TField, file: File) => string | null;
  maxFileBytes?: number;
  upload: EagerUploadFn<TField, TMeta>;
  /**
   * Called with the previous entry's meta when replacing a field (abort-on-replace).
   */
  deletePrevious?: (previousMeta: TMeta) => Promise<void>;
  /** Initial meta value for new / failed entries that have no server result yet. */
  emptyMeta: TMeta;
  /** Warn the user when closing the tab while uploads are in flight. */
  warnOnUnload?: boolean;
  /**
   * When true, keep `selectedFile` after a successful upload (registration retry).
   * When false, clear it on success (profile).
   */
  keepSelectedFileOnSuccess?: boolean;
  /**
   * When true, keep previous entry meta while a replace is in flight
   * (profile keeps the existing file URL until the new upload succeeds).
   * Registration clears uploadId until the new upload completes.
   */
  retainMetaWhileInFlight?: boolean;
};

export type UseEagerFileUploadResult<TField extends string, TMeta> = {
  entries: Partial<Record<TField, EagerUploadEntry<TField, TMeta>>>;
  hasInFlightUploads: boolean;
  hasFailedUploads: boolean;
  selectAndUpload: (field: TField, file: File) => Promise<void>;
  retryUpload: (field: TField) => Promise<void>;
  clearField: (field: TField) => Promise<void>;
  awaitInFlightUploads: () => Promise<{ failed: boolean; stillInFlight: boolean }>;
  resetAll: () => void;
};

function isCanceledError(error: unknown): boolean {
  if (!error || typeof error !== 'object') {
    return false;
  }

  if ('code' in error && (error as { code?: string }).code === 'ERR_CANCELED') {
    return true;
  }

  return error instanceof DOMException && error.name === 'AbortError';
}

/**
 * Shared eager-upload state machine: prepare → size check → upload with progress,
 * abort-on-replace, retry, and tray-compatible entry shape.
 *
 * Transport (HTTP endpoint, auth, temp-token vs authenticated attach) is injected
 * via `upload` / `deletePrevious` so registration and profile can share everything else.
 */
export function useEagerFileUpload<TField extends string, TMeta>(
  options: UseEagerFileUploadOptions<TField, TMeta>,
): UseEagerFileUploadResult<TField, TMeta> {
  const warnOnUnload = options.warnOnUnload ?? false;

  const [entries, setEntries] = useState<
    Partial<Record<TField, EagerUploadEntry<TField, TMeta>>>
  >({});
  const abortControllersRef = useRef<Partial<Record<TField, AbortController>>>({});
  const inFlightResolversRef = useRef<Map<TField, Array<() => void>>>(new Map());
  const entriesRef = useRef(entries);
  entriesRef.current = entries;

  const optionsRef = useRef(options);
  optionsRef.current = {
    ...options,
    warnOnUnload,
    keepSelectedFileOnSuccess: options.keepSelectedFileOnSuccess ?? false,
    retainMetaWhileInFlight: options.retainMetaWhileInFlight ?? false,
  };

  const notifySettled = useCallback((field: TField) => {
    const resolvers = inFlightResolversRef.current.get(field) ?? [];
    resolvers.forEach((resolve) => resolve());
    inFlightResolversRef.current.delete(field);
  }, []);

  const waitForField = useCallback((field: TField) => {
    return new Promise<void>((resolve) => {
      const current = entriesRef.current[field];
      if (
        !current ||
        (current.status !== 'compressing' && current.status !== 'uploading')
      ) {
        resolve();

        return;
      }

      const list = inFlightResolversRef.current.get(field) ?? [];
      list.push(resolve);
      inFlightResolversRef.current.set(field, list);
    });
  }, []);

  const abortField = useCallback((field: TField) => {
    const controller = abortControllersRef.current[field];
    if (controller) {
      controller.abort();
    }
    delete abortControllersRef.current[field];
  }, []);

  const runUpload = useCallback(
    async (field: TField, file: File) => {
      const opts = optionsRef.current;
      abortField(field);

      const previousMeta = entriesRef.current[field]?.meta;
      if (previousMeta !== undefined && opts.deletePrevious) {
        void opts.deletePrevious(previousMeta);
      }

      const validationError = opts.validateFile?.(field, file) ?? null;
      if (validationError) {
        setEntries((prev) => ({
          ...prev,
          [field]: {
            field,
            fileName: file.name,
            status: 'failed',
            progress: 0,
            error: validationError,
            selectedFile: file,
            meta: prev[field]?.meta ?? opts.emptyMeta,
          },
        }));
        notifySettled(field);

        return;
      }

      const metaForInFlight = (previous: typeof entriesRef.current): TMeta => {
        if (opts.retainMetaWhileInFlight) {
          return previous[field]?.meta ?? opts.emptyMeta;
        }

        return opts.emptyMeta;
      };

      setEntries((prev) => ({
        ...prev,
        [field]: {
          field,
          fileName: file.name,
          status: opts.prepareFile ? 'compressing' : 'uploading',
          progress: 0,
          error: null,
          selectedFile: file,
          meta: metaForInFlight(prev),
        },
      }));

      let prepared = file;

      if (opts.prepareFile) {
        try {
          prepared = await opts.prepareFile(field, file);
        } catch {
          setEntries((prev) => ({
            ...prev,
            [field]: {
              field,
              fileName: file.name,
              status: 'failed',
              progress: 0,
              error: 'compression_failed',
              selectedFile: file,
              meta: metaForInFlight(prev),
            },
          }));
          notifySettled(field);

          return;
        }
      }

      if (opts.maxFileBytes !== undefined && prepared.size > opts.maxFileBytes) {
        setEntries((prev) => ({
          ...prev,
          [field]: {
            field,
            fileName: file.name,
            status: 'failed',
            progress: 0,
            error: 'file_too_large',
            selectedFile: file,
            meta: metaForInFlight(prev),
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
          error: null,
          selectedFile: prepared,
          meta: metaForInFlight(prev),
        },
      }));

      try {
        const result = await opts.upload({
          field,
          file: prepared,
          signal: controller.signal,
          onProgress: (progress) => {
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
        });

        setEntries((prev) => ({
          ...prev,
          [field]: {
            field,
            fileName: result.fileName || file.name,
            status: 'done',
            progress: 100,
            error: null,
            selectedFile: opts.keepSelectedFileOnSuccess ? prepared : null,
            meta: result.meta,
          },
        }));
      } catch (error) {
        if (controller.signal.aborted || isCanceledError(error)) {
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
            error: message,
            selectedFile: prepared,
            meta: prev[field]?.meta ?? opts.emptyMeta,
          },
        }));
      } finally {
        delete abortControllersRef.current[field];
        notifySettled(field);
      }
    },
    [abortField, notifySettled],
  );

  const selectAndUpload = useCallback(
    async (field: TField, file: File) => {
      await runUpload(field, file);
    },
    [runUpload],
  );

  const retryUpload = useCallback(
    async (field: TField) => {
      const entry = entriesRef.current[field];
      if (!entry?.selectedFile) {
        return;
      }

      await runUpload(field, entry.selectedFile);
    },
    [runUpload],
  );

  const clearField = useCallback(
    async (field: TField) => {
      abortField(field);
      const meta = entriesRef.current[field]?.meta;
      if (meta !== undefined && optionsRef.current.deletePrevious) {
        await optionsRef.current.deletePrevious(meta);
      }
      setEntries((prev) => {
        const next = { ...prev };
        delete next[field];

        return next;
      });
      notifySettled(field);
    },
    [abortField, notifySettled],
  );

  const awaitInFlightUploads = useCallback(async () => {
    const fields = Object.keys(entriesRef.current) as TField[];
    await Promise.all(fields.map((field) => waitForField(field)));

    const settled = entriesRef.current;
    const failed = Object.values(settled).some(
      (entry) => (entry as EagerUploadEntry<TField, TMeta> | undefined)?.status === 'failed',
    );
    const stillInFlight = Object.values(settled).some((entry) => {
      const status = (entry as EagerUploadEntry<TField, TMeta> | undefined)?.status;

      return status === 'compressing' || status === 'uploading';
    });

    return { failed, stillInFlight };
  }, [waitForField]);

  const resetAll = useCallback(() => {
    (Object.keys(abortControllersRef.current) as TField[]).forEach(abortField);
    setEntries({});
  }, [abortField]);

  const hasInFlightUploads = useMemo(
    () =>
      Object.values(entries).some((entry) => {
        const status = (entry as EagerUploadEntry<TField, TMeta> | undefined)?.status;

        return status === 'compressing' || status === 'uploading';
      }),
    [entries],
  );

  const hasFailedUploads = useMemo(
    () =>
      Object.values(entries).some(
        (entry) =>
          (entry as EagerUploadEntry<TField, TMeta> | undefined)?.status === 'failed',
      ),
    [entries],
  );

  useEffect(() => {
    if (!warnOnUnload || !hasInFlightUploads) {
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
  }, [warnOnUnload, hasInFlightUploads]);

  return {
    entries,
    hasInFlightUploads,
    hasFailedUploads,
    selectAndUpload,
    retryUpload,
    clearField,
    awaitInFlightUploads,
    resetAll,
  };
}
