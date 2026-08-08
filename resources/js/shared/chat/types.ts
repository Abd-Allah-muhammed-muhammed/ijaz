import type { ReactNode } from 'react';

/** API URLs for controlled ConversationContent (Admin Orders / Tickets). */
export type ConversationContentEndpoints = {
  messagesUrl: (options?: { search?: string; page?: number }) => string;
  sendUrl: string;
  typingUrl?: string | null;
};

/** Context passed to a custom `headerSlot` so pages can place search in their chrome. */
export type ConversationHeaderSlotContext = {
  /**
   * Search toggle + input for the consumer to place in its header row.
   * `null` when there is no active conversation (empty state) — hide the control.
   */
  searchToolbar: ReactNode;
};
