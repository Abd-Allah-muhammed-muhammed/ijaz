/**
 * Rough client equivalent of Carbon shortAbsoluteDiffForHumans()
 * (e.g. "0s", "45s", "2m", "3h", "1d") for live chat timestamps.
 */
export function formatShortAbsoluteDiff(from: Date, now: Date = new Date()): string {
  const diffMs = now.getTime() - from.getTime();
  const seconds = Math.max(0, Math.floor(diffMs / 1000));

  if (seconds < 60) {
    return `${seconds}s`;
  }

  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) {
    return `${minutes}m`;
  }

  const hours = Math.floor(minutes / 60);
  if (hours < 24) {
    return `${hours}h`;
  }

  const days = Math.floor(hours / 24);
  if (days < 7) {
    return `${days}d`;
  }

  const weeks = Math.floor(days / 7);
  if (weeks < 4) {
    return `${weeks}w`;
  }

  const months = Math.floor(days / 30);
  if (months < 12) {
    return `${months}mo`;
  }

  const years = Math.floor(days / 365);

  return `${Math.max(1, years)}y`;
}

export function parseChatAbsoluteTime(
  value: string | Date | null | undefined,
): Date | null {
  if (value == null || value === '') {
    return null;
  }

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value;
  }

  // Reject already-humanized strings ("2m", "1h") — only ISO / parseable absolutes.
  if (/^\d+[smhdwy]|^\d+mo$/i.test(value.trim()) && !value.includes('T') && !value.includes('-')) {
    return null;
  }

  const parsed = new Date(value);

  return Number.isNaN(parsed.getTime()) ? null : parsed;
}
