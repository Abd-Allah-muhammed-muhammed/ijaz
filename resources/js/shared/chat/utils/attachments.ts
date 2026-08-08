export function formatFileSize(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes < 0) {
    return '';
  }

  if (bytes < 1024) {
    return `${bytes} B`;
  }

  if (bytes < 1024 * 1024) {
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  }

  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function isImageMime(mimeType?: string | null): boolean {
  return Boolean(mimeType?.startsWith('image/'));
}

export function isPdfMime(mimeType?: string | null): boolean {
  return Boolean(mimeType?.includes('pdf'));
}

export function isImageFile(file: File): boolean {
  return isImageMime(file.type) || /\.(jpe?g|png|gif|webp)$/i.test(file.name);
}

export function isPdfFile(file: File): boolean {
  return isPdfMime(file.type) || /\.pdf$/i.test(file.name);
}

export function isImageAttachment(input: {
  type?: string;
  mime_type?: string;
  file_name?: string;
  name?: string;
  extension?: string;
}): boolean {
  const name = (input.file_name || input.name || '').toLowerCase();

  return (
    input.type === 'image' ||
    isImageMime(input.mime_type) ||
    /\.(jpe?g|png|gif|webp)$/i.test(name)
  );
}

export function isPdfAttachment(input: {
  type?: string;
  mime_type?: string;
  file_name?: string;
  name?: string;
  extension?: string;
}): boolean {
  const name = (input.file_name || input.name || '').toLowerCase();

  return (
    input.type === 'pdf' ||
    input.extension === 'pdf' ||
    isPdfMime(input.mime_type) ||
    name.endsWith('.pdf')
  );
}

export function attachmentDisplayName(input: {
  file_name?: string;
  name?: string;
}): string {
  return input.file_name || input.name || 'file';
}
