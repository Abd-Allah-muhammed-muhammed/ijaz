import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  EMPTY_VALUE_FALLBACK,
  SECTION_CARD_BASE_CLASS,
  STATUS_BADGE_BASE_CLASS,
  STATUS_BADGE_FALLBACK_COLOR_CLASS,
  STAT_TILE_SHELL_CLASS,
} from './types';

const dir = join(__dirname);
const barrelSrc = readFileSync(join(dir, 'index.ts'), 'utf8');
const statusBadgeSrc = readFileSync(join(dir, 'status-badge.tsx'), 'utf8');
const confirmDialogSrc = readFileSync(join(dir, 'confirm-dialog.tsx'), 'utf8');
const pageFilterBarSrc = readFileSync(join(dir, 'page-filter-bar.tsx'), 'utf8');

describe('ui barrel', () => {
  it('exports all 7 components from the subdomain barrel', () => {
    expect(barrelSrc).toContain("export { default as StatusBadge }");
    expect(barrelSrc).toContain("export { default as StatTile }");
    expect(barrelSrc).toContain("export { default as EmptyState }");
    expect(barrelSrc).toContain("export { default as DetailSection }");
    expect(barrelSrc).toContain("export { default as SectionCard }");
    expect(barrelSrc).toContain("export { default as PageFilterBar }");
    expect(barrelSrc).toContain("export { default as ConfirmDialog }");
  });

  it('keeps approved Orders/Show class constants for badge, tile, and card shells', () => {
    expect(STATUS_BADGE_BASE_CLASS).toBe('badge rounded-pill fw-bold px-3 py-2');
    expect(STATUS_BADGE_FALLBACK_COLOR_CLASS).toBe('badge-light-secondary');
    expect(STAT_TILE_SHELL_CLASS).toContain('bg-white rounded-3 p-4 border border-gray-100');
    expect(SECTION_CARD_BASE_CLASS).toBe('card border-0 shadow-sm rounded-4');
    expect(EMPTY_VALUE_FALLBACK).toBe('—');
  });

  it('StatusBadge stays domain-agnostic (no Order/Offer enum imports)', () => {
    expect(statusBadgeSrc).not.toMatch(/OrderStatus|OfferStatus|Guarantor/);
    expect(statusBadgeSrc).toContain('STATUS_BADGE_LIGHT_PREFIX');
  });

  it('ConfirmDialog is a Bootstrap Modal, not SweetAlert / ConfirmAction', () => {
    expect(confirmDialogSrc).toContain('react-bootstrap');
    expect(confirmDialogSrc).toContain('Modal');
    expect(confirmDialogSrc).not.toMatch(/sweetalert|ConfirmAction|swal/i);
  });

  it('PageFilterBar supports search, select, and date field types', () => {
    expect(pageFilterBarSrc).toContain("field.type === 'search'");
    expect(pageFilterBarSrc).toContain("field.type === 'select'");
    expect(pageFilterBarSrc).toContain("field.type === 'date'");
  });
});
