import type { ImageCompressionProfile } from '@/shared/uploads/constants';

export type UploadStatus =
  | 'idle'
  | 'compressing'
  | 'uploading'
  | 'done'
  | 'failed';

export type BackgroundUploadTrayEntry = {
  id: string;
  fileName: string;
  status: UploadStatus;
  progress: number;
};

export type LocalImagePrepareErrorCode =
  | 'too_large'
  | 'invalid_type'
  | 'compression_failed';

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
