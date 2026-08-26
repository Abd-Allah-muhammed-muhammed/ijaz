export type GuarantorHistoryReasonTranslator = (
  key: string,
  options?: Record<string, unknown>,
) => string;

export const GUARANTOR_MACHINE_RESOLUTION_REASON_PREFIXES = [
  'dispute_resolved_full_requester',
  'dispute_resolved_full_counterparty',
  'dispute_escalated_to_court',
  'dispute_resolved_percentage_split',
] as const;

export const GUARANTOR_CLOSED_BY_ADMIN_CANCEL_REASON = 'dispute_closed_by_admin_cancel';

export const isGuarantorMachineHistoryReason = (reason?: string | null): boolean => {
  if (!reason) {
    return false;
  }

  if (reason === GUARANTOR_CLOSED_BY_ADMIN_CANCEL_REASON) {
    return true;
  }

  return GUARANTOR_MACHINE_RESOLUTION_REASON_PREFIXES.some(
    (prefix) => reason === prefix || reason.startsWith(`${prefix}:`),
  );
};

/**
 * Translates known machine-coded status-history reasons; returns free-text reasons verbatim.
 */
export const parseGuarantorHistoryReason = (
  reason: string | null | undefined,
  t: GuarantorHistoryReasonTranslator,
): string => {
  if (!reason) {
    return '';
  }

  if (!isGuarantorMachineHistoryReason(reason)) {
    return reason;
  }

  return formatGuarantorMachineHistoryReason(reason, t);
};

export const formatGuarantorMachineHistoryReason = (
  reason: string,
  t: GuarantorHistoryReasonTranslator,
): string => {
  if (reason === 'dispute_resolved_full_requester') {
    return t('guarantor.dispute_outcome_full_requester');
  }

  if (reason === 'dispute_resolved_full_counterparty') {
    return t('guarantor.dispute_outcome_full_counterparty');
  }

  if (reason === 'dispute_escalated_to_court') {
    return t('guarantor.dispute_outcome_escalated');
  }

  if (reason.startsWith('dispute_resolved_percentage_split')) {
    const ratio = reason.includes(':') ? reason.split(':')[1] : null;
    if (ratio) {
      const [requester, counterparty] = ratio.split('/');

      return t('guarantor.dispute_outcome_percentage_split_detail', {
        requester,
        counterparty,
        defaultValue: `Percentage split — requester ${requester}%, counterparty ${counterparty}%`,
      });
    }

    return t('guarantor.dispute_outcome_percentage_split');
  }

  if (reason === GUARANTOR_CLOSED_BY_ADMIN_CANCEL_REASON) {
    return t('guarantor.dispute_outcome_admin_cancel');
  }

  return reason;
};
