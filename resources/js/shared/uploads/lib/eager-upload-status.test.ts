import { isDoneStatus, isFailedStatus, isInFlightStatus, isUploadingStatus } from '@/shared/uploads/lib/eager-upload-status';
import { describe, expect, it } from 'vitest';

describe('eager-upload-status predicates', () => {
  it('isInFlightStatus is true for compressing and uploading only', () => {
    expect(isInFlightStatus('compressing')).toBe(true);
    expect(isInFlightStatus('uploading')).toBe(true);
    expect(isInFlightStatus('idle')).toBe(false);
    expect(isInFlightStatus('done')).toBe(false);
    expect(isInFlightStatus('failed')).toBe(false);
    expect(isInFlightStatus(undefined)).toBe(false);
    expect(isInFlightStatus(null)).toBe(false);
  });

  it('isFailedStatus is true only for failed', () => {
    expect(isFailedStatus('failed')).toBe(true);
    expect(isFailedStatus('uploading')).toBe(false);
    expect(isFailedStatus(undefined)).toBe(false);
  });

  it('isDoneStatus is true only for done', () => {
    expect(isDoneStatus('done')).toBe(true);
    expect(isDoneStatus('failed')).toBe(false);
  });

  it('isUploadingStatus is true only for uploading', () => {
    expect(isUploadingStatus('uploading')).toBe(true);
    expect(isUploadingStatus('compressing')).toBe(false);
  });
});
