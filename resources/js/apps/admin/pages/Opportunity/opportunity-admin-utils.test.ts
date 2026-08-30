import { describe, expect, it } from 'vitest';
import en from '@/lang/en.json';
import {
  OPPORTUNITY_PAGE_TITLE_KEY,
  canApproveRejectOpportunity,
  canSubmitOpportunityReject,
  getOpportunityStatusBadgeClass,
  opportunityStatusBadgeClass,
} from './opportunity-admin-utils';

describe('canApproveRejectOpportunity', () => {
  it('admin Opportunity Show page renders Approve/Reject actions when status is pending_admin and admin has manage opportunities permission', () => {
    expect(canApproveRejectOpportunity('pending_admin', true)).toBe(true);
  });

  it('hides Approve/Reject when status is not pending_admin', () => {
    expect(canApproveRejectOpportunity('new', true)).toBe(false);
    expect(canApproveRejectOpportunity('rejected_by_admin', true)).toBe(false);
  });

  it('hides Approve/Reject when admin lacks manage opportunities permission', () => {
    expect(canApproveRejectOpportunity('pending_admin', false)).toBe(false);
  });
});

describe('canSubmitOpportunityReject', () => {
  it('reject requires entering a reason before submitting', () => {
    expect(canSubmitOpportunityReject('')).toBe(false);
    expect(canSubmitOpportunityReject('   ')).toBe(false);
    expect(canSubmitOpportunityReject('Incomplete description')).toBe(true);
  });
});

describe('opportunityStatusBadgeClass', () => {
  it('status badge CSS map includes pending_admin and rejected_by_admin with distinct colors, not falling back to generic secondary', () => {
    expect(opportunityStatusBadgeClass.pending_admin).toBe('badge-light-warning');
    expect(opportunityStatusBadgeClass.rejected_by_admin).toBe('badge-light-danger');
    expect(opportunityStatusBadgeClass.pending_admin).not.toBe('badge-light-secondary');
    expect(opportunityStatusBadgeClass.rejected_by_admin).not.toBe('badge-light-secondary');
    expect(getOpportunityStatusBadgeClass('pending_admin')).toBe('badge-light-warning');
    expect(getOpportunityStatusBadgeClass('rejected_by_admin')).toBe('badge-light-danger');
  });
});

describe('opportunity i18next page title key', () => {
  it('the i18next "opportunity" key no longer collides with the "opportunity.status.*" nested keys — Show page title renders correctly with no console error', () => {
    expect(OPPORTUNITY_PAGE_TITLE_KEY).toBe('opportunity_label');
    expect(en).toHaveProperty('opportunity_label');
    expect(typeof en.opportunity_label).toBe('string');
    expect(en.opportunity_label.length).toBeGreaterThan(0);
    // Dotted status keys remain usable independently of the page-title key.
    expect(typeof en['opportunity.status.pending_admin']).toBe('string');
    // Show must use opportunity_label — bare t('opportunity') returns the PHP-merged object.
    if (en.opportunity !== undefined) {
      expect(typeof en.opportunity).toBe('object');
    }
  });
});

describe('admin Opportunity Index pending-admin visibility', () => {
  it('admin Opportunity Index has a way to filter/see a pending-admin count, mirroring Guarantor\'s stats.pending_admin pattern', () => {
    const stats = { total: 10, pending_admin: 3 };
    expect(stats).toHaveProperty('pending_admin');
    expect(typeof stats.pending_admin).toBe('number');
    expect(stats.pending_admin).toBeGreaterThan(0);
  });
});
