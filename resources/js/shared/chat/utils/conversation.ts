import type { Conversation, ConversationMessage } from '@/shared/types/models';
import { isAxiosError } from 'axios';

/** px from bottom — treat as "still following the live end". */
export const NEAR_BOTTOM_THRESHOLD_PX = 120;

export function echoSocketId(): string | undefined {
  try {
    return window.Echo?.socketId() ?? undefined;
  } catch {
    return undefined;
  }
}

/**
 * Parse MMAE-style validation envelopes (and PostTooLargeException, which uses
 * the same shape with `errors.files` when PHP rejects the body before Laravel
 * can attribute a specific file index).
 *
 * Returns empty `messages` when there is no response body, no field errors, or
 * only the envelope title "Validation Failed" — callers must toast a generic
 * fallback in that case (network drop / unexpected 5xx / empty payload).
 */
export function extractValidationErrors(
  error: unknown,
  attachedFileCount = 0,
): {
  messages: string[];
  fileIndexes: number[];
} {
  if (!isAxiosError(error) || !error.response?.data) {
    return { messages: [], fileIndexes: [] };
  }

  const data = error.response.data as {
    message?: string;
    errors?: Record<string, string[] | string>;
  };

  const messages: string[] = [];
  const fileIndexes: number[] = [];

  if (data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)) {
    for (const [key, value] of Object.entries(data.errors)) {
      const list = Array.isArray(value) ? value : [String(value)];
      const usable = list.map(String).map((s) => s.trim()).filter(Boolean);
      messages.push(...usable);

      const match = key.match(/^files(?:\.|\[)(\d+)/);
      if (match) {
        fileIndexes.push(Number(match[1]));
        continue;
      }

      // PostTooLarge / bag-level "files" — highlight every attached preview.
      if (key === 'files' && attachedFileCount > 0) {
        for (let i = 0; i < attachedFileCount; i++) {
          fileIndexes.push(i);
        }
      }
    }
  }

  // Envelope title alone is not a real validation detail — leave messages empty
  // so the UI shows the generic fallback instead of "Validation Failed".
  if (messages.length === 0 && typeof data.message === 'string') {
    const trimmed = data.message.trim();
    if (trimmed !== '' && trimmed.toLowerCase() !== 'validation failed') {
      messages.push(trimmed);
    }
  }

  return { messages, fileIndexes: [...new Set(fileIndexes)] };
}

export function buildSidebarPreviewFromMessage(
  message: ConversationMessage,
  conversation: Conversation,
): Conversation {
  const attachmentsCount = message.attachments?.length
    ?? message.attachments_count
    ?? 0;

  return {
    ...conversation,
    last_message: {
      ...message,
      content: message.content,
      has_attachments: Boolean(message.has_attachments) || attachmentsCount > 0,
      attachments_count: attachmentsCount,
    },
    last_message_at: typeof message.created_at === 'string'
      ? message.created_at
      : conversation.last_message_at,
    last_massage_at: typeof message.created_at === 'string'
      ? message.created_at
      : conversation.last_massage_at,
    last_message_at_iso: message.created_at_iso
      ?? (message.created_at instanceof Date ? message.created_at.toISOString() : conversation.last_message_at_iso),
    unread_count: 0,
  };
}

export function isNearBottom(el: HTMLElement, thresholdPx = NEAR_BOTTOM_THRESHOLD_PX): boolean {
  return el.scrollHeight - el.scrollTop - el.clientHeight <= thresholdPx;
}
