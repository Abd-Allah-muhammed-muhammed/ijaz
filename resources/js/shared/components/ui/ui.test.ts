import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  EMPTY_VALUE_FALLBACK,
  PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS,
  PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS,
  SECTION_CARD_BASE_CLASS,
  SECTION_CARD_HERO_BODY_CLASS,
  STATUS_BADGE_BASE_CLASS,
  STATUS_BADGE_FALLBACK_COLOR_CLASS,
  STAT_TILE_SHELL_CLASS,
} from './types';

const dir = join(__dirname);
const barrelSrc = readFileSync(join(dir, 'index.ts'), 'utf8');
const statusBadgeSrc = readFileSync(join(dir, 'status-badge.tsx'), 'utf8');
const confirmDialogSrc = readFileSync(join(dir, 'confirm-dialog.tsx'), 'utf8');
const pageFilterBarSrc = readFileSync(join(dir, 'page-filter-bar.tsx'), 'utf8');
const sectionCardSrc = readFileSync(join(dir, 'section-card.tsx'), 'utf8');

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

  it('PageFilterBar does not wrap search inputs in a heading element', () => {
    expect(pageFilterBarSrc).toContain('PAGE_FILTER_SEARCH_COLUMN_CLASS');
    expect(pageFilterBarSrc).not.toMatch(/<h3[\s\S]*SearchFilterField/);
  });

  it('PageFilterBar uses named width constants instead of inline magic strings', () => {
    expect(PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS).toBe('w-200px');
    expect(PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS).toBe('w-150px');
    expect(pageFilterBarSrc).toContain('PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS');
    expect(pageFilterBarSrc).toContain('PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS');
    expect(pageFilterBarSrc).not.toContain("'w-200px'");
    expect(pageFilterBarSrc).not.toContain("'w-150px'");
    expect(barrelSrc).toContain('PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS');
    expect(barrelSrc).toContain('PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS');
  });

  it('SectionCard hero variant wraps children in the tinted body shell', () => {
    expect(SECTION_CARD_HERO_BODY_CLASS).toContain('bg-light-primary');
    expect(sectionCardSrc).toContain('SECTION_CARD_HERO_BODY_CLASS');
    expect(sectionCardSrc).toContain('SECTION_CARD_HERO_FOOTER_CLASS');
    expect(sectionCardSrc).not.toMatch(
      /if \(variant === 'hero'\) \{\s*return <div className=\{rootClass\}>\{children\}<\/div>/,
    );
  });
});
