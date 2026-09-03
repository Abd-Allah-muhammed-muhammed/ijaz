import type { ReactNode } from 'react';

/** Shared empty-value placeholder used by tiles and detail rows. */
export const EMPTY_VALUE_FALLBACK = '—';

/** Metronic status-pill base classes (Orders/Show approved language). */
export const STATUS_BADGE_BASE_CLASS = 'badge rounded-pill fw-bold px-3 py-2';

/** Fallback when no color / colorClass is provided. */
export const STATUS_BADGE_FALLBACK_COLOR_CLASS = 'badge-light-secondary';

/** Prefix for semantic Metronic color tokens (`success` → `badge-light-success`). */
export const STATUS_BADGE_LIGHT_PREFIX = 'badge-light-';

export const STAT_TILE_SHELL_CLASS =
  'bg-white rounded-3 p-4 border border-gray-100 h-100';

export const STAT_TILE_LABEL_CLASS = 'text-muted fs-8 text-uppercase fw-bold mb-1';

export const STAT_TILE_VALUE_CLASS = 'fs-5 fw-bolder text-gray-900';

export const DETAIL_SECTION_LABEL_CLASS =
  'text-muted fs-8 text-uppercase fw-bold mb-2';

export const SECTION_CARD_BASE_CLASS = 'card border-0 shadow-sm rounded-4';

export const SECTION_CARD_HEADER_CLASS =
  'card-header border-0 pt-6 px-6 px-lg-8 bg-transparent d-flex align-items-center justify-content-between gap-3';

export const SECTION_CARD_BODY_CLASS = 'card-body pt-0 px-6 px-lg-8 pb-6';

export const SECTION_CARD_TITLE_CLASS = 'fw-bolder text-gray-900 mb-0';

/** Default tinted body for `variant="hero"` (Orders/Show approved language). */
export const SECTION_CARD_HERO_BODY_CLASS =
  'card-body p-6 p-lg-8 bg-light-primary bg-opacity-10';

/** Optional trailing body under the hero tint (e.g. description strip). */
export const SECTION_CARD_HERO_FOOTER_CLASS =
  'card-body p-6 p-lg-8 bg-white border-top border-gray-100';

export const EMPTY_STATE_DEFAULT_PADDING_CLASS = 'text-center py-12 px-4';

export const EMPTY_STATE_COMPACT_PADDING_CLASS = 'text-center py-10';

export const EMPTY_STATE_TITLE_CLASS = 'fw-bold text-gray-800 mb-2';

export const EMPTY_STATE_DESCRIPTION_CLASS = 'text-muted fs-6 mb-5';

export const EMPTY_STATE_COMPACT_TITLE_CLASS = 'text-muted fw-semibold fs-6 mb-1';

export const EMPTY_STATE_COMPACT_DESCRIPTION_CLASS = 'text-muted fs-7 mb-0';

export const PAGE_FILTER_BAR_CLASS = 'd-flex flex-wrap flex-stack mb-6';

/** Visual-only search column wrapper (was an invalid `<h3>` in the source pages). */
export const PAGE_FILTER_SEARCH_COLUMN_CLASS = 'fw-bolder my-2';

export const PAGE_FILTER_SEARCH_ICON_CLASS = 'fs-1 position-absolute ms-6';

export const PAGE_FILTER_SEARCH_INPUT_CLASS = 'form-control  ps-14';

export const PAGE_FILTER_SELECT_CLASS = 'form-select form-select-white form-select-sm';

export const PAGE_FILTER_DATE_CLASS = 'form-control form-control-white form-control-sm';

export const PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS = 'w-200px';

export const PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS = 'w-150px';

/**
 * Generic labeled status payload — matches EnumWithColors shape from shared models
 * without coupling to Order/Offer/Guarantor enums.
 */
export type StatusBadgeStatus = {
  label: string;
  color?: string | null;
};

export type StatusBadgeProps = {
  /** Prefer when the API already returned `{ label, color }`. */
  status?: StatusBadgeStatus | null;
  /** Explicit label when not using `status`. */
  label?: string;
  /**
   * Semantic Metronic color token (`success`, `danger`, …).
   * Becomes `badge-light-{color}` unless `colorClass` is set.
   */
  color?: string | null;
  /**
   * Full Metronic badge color class (e.g. `badge-light-primary`).
   * Wins over `color` / `status.color` when provided — use for domain maps
   * that do not mirror the API `color` field 1:1.
   */
  colorClass?: string | null;
  className?: string;
};

export type StatTileProps = {
  label: string;
  value: ReactNode;
  icon?: ReactNode;
  className?: string;
};

export type EmptyStateProps = {
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
  /** Tighter padding / muted title (attachments sidebar). */
  compact?: boolean;
  className?: string;
};

export type DetailSectionProps = {
  label: string;
  /** Prefer for free-form body markup. */
  children?: ReactNode;
  /** Simple text/node value when `children` is omitted. */
  value?: ReactNode;
  emptyFallback?: string;
  className?: string;
};

export type SectionCardVariant = 'default' | 'hero';

export type SectionCardProps = {
  children: ReactNode;
  /** Simple string/node title rendered as an h3 when `header` is omitted. */
  title?: ReactNode;
  /** Full header override (replaces title + headerExtra layout). */
  header?: ReactNode;
  /** Actions / badges aligned opposite the title. */
  headerExtra?: ReactNode;
  /**
   * Optional trailing body under the hero tint (e.g. description strip).
   * Only rendered when `variant="hero"`.
   */
  footer?: ReactNode;
  variant?: SectionCardVariant;
  className?: string;
  bodyClassName?: string;
  headerClassName?: string;
  footerClassName?: string;
};

export type PageFilterFieldType = 'search' | 'select' | 'date';

export type PageFilterOption = {
  value: string;
  label: string;
};

export type PageFilterField = {
  name: string;
  type: PageFilterFieldType;
  value?: string;
  placeholder?: string;
  options?: readonly PageFilterOption[];
  /** e.g. `w-200px` / `w-150px` */
  widthClassName?: string;
  className?: string;
};

export type PageFilterBarProps = {
  filters: readonly PageFilterField[];
  onFilterChange: (name: string, value: string) => void;
  className?: string;
};

export type ConfirmDialogConfirmVariant = 'danger' | 'primary';

export type ConfirmDialogProps = {
  show: boolean;
  title: string;
  message: ReactNode;
  confirmLabel: string;
  cancelLabel: string;
  onConfirm: () => void;
  onCancel: () => void;
  confirmVariant?: ConfirmDialogConfirmVariant;
  loading?: boolean;
  centered?: boolean;
};
