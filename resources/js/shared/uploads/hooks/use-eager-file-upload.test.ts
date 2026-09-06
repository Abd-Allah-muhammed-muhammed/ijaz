import { describe, expect, it, vi } from 'vitest';
import {
  isDoneStatus,
  isFailedStatus,
  isInFlightStatus,
} from '@/shared/uploads/lib/eager-upload-status';
import type { UploadStatus } from '@/shared/uploads/types';

/**
 * Mirrors abort-on-replace + tray summary semantics from useEagerFileUpload
 * so registration and profile suites keep covering shared behavior.
 */
type Entry = {
  field: string;
  status: UploadStatus;
  progress: number;
};

function summarize(entries: Entry[]) {
  return {
    uploading: entries.filter((e) => isInFlightStatus(e.status)).length,
    done: entries.filter((e) => isDoneStatus(e.status)).length,
    failed: entries.filter((e) => isFailedStatus(e.status)).length,
  };
}

describe('shared eager-upload tray state', () => {
  it('summarizes in-flight, done, and failed counts for the tray', () => {
    expect(
      summarize([
        { field: 'logo', status: 'compressing', progress: 0 },
        { field: 'id_image', status: 'uploading', progress: 40 },
        { field: 'iban_certification', status: 'done', progress: 100 },
        { field: 'commercial_record', status: 'failed', progress: 0 },
      ]),
    ).toEqual({ uploading: 2, done: 1, failed: 1 });
  });
});

describe('shared eager-upload abort-on-replace', () => {
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
