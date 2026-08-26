import { describe, expect, it } from 'vitest';
import {
  formatGuarantorMachineHistoryReason,
  isGuarantorMachineHistoryReason,
  parseGuarantorHistoryReason,
  type GuarantorHistoryReasonTranslator,
} from './parseGuarantorHistoryReason';

const mockT: GuarantorHistoryReasonTranslator = (key, options) => {
  if (key === 'guarantor.dispute_outcome_full_requester') {
    return 'Resolved in favor of the requester (full amount)';
  }

  if (key === 'guarantor.dispute_outcome_percentage_split_detail') {
    return `Percentage split — requester ${options?.requester}%, counterparty ${options?.counterparty}%`;
  }

  return key;
};

describe('parseGuarantorHistoryReason', () => {
  it('translates dispute_resolved_percentage_split:X/Y into a human-readable label', () => {
    expect(parseGuarantorHistoryReason('dispute_resolved_percentage_split:70/30', mockT)).toBe(
      'Percentage split — requester 70%, counterparty 30%',
    );
  });

  it('translates dispute_resolved_full_requester into its label', () => {
    expect(parseGuarantorHistoryReason('dispute_resolved_full_requester', mockT)).toBe(
      'Resolved in favor of the requester (full amount)',
    );
  });

  it('leaves genuine free-text reasons verbatim', () => {
    const freeText = 'Work not delivered as agreed — client refuses partial refund';

    expect(parseGuarantorHistoryReason(freeText, mockT)).toBe(freeText);
  });

  it('uses the same machine-code detection for dispute tab resolution lookup', () => {
    expect(isGuarantorMachineHistoryReason('dispute_resolved_percentage_split:70/30')).toBe(true);
    expect(isGuarantorMachineHistoryReason('Goods not as agreed')).toBe(false);
  });

  it('exposes formatGuarantorMachineHistoryReason for dispute-tab resolution display', () => {
    expect(formatGuarantorMachineHistoryReason('dispute_resolved_full_requester', mockT)).toBe(
      'Resolved in favor of the requester (full amount)',
    );
  });
});
