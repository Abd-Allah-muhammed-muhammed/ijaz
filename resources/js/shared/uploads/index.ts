export {
  UPLOAD_DOCUMENT_IMAGE_COMPRESSION,
  UPLOAD_FIELD_LOGO,
  UPLOAD_LOGO_COMPRESSION,
  UPLOAD_MAX_FILE_SIZE_BYTES,
  UPLOAD_MAX_FILE_SIZE_MB,
  type ImageCompressionProfile,
  type UploadCompressionKind,
} from '@/shared/uploads/constants';

export { compressImageFile, compressUploadFile, isImageFile, isPdfFile } from '@/shared/uploads/lib/compress-image';

export { useEagerFileUpload } from '@/shared/uploads/hooks/use-eager-file-upload';

export { prepareLocalImage, validateImageMime, validateImageSize } from '@/shared/uploads/hooks/use-local-image-prepare';

export { default as BackgroundUploadTray } from '@/shared/uploads/components/BackgroundUploadTray';
export type {
  BackgroundUploadTrayEntry,
  BackgroundUploadTrayProps,
  BackgroundUploadTrayStatus,
} from '@/shared/uploads/components/BackgroundUploadTray';

export type {
  EagerUploadEntry,
  EagerUploadFn,
  EagerUploadFnArgs,
  EagerUploadStatus,
  EagerUploadSuccess,
  LocalImagePrepareErrorCode,
  LocalImagePrepareOptions,
  LocalImagePrepareResult,
  UploadStatus,
  UseEagerFileUploadOptions,
  UseEagerFileUploadResult,
} from '@/shared/uploads/types';
