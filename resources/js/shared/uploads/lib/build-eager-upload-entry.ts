import type { EagerUploadEntry, EagerUploadStatus } from '@/shared/uploads/types';

type EagerUploadEntryBase<TField extends string, TMeta> = {
  field: TField;
  fileName: string;
  selectedFile: File | null;
  meta: TMeta;
};

export function buildFailedEntry<TField extends string, TMeta>(
  args: EagerUploadEntryBase<TField, TMeta> & { error: string },
): EagerUploadEntry<TField, TMeta> {
  return {
    field: args.field,
    fileName: args.fileName,
    status: 'failed',
    progress: 0,
    error: args.error,
    selectedFile: args.selectedFile,
    meta: args.meta,
  };
}

export function buildInFlightEntry<TField extends string, TMeta>(
  args: EagerUploadEntryBase<TField, TMeta> & {
    status: Extract<EagerUploadStatus, 'compressing' | 'uploading'>;
  },
): EagerUploadEntry<TField, TMeta> {
  return {
    field: args.field,
    fileName: args.fileName,
    status: args.status,
    progress: 0,
    error: null,
    selectedFile: args.selectedFile,
    meta: args.meta,
  };
}

export function buildDoneEntry<TField extends string, TMeta>(args: EagerUploadEntryBase<TField, TMeta>): EagerUploadEntry<TField, TMeta> {
  return {
    field: args.field,
    fileName: args.fileName,
    status: 'done',
    progress: 100,
    error: null,
    selectedFile: args.selectedFile,
    meta: args.meta,
  };
}
