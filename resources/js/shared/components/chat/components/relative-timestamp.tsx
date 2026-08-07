import { formatShortAbsoluteDiff, parseChatAbsoluteTime } from '@/shared/components/chat/utils/format-short-absolute-diff';
import { useSharedRelativeNow } from '@/shared/components/chat/hooks/use-shared-relative-now';

type Props = {
  /** Prefer ISO / Date — enables live updates via the shared tick. */
  iso?: string | Date | null;
  /** Pre-humanized API string used only when iso is missing/unparseable. */
  fallback?: string | null;
  className?: string;
};

/**
 * Live relative timestamp driven by the shared chat tick (not per-bubble timers).
 */
export default function RelativeTimestamp({ iso, fallback = '', className }: Props) {
  const nowMs = useSharedRelativeNow();
  const absolute = parseChatAbsoluteTime(iso);

  const label = absolute
    ? formatShortAbsoluteDiff(absolute, new Date(nowMs))
    : String(fallback ?? '');

  return <span className={className}>{label}</span>;
}
