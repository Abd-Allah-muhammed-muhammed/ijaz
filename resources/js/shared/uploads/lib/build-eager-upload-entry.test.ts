import { buildDoneEntry, buildFailedEntry, buildInFlightEntry } from '@/shared/uploads/lib/build-eager-upload-entry';
import { describe, expect, it } from 'vitest';

const file = new File(['x'], 'doc.pdf', { type: 'application/pdf' });

describe('build-eager-upload-entry', () => {
  it('builds a failed entry with progress 0', () => {
    expect(
      buildFailedEntry({
        field: 'id_image',
        fileName: 'doc.pdf',
        error: 'file_too_large',
        selectedFile: file,
        meta: { uploadId: null },
      }),
    ).toEqual({
      field: 'id_image',
      fileName: 'doc.pdf',
      status: 'failed',
      progress: 0,
      error: 'file_too_large',
      selectedFile: file,
      meta: { uploadId: null },
    });
  });

  it('builds an in-flight entry with error null', () => {
    expect(
      buildInFlightEntry({
        field: 'logo',
        fileName: 'logo.png',
        status: 'compressing',
        selectedFile: file,
        meta: { url: null },
      }),
    ).toMatchObject({
      status: 'compressing',
      progress: 0,
      error: null,
    });
  });

  it('builds a done entry at 100 progress', () => {
    expect(
      buildDoneEntry({
        field: 'logo',
        fileName: 'logo.png',
        selectedFile: null,
        meta: { url: 'https://example.test/logo.png' },
      }),
    ).toMatchObject({
      status: 'done',
      progress: 100,
      error: null,
      selectedFile: null,
    });
  });
});
