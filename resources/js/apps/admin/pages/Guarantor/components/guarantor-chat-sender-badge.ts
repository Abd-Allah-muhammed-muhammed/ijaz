import type { ConversationMessage } from '@/shared/types/models';

export type GuarantorPartyRole = 'requester' | 'counterparty';

type Translate = (key: string, options?: Record<string, unknown>) => string;

/**
 * Resolve the Guarantor admin Chat tab sender badge label from a message's
 * party_role (and admin sender type). Returns null when no badge applies.
 */
export function resolveGuarantorChatSenderBadge(
  message: Pick<ConversationMessage, 'party_role'> & {
    sender?: { type?: string } | null;
  },
  t: Translate,
): string | null {
  if (message.party_role === 'requester') {
    return t('guarantor.requester');
  }

  if (message.party_role === 'counterparty') {
    return t('guarantor.counterparty');
  }

  if (message.sender?.type === 'admin') {
    return t('Admin', { defaultValue: 'Admin' });
  }

  return null;
}
