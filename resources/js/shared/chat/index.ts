/**
 * Shared chat feature module — frontend counterpart to Modules/Chat.
 *
 * Public entry points for pages/layouts. Prefer importing from `@/shared/chat`
 * rather than deep paths under components/hooks.
 */

export { default as ConversationContent } from '@/shared/chat/components/conversation-content';
export { default as ConversationsPanel } from '@/shared/chat/components/inbox/conversations-panel';
export { default as ChatComposer } from '@/shared/chat/components/chat-composer';
export type { ChatComposerProps } from '@/shared/chat/components/chat-composer';

export { ConversationProvider, useConversations, ConversationContext } from '@/shared/chat/store';

export type {
  ConversationContentEndpoints,
  ConversationHeaderSlotContext,
} from '@/shared/chat/types';
