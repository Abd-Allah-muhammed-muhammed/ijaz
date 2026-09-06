import { describe, expect, it } from 'vitest';
import type { UploadStatus } from '@/shared/uploads/types';

type Entry = {
  field: string;
  status: UploadStatus;
  progress: number;
  url: string | null;
};

function summarize(entries: Entry[]) {
  return {
    uploading: entries.filter(
      (e) => e.status === 'compressing' || e.status === 'uploading',
    ).length,
    done: entries.filter((e) => e.status === 'done').length,
    failed: entries.filter((e) => e.status === 'failed').length,
  };
}

function isFieldUploaded(entry: Entry | undefined, hasExisting: boolean): boolean {
  if (entry?.status === 'done') {
    return true;
  }
  if (entry?.status === 'uploading' || entry?.status === 'failed') {
    return false;
  }

  return hasExisting;
}

describe('profile required-files upload tray state', () => {
  it('shows progress counts while uploads are in flight', () => {
    const summary = summarize([
      { field: 'id_image', status: 'uploading', progress: 35, url: null },
      { field: 'iban_certification', status: 'done', progress: 100, url: '/media/1' },
    ]);

    expect(summary).toEqual({ uploading: 1, done: 1, failed: 0 });
  });

  it('marks a field uploaded after background upload completes', () => {
    expect(
      isFieldUploaded(
        { field: 'id_image', status: 'done', progress: 100, url: '/media/x' },
        false,
      ),
    ).toBe(true);
  });

  it('keeps existing media as uploaded until a replace upload starts', () => {
    expect(isFieldUploaded(undefined, true)).toBe(true);
    expect(
      isFieldUploaded(
        { field: 'id_image', status: 'uploading', progress: 10, url: null },
        true,
      ),
    ).toBe(false);
  });
});
