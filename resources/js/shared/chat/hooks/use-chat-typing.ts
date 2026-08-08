import { useCallback, useEffect, useRef, useState } from 'react';
import type { ConversationUser } from '@/shared/types/models';
import axios from '@/shared/helpers/axios';
import { echoSocketId } from '@/shared/chat/utils/conversation';

/** Throttle while actively typing — avoid a request per keystroke. */
export const TYPING_EMIT_INTERVAL_MS = 2500;

/** Auto-hide indicator if no further typing events arrive. */
export const TYPING_DISPLAY_TTL_MS = 3000;

type Options = {
  conversationId?: string | number | null;
  currentSocketId?: string | null;
  typingUrl?: string | null;
  enabled?: boolean;
};

type UseChatTypingResult = {
  typingUser: ConversationUser | null;
  handleRemoteTyping: (user: ConversationUser) => void;
  notifyTyping: () => void;
  clearTyping: () => void;
  resetEmitThrottle: () => void;
};

/**
 * Shared typing indicator for Provider chat and Admin Tickets.
 * Emits a throttled POST to the conversation typing endpoint (presence broadcast)
 * and tracks the remote participant shown in the message list.
 */
export function useChatTyping({
  conversationId,
  currentSocketId,
  typingUrl,
  enabled = true,
}: Options): UseChatTypingResult {
  const [typingUser, setTypingUser] = useState<ConversationUser | null>(null);
  const lastEmitAtRef = useRef(0);
  const clearTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const clearTyping = useCallback(() => {
    setTypingUser(null);
    if (clearTimerRef.current) {
      clearTimeout(clearTimerRef.current);
      clearTimerRef.current = null;
    }
  }, []);

  const handleRemoteTyping = useCallback(
    (user: ConversationUser) => {
      if (!user?.socket_id || user.socket_id === currentSocketId) {
        return;
      }

      setTypingUser(user);

      if (clearTimerRef.current) {
        clearTimeout(clearTimerRef.current);
      }

      clearTimerRef.current = setTimeout(() => {
        setTypingUser(null);
        clearTimerRef.current = null;
      }, TYPING_DISPLAY_TTL_MS);
    },
    [currentSocketId],
  );

  const notifyTyping = useCallback(() => {
    if (!enabled || !typingUrl) {
      return;
    }

    const now = Date.now();
    if (now - lastEmitAtRef.current < TYPING_EMIT_INTERVAL_MS) {
      return;
    }

    lastEmitAtRef.current = now;

    const headers: Record<string, string> = {};
    const socketId = echoSocketId();
    if (socketId) {
      headers['X-Socket-Id'] = socketId;
    }

    void axios.post(typingUrl, {}, { headers }).catch(() => {
      // Best-effort signal — ignore network failures.
    });
  }, [enabled, typingUrl]);

  const resetEmitThrottle = useCallback(() => {
    lastEmitAtRef.current = 0;
  }, []);

  useEffect(() => {
    clearTyping();
    lastEmitAtRef.current = 0;

    return () => {
      if (clearTimerRef.current) {
        clearTimeout(clearTimerRef.current);
        clearTimerRef.current = null;
      }
    };
  }, [conversationId, clearTyping]);

  return {
    typingUser,
    handleRemoteTyping,
    notifyTyping,
    clearTyping,
    resetEmitThrottle,
  };
}
