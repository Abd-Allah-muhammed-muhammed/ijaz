import { useEffect, useState } from 'react';

/** Shared chat relative-time cadence — one interval for all mounted subscribers. */
export const CHAT_RELATIVE_TIME_TICK_MS = 30_000;

type Listener = () => void;

const listeners = new Set<Listener>();
let intervalId: ReturnType<typeof setInterval> | null = null;

function ensureSharedTick(): void {
  if (intervalId !== null || typeof window === 'undefined') {
    return;
  }

  intervalId = window.setInterval(() => {
    listeners.forEach((listener) => listener());
  }, CHAT_RELATIVE_TIME_TICK_MS);
}

function maybeStopSharedTick(): void {
  if (listeners.size > 0 || intervalId === null || typeof window === 'undefined') {
    return;
  }

  window.clearInterval(intervalId);
  intervalId = null;
}

/**
 * Single shared "now" tick for chat relative timestamps.
 * Mounting N RelativeTimestamp components still uses ONE interval.
 */
export function useSharedRelativeNow(): number {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    const onTick = () => setNow(Date.now());
    listeners.add(onTick);
    ensureSharedTick();

    return () => {
      listeners.delete(onTick);
      maybeStopSharedTick();
    };
  }, []);

  return now;
}
