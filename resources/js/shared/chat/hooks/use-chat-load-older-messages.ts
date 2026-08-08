import { useCallback, useLayoutEffect, useRef, useState } from 'react';
import type { ConversationMessage } from '@/shared/types/models';

const NEAR_TOP_THRESHOLD_PX = 80;

type FetchOlderPageResult = {
  items: ConversationMessage[];
  hasMorePages: boolean;
  currentPage: number;
};

type Options = {
  /** When false (e.g. search mode), never fetch older pages. */
  enabled: boolean;
  messagesBoxRef: React.RefObject<HTMLDivElement | null>;
  /**
   * Fetch one older page (API newest-first). Caller should reverse items
   * to chronological before returning, or return already chronological.
   */
  fetchOlderPage: (page: number) => Promise<FetchOlderPageResult>;
  /** Prepend chronological older messages onto the existing list. */
  onPrepend: (olderChronological: ConversationMessage[]) => void;
  /** Called when stick-to-bottom must stay off during prepend restores. */
  onDisableStickToBottom?: () => void;
};

/**
 * Load older messages when the user scrolls near the top of the chat viewport,
 * preserving scroll position after prepending (no jump).
 */
export function useChatLoadOlderMessages({
  enabled,
  messagesBoxRef,
  fetchOlderPage,
  onPrepend,
  onDisableStickToBottom,
}: Options) {
  const [loadingOlder, setLoadingOlder] = useState(false);
  const [hasMoreOlder, setHasMoreOlder] = useState(true);
  const [reachedBeginning, setReachedBeginning] = useState(false);
  const nextPageRef = useRef(2);
  const loadingRef = useRef(false);
  const scrollRestoreRef = useRef<{ height: number; top: number } | null>(null);

  const resetPagination = useCallback((hasMorePages: boolean, currentPage = 1) => {
    nextPageRef.current = currentPage + 1;
    setHasMoreOlder(hasMorePages);
    setReachedBeginning(!hasMorePages);
    setLoadingOlder(false);
    loadingRef.current = false;
    scrollRestoreRef.current = null;
  }, []);

  const loadOlder = useCallback(async () => {
    if (!enabled || loadingRef.current || !hasMoreOlder) {
      return;
    }

    const el = messagesBoxRef.current;
    if (!el) {
      return;
    }

    loadingRef.current = true;
    setLoadingOlder(true);
    onDisableStickToBottom?.();

    scrollRestoreRef.current = {
      height: el.scrollHeight,
      top: el.scrollTop,
    };

    const page = nextPageRef.current;

    try {
      const result = await fetchOlderPage(page);
      // API returns newest-first; reverse to chronological for prepend.
      const chronological = [...result.items].reverse();

      if (chronological.length === 0) {
        setHasMoreOlder(false);
        setReachedBeginning(true);
        scrollRestoreRef.current = null;
        return;
      }

      onPrepend(chronological);
      nextPageRef.current = page + 1;
      setHasMoreOlder(result.hasMorePages);
      setReachedBeginning(!result.hasMorePages);
    } catch {
      scrollRestoreRef.current = null;
    } finally {
      loadingRef.current = false;
      setLoadingOlder(false);
    }
  }, [
    enabled,
    fetchOlderPage,
    hasMoreOlder,
    messagesBoxRef,
    onDisableStickToBottom,
    onPrepend,
  ]);

  useLayoutEffect(() => {
    const restore = scrollRestoreRef.current;
    const el = messagesBoxRef.current;
    if (!restore || !el) {
      return;
    }

    el.scrollTop = el.scrollHeight - restore.height + restore.top;
    scrollRestoreRef.current = null;
  });

  const onScroll = useCallback(() => {
    if (!enabled || loadingRef.current || !hasMoreOlder) {
      return;
    }

    const el = messagesBoxRef.current;
    if (!el) {
      return;
    }

    if (el.scrollTop <= NEAR_TOP_THRESHOLD_PX) {
      void loadOlder();
    }
  }, [enabled, hasMoreOlder, loadOlder, messagesBoxRef]);

  return {
    loadingOlder,
    hasMoreOlder,
    reachedBeginning,
    resetPagination,
    onScroll,
  };
}
