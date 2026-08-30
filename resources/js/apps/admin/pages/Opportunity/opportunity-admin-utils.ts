export const opportunityStatusBadgeClass: Record<string, string> = {
  new: 'badge-light-primary',
  pending_admin: 'badge-light-warning',
  rejected_by_admin: 'badge-light-danger',
  offer_accepted: 'badge-light-warning',
  in_progress: 'badge-light-info',
  ended: 'badge-light-success',
  cancelled: 'badge-light-danger',
  expired: 'badge-light-secondary',
};

export function getOpportunityStatusBadgeClass(status: string | undefined | null): string {
  if (!status) {
    return 'badge-light-secondary';
  }

  return opportunityStatusBadgeClass[status] ?? 'badge-light-secondary';
}

export function canApproveRejectOpportunity(
  status: string | undefined | null,
  hasManageOpportunitiesPermission: boolean,
): boolean {
  return hasManageOpportunitiesPermission && status === 'pending_admin';
}

export function canSubmitOpportunityReject(reason: string): boolean {
  return reason.trim().length > 0;
}

/** Page-title key that must not collide with nested opportunity.status.* / opportunity.php tree. */
export const OPPORTUNITY_PAGE_TITLE_KEY = 'opportunity_label' as const;
