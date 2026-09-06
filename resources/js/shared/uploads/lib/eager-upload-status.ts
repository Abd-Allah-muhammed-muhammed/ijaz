import type { UploadStatus } from '@/shared/uploads/types';

export function isInFlightStatus(status: UploadStatus | null | undefined): boolean {
  return status === 'compressing' || status === 'uploading';
}

export function isFailedStatus(status: UploadStatus | null | undefined): boolean {
  return status === 'failed';
}

export function isDoneStatus(status: UploadStatus | null | undefined): boolean {
  return status === 'done';
}

export function isUploadingStatus(status: UploadStatus | null | undefined): boolean {
  return status === 'uploading';
}
