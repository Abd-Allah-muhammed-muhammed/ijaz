import { describe, expect, it } from 'vitest';
import { resolveGuarantorChatSenderBadge } from './guarantor-chat-sender-badge';

const t = (key: string, options?: Record<string, unknown>) => {
  if (key === 'guarantor.requester') {
    return 'مقدم الطلب';
  }
  if (key === 'guarantor.counterparty') {
    return 'الطرف الآخر';
  }
  if (key === 'Admin') {
    return String(options?.defaultValue ?? 'Admin');
  }

  return key;
};

describe('resolveGuarantorChatSenderBadge', () => {
  it('Guarantor admin Chat tab renders a role badge (مقدم الطلب / الطرف الآخر) next to each message sender name', () => {
    expect(resolveGuarantorChatSenderBadge({ party_role: 'requester' }, t)).toBe('مقدم الطلب');
    expect(resolveGuarantorChatSenderBadge({ party_role: 'counterparty' }, t)).toBe('الطرف الآخر');
  });

  it('an admin-sent message shows an admin indicator, not a party role badge', () => {
    expect(
      resolveGuarantorChatSenderBadge(
        { party_role: null, sender: { type: 'admin' } },
        t,
      ),
    ).toBe('Admin');

    expect(
      resolveGuarantorChatSenderBadge(
        { party_role: 'requester', sender: { type: 'admin' } },
        t,
      ),
    ).toBe('مقدم الطلب');
  });

  it('non-Guarantor chat tabs (e.g. Orders) are visually unaffected — no role badge appears where it does not apply', () => {
    expect(resolveGuarantorChatSenderBadge({}, t)).toBeNull();
    expect(resolveGuarantorChatSenderBadge({ sender: { type: 'user' } }, t)).toBeNull();
    expect(resolveGuarantorChatSenderBadge({ party_role: undefined }, t)).toBeNull();
  });
});
