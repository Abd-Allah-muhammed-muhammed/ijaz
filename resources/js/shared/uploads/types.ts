import type { ImageCompressionProfile } from '@/shared/uploads/constants';

export type UploadStatus = 'idle' | 'compressing' | 'uploading' | 'done' | 'failed';

export type EagerUploadStatus = UploadStatus;

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

export type EagerUploadFn<TField extends string, TMeta> = (args: EagerUploadFnArgs<TField>) => Promise<EagerUploadSuccess<TMeta>>;

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

export type BackgroundUploadTrayEntry = {
  id: string;
  fileName: string;
  status: UploadStatus;
  progress: number;
};

export type LocalImagePrepareErrorCode = 'too_large' | 'invalid_type' | 'compression_failed';

export type LocalImagePrepareResult = {
  file: File | null;
  errorCode: LocalImagePrepareErrorCode | null;
  originalSize: number;
  compressedSize: number | null;
};

export type LocalImagePrepareOptions = {
  allowedMimeTypes: readonly string[];
  maxBytes: number;
  compressionProfile: ImageCompressionProfile;
};
