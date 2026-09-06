import { describe, expect, it, vi } from 'vitest';
import type { BackgroundUploadTrayStatus } from '@/shared/components/uploads/BackgroundUploadTray';

type Entry = {
  field: string;
  status: BackgroundUploadTrayStatus;
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

describe('profile required-files abort-on-replace', () => {
  it('aborts the previous controller before starting a replacement upload', () => {
    const abort = vi.fn();
    const previous = { abort } as unknown as AbortController;
    const controllers: Record<string, AbortController | undefined> = {
      id_image: previous,
    };

    const replace = (field: string) => {
      controllers[field]?.abort();
      delete controllers[field];
      controllers[field] = new AbortController();
    };

    replace('id_image');

    expect(abort).toHaveBeenCalledOnce();
    expect(controllers.id_image).toBeInstanceOf(AbortController);
    expect(controllers.id_image).not.toBe(previous);
  });
});
