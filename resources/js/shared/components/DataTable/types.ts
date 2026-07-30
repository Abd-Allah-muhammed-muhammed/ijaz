import type { ReactNode } from 'react';
import type { PaginationMeta } from '@/shared/types';

/**
 * Minimum row shape — every list resource in this app has an `id`.
 * Prefer extending your model type (Admin, Order, …) rather than inventing a parallel shape.
 */
export type DataTableRow = {
  id: string | number;
};

export type DataTableColumn<T extends DataTableRow> = {
  /** Stable column id (also used as React key). */
  id: string;
  header: ReactNode;
  /**
   * Default text cell: reads `row[accessorKey]`.
   * Prefer `cell` for anything non-scalar (avatar, badge, currency).
   */
  accessorKey?: keyof T & string;
  /** Custom cell renderer — compose smart cells here. */
  cell?: (row: T) => ReactNode;
  /**
   * When true and `onSort` is provided on DataTable, the header is clickable.
   * Sorting itself is server-driven (Inertia query) — DataTable only emits the column id.
   */
  sortable?: boolean;
  className?: string;
  headerClassName?: string;
};

export type DataTableActionVariant = 'default' | 'destructive';

/**
 * Confirm before running `onSelect`.
 * - `swal` — project SweetAlert (same config as ConfirmAction); `title` defaults to `t('are_you_sure')`
 * - `browser` — `window.confirm(message)`
 */
export type DataTableActionConfirm =
  | {
      type: 'swal';
      /** SweetAlert `title`. Defaults to translated `are_you_sure`. */
      title?: string;
    }
  | {
      type: 'browser';
      message: string;
    };

/**
 * Row-level action. Gate with `visible` (boolean or per-row callback) —
 * typically `visible: hasPermission('edit admins')`.
 */
export type DataTableAction<T extends DataTableRow> = {
  id: string;
  label: ReactNode;
  href?: string | ((row: T) => string);
  onSelect?: (row: T) => void;
  visible?: boolean | ((row: T) => boolean);
  variant?: DataTableActionVariant;
  /** Prefer this over legacy `confirmMessage`. */
  confirm?: DataTableActionConfirm;
  /**
   * @deprecated Use `confirm: { type: 'browser', message }` instead.
   */
  confirmMessage?: string;
};

export type DataTableEmptyState = ReactNode;

export type DataTableProps<T extends DataTableRow> = {
  columns: DataTableColumn<T>[];
  /** Current page rows (usually `resource.data`). */
  data: T[];
  /** Laravel pagination meta — drives the shared Pagination partial. */
  pagination?: PaginationMeta;
  /** Inertia `only` for pagination visits. */
  paginationOnly?: string[];
  searchable?: boolean;
  searchValue?: string;
  onSearch?: (value: string) => void;
  /**
   * Debounce for live search. Default `0` = Enter-only (majority of CRUD indexes /
   * legacy Table). Pass `400` (or similar) for Guarantor/Opportunity-style live search.
   */
  searchDebounceMs?: number;
  searchPlaceholder?: string;
  /**
   * Optional row navigation — destination is page-defined (Show, Edit, or omit).
   * Actions cell stops propagation so edit/delete do not trigger this.
   */
  onRowClick?: (row: T) => void;
  /**
   * Row actions. Prefer a function so permission / per-row visibility stays local:
   * `actions={(row) => [{ id: 'edit', label: t('edit'), href: …, visible: canEdit }]}`
   *
   * The Actions column is omitted automatically when every resolved row has zero visible actions.
   */
  actions?: DataTableAction<T>[] | ((row: T) => DataTableAction<T>[]);
  /** Right-side toolbar (e.g. Create button). */
  toolbar?: ReactNode;
  emptyState?: DataTableEmptyState;
  className?: string;
  onSort?: (columnId: string) => void;
  getRowId?: (row: T) => string | number;
};
