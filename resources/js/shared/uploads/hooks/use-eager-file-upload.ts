import { useWarnOnUnload } from '@/shared/uploads/hooks/use-warn-on-unload';
import { buildDoneEntry, buildFailedEntry, buildInFlightEntry } from '@/shared/uploads/lib/build-eager-upload-entry';
import { isFailedStatus, isInFlightStatus, isUploadingStatus } from '@/shared/uploads/lib/eager-upload-status';
import { isCanceledError } from '@/shared/uploads/lib/is-canceled-error';
import type { EagerUploadEntry, UseEagerFileUploadOptions, UseEagerFileUploadResult } from '@/shared/uploads/types';
import { useCallback, useMemo, useRef, useState } from 'react';

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

  const [entries, setEntries] = useState<Partial<Record<TField, EagerUploadEntry<TField, TMeta>>>>({});
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
      if (!current || !isInFlightStatus(current.status)) {
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
          [field]: buildFailedEntry({
            field,
            fileName: file.name,
            error: validationError,
            selectedFile: file,
            meta: prev[field]?.meta ?? opts.emptyMeta,
          }),
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
        [field]: buildInFlightEntry({
          field,
          fileName: file.name,
          status: opts.prepareFile ? 'compressing' : 'uploading',
          selectedFile: file,
          meta: metaForInFlight(prev),
        }),
      }));

      let prepared = file;

      if (opts.prepareFile) {
        try {
          prepared = await opts.prepareFile(field, file);
        } catch {
          setEntries((prev) => ({
            ...prev,
            [field]: buildFailedEntry({
              field,
              fileName: file.name,
              error: 'compression_failed',
              selectedFile: file,
              meta: metaForInFlight(prev),
            }),
          }));
          notifySettled(field);

          return;
        }
      }

      if (opts.maxFileBytes !== undefined && prepared.size > opts.maxFileBytes) {
        setEntries((prev) => ({
          ...prev,
          [field]: buildFailedEntry({
            field,
            fileName: file.name,
            error: 'file_too_large',
            selectedFile: file,
            meta: metaForInFlight(prev),
          }),
        }));
        notifySettled(field);

        return;
      }

      const controller = new AbortController();
      abortControllersRef.current[field] = controller;

      setEntries((prev) => ({
        ...prev,
        [field]: buildInFlightEntry({
          field,
          fileName: file.name,
          status: 'uploading',
          selectedFile: prepared,
          meta: metaForInFlight(prev),
        }),
      }));

      try {
        const result = await opts.upload({
          field,
          file: prepared,
          signal: controller.signal,
          onProgress: (progress) => {
            setEntries((prev) => {
              const current = prev[field];
              if (!current || !isUploadingStatus(current.status)) {
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
          [field]: buildDoneEntry({
            field,
            fileName: result.fileName || file.name,
            selectedFile: opts.keepSelectedFileOnSuccess ? prepared : null,
            meta: result.meta,
          }),
        }));
      } catch (error) {
        if (controller.signal.aborted || isCanceledError(error)) {
          notifySettled(field);

          return;
        }

        const message = error instanceof Error ? error.message : 'upload_failed';

        setEntries((prev) => ({
          ...prev,
          [field]: buildFailedEntry({
            field,
            fileName: file.name,
            error: message,
            selectedFile: prepared,
            meta: prev[field]?.meta ?? opts.emptyMeta,
          }),
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
    const failed = Object.values(settled).some((entry) => isFailedStatus((entry as EagerUploadEntry<TField, TMeta> | undefined)?.status));
    const stillInFlight = Object.values(settled).some((entry) => isInFlightStatus((entry as EagerUploadEntry<TField, TMeta> | undefined)?.status));

    return { failed, stillInFlight };
  }, [waitForField]);

  const resetAll = useCallback(() => {
    (Object.keys(abortControllersRef.current) as TField[]).forEach(abortField);
    setEntries({});
  }, [abortField]);

  const hasInFlightUploads = useMemo(
    () => Object.values(entries).some((entry) => isInFlightStatus((entry as EagerUploadEntry<TField, TMeta> | undefined)?.status)),
    [entries],
  );

  const hasFailedUploads = useMemo(
    () => Object.values(entries).some((entry) => isFailedStatus((entry as EagerUploadEntry<TField, TMeta> | undefined)?.status)),
    [entries],
  );

  useWarnOnUnload(warnOnUnload && hasInFlightUploads);

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
