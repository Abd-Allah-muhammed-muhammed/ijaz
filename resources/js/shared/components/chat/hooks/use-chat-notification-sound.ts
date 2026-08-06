import { useCallback, useEffect, useRef } from 'react';
import { url as appUrl } from '@/shared/helpers/general';

/** Max one notification sound per this many ms (rapid message bursts). */
export const CHAT_NOTIFICATION_THROTTLE_MS = 3000;

const SOUND_PATH = '/media/sounds/chat-notification.wav';

function isPageHidden(): boolean {
  if (typeof document === 'undefined') {
    return false;
  }

  return document.visibilityState === 'hidden';
}

/**
 * Plays a short chat notification when a message arrives while the tab is
 * unfocused. Unlocks Audio after the first user gesture (browser autoplay
 * policy). Failures are swallowed — never throw from blocked playback.
 */
export function useChatNotificationSound() {
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const unlockedRef = useRef(false);
  const lastPlayedAtRef = useRef(0);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const audio = new Audio(appUrl(SOUND_PATH));
    audio.preload = 'auto';
    audio.volume = 0.55;
    audioRef.current = audio;

    const unlock = () => {
      if (unlockedRef.current || !audioRef.current) {
        return;
      }

      // Quiet prime so later play() is allowed without a gesture.
      const el = audioRef.current;
      const previousVolume = el.volume;
      el.muted = true;
      el.volume = 0;
      void el
        .play()
        .then(() => {
          el.pause();
          el.currentTime = 0;
          el.muted = false;
          el.volume = previousVolume;
          unlockedRef.current = true;
          window.removeEventListener('pointerdown', unlock);
          window.removeEventListener('keydown', unlock);
        })
        .catch(() => {
          el.muted = false;
          el.volume = previousVolume;
        });
    };

    window.addEventListener('pointerdown', unlock, { passive: true });
    window.addEventListener('keydown', unlock);

    return () => {
      window.removeEventListener('pointerdown', unlock);
      window.removeEventListener('keydown', unlock);
      audio.pause();
      audioRef.current = null;
    };
  }, []);

  const notifyIncomingMessage = useCallback((isFromCurrentUser: boolean) => {
    if (isFromCurrentUser) {
      return;
    }

    if (!isPageHidden()) {
      return;
    }

    const now = Date.now();
    if (now - lastPlayedAtRef.current < CHAT_NOTIFICATION_THROTTLE_MS) {
      return;
    }

    const audio = audioRef.current;
    if (!audio) {
      return;
    }

    lastPlayedAtRef.current = now;

    try {
      audio.currentTime = 0;
      void audio.play().catch(() => {
        // Autoplay / unlock race — ignore.
      });
    } catch {
      // Ignore synchronous media errors.
    }
  }, []);

  return { notifyIncomingMessage };
}
