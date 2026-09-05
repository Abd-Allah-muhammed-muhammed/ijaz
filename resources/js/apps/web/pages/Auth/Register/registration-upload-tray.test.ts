import { describe, expect, it, vi } from 'vitest';

type Entry = {
  field: string;
  status: 'idle' | 'compressing' | 'uploading' | 'done' | 'failed';
  progress: number;
};

/**
 * Lightweight state reducer mirror for tray summary + abort-on-replace semantics.
 */
function summarize(entries: Entry[]) {
  return {
    uploading: entries.filter((e) => e.status === 'compressing' || e.status === 'uploading').length,
    done: entries.filter((e) => e.status === 'done').length,
    failed: entries.filter((e) => e.status === 'failed').length,
  };
}

describe('registration upload tray state', () => {
  it('reflects in-flight and failed counts from a non-Files step snapshot', () => {
    const summary = summarize([
      { field: 'logo', status: 'uploading', progress: 40 },
      { field: 'id_image', status: 'done', progress: 100 },
      { field: 'iban_certification', status: 'failed', progress: 0 },
    ]);

    expect(summary).toEqual({ uploading: 1, done: 1, failed: 1 });
  });
});

describe('abort-on-replace', () => {
  it('aborts the previous controller before starting a replacement upload', () => {
    const abort = vi.fn();
    const previous = { abort } as unknown as AbortController;
    const controllers: Record<string, AbortController | undefined> = { logo: previous };

    const replace = (field: string) => {
      controllers[field]?.abort();
      delete controllers[field];
      controllers[field] = new AbortController();
    };

    replace('logo');

    expect(abort).toHaveBeenCalledOnce();
    expect(controllers.logo).toBeInstanceOf(AbortController);
    expect(controllers.logo).not.toBe(previous);
  });
});
