import { useEffect, useMemo, useRef, useState, type KeyboardEvent, type MouseEvent } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronDown, Search } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import Pagination from '@/shared/components/Table/partials/Pagination';
import { Button } from '@/shared/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu';
import { Input } from '@/shared/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/shared/components/ui/table';
import { cn } from '@/shared/lib/utils';
import type {
  DataTableAction,
  DataTableColumn,
  DataTableProps,
  DataTableRow,
} from './types';

function resolveActions<T extends DataTableRow>(
  actions: DataTableProps<T>['actions'],
  row: T,
): DataTableAction<T>[] {
  if (!actions) {
    return [];
  }

  const list = typeof actions === 'function' ? actions(row) : actions;

  return list.filter((action) => {
    if (action.visible === undefined) {
      return true;
    }

    return typeof action.visible === 'function'
      ? action.visible(row)
      : action.visible;
  });
}

function defaultCellValue<T extends DataTableRow>(
  row: T,
  column: DataTableColumn<T>,
): string {
  if (!column.accessorKey) {
    return '';
  }

  const value = row[column.accessorKey];

  if (value === null || value === undefined) {
    return '';
  }

  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    return String(value);
  }

  return '';
}

function DataTableActionsMenu<T extends DataTableRow>({
  row,
  actions,
}: {
  row: T;
  actions: DataTableAction<T>[];
}) {
  const { t } = useTranslation();

  if (actions.length === 0) {
    return null;
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8"
          onClick={(event: MouseEvent) => event.stopPropagation()}
        >
          {t('actions')}
          <ChevronDown className="size-4 opacity-60" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" onClick={(event) => event.stopPropagation()}>
        {actions.map((action) => {
          const href =
            typeof action.href === 'function' ? action.href(row) : action.href;

          const runSelect = () => {
            if (!action.onSelect) {
              return;
            }

            if (action.confirmMessage) {
              if (window.confirm(action.confirmMessage)) {
                action.onSelect(row);
              }
              return;
            }

            action.onSelect(row);
          };

          if (href) {
            return (
              <DropdownMenuItem
                key={action.id}
                asChild
                className={cn(
                  action.variant === 'destructive' && 'text-destructive focus:text-destructive',
                )}
              >
                <Link href={href} onClick={(event) => event.stopPropagation()}>
                  {action.label}
                </Link>
              </DropdownMenuItem>
            );
          }

          return (
            <DropdownMenuItem
              key={action.id}
              className={cn(
                action.variant === 'destructive' && 'text-destructive focus:text-destructive',
              )}
              onSelect={(event) => {
                event.preventDefault();
                runSelect();
              }}
            >
              {action.label}
            </DropdownMenuItem>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

/**
 * Unified project DataTable — single table format, smart column cells,
 * details-on-click via `onRowClick`. No card-mode dual path.
 *
 * Pagination reuses `@/shared/components/Table/partials/Pagination` (Laravel meta links).
 */
export function DataTable<T extends DataTableRow>({
  columns,
  data,
  pagination,
  paginationOnly,
  searchable = false,
  searchValue = '',
  onSearch,
  searchDebounceMs = 400,
  searchPlaceholder = 'Search',
  onRowClick,
  actions,
  toolbar,
  emptyState,
  className,
  onSort,
  getRowId = (row) => row.id,
}: DataTableProps<T>) {
  const { t } = useTranslation();
  const [draftSearch, setDraftSearch] = useState(searchValue);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const showActionsColumn = Boolean(actions);

  useEffect(() => {
    setDraftSearch(searchValue);
  }, [searchValue]);

  useEffect(() => {
    if (!searchable || !onSearch || searchDebounceMs <= 0) {
      return;
    }

    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    debounceRef.current = setTimeout(() => {
      if (draftSearch !== searchValue) {
        onSearch(draftSearch);
      }
    }, searchDebounceMs);

    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, [draftSearch, searchable, onSearch, searchDebounceMs, searchValue]);

  const colSpan = useMemo(
    () => columns.length + (showActionsColumn ? 1 : 0),
    [columns.length, showActionsColumn],
  );

  const handleSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key !== 'Enter' || !onSearch) {
      return;
    }

    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    onSearch(event.currentTarget.value);
  };

  const defaultEmpty = (
    <div className="py-10 text-center text-sm text-muted-foreground">
      {t('no_matching_records_found', { defaultValue: 'No matching records found' })}
    </div>
  );

  return (
    <div className={cn('flex flex-col gap-4', className)}>
      {(searchable || toolbar) && (
        <div className="flex flex-wrap items-center justify-between gap-3">
          {searchable ? (
            <div className="relative w-full max-w-xs">
              <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                type="search"
                value={draftSearch}
                placeholder={searchPlaceholder}
                className="ps-9"
                onChange={(event) => setDraftSearch(event.target.value)}
                onKeyDown={handleSearchKeyDown}
              />
            </div>
          ) : (
            <div />
          )}
          {toolbar ? <div className="flex items-center gap-2">{toolbar}</div> : null}
        </div>
      )}

      <div className="rounded-md border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              {columns.map((column) => {
                const clickable = Boolean(column.sortable && onSort);

                return (
                  <TableHead
                    key={column.id}
                    className={cn(
                      clickable && 'cursor-pointer select-none hover:text-foreground',
                      column.headerClassName,
                    )}
                    onClick={
                      clickable
                        ? () => {
                            onSort?.(column.id);
                          }
                        : undefined
                    }
                  >
                    {column.header}
                  </TableHead>
                );
              })}
              {showActionsColumn ? (
                <TableHead className="text-end">{t('actions')}</TableHead>
              ) : null}
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={colSpan} className="h-24">
                  {emptyState ?? defaultEmpty}
                </TableCell>
              </TableRow>
            ) : (
              data.map((row) => {
                const rowActions = resolveActions(actions, row);
                const rowId = getRowId(row);

                return (
                  <TableRow
                    key={String(rowId)}
                    className={cn(onRowClick && 'cursor-pointer')}
                    onClick={
                      onRowClick
                        ? () => {
                            onRowClick(row);
                          }
                        : undefined
                    }
                  >
                    {columns.map((column) => (
                      <TableCell key={`${rowId}-${column.id}`} className={column.className}>
                        {column.cell
                          ? column.cell(row)
                          : defaultCellValue(row, column)}
                      </TableCell>
                    ))}
                    {showActionsColumn ? (
                      <TableCell
                        className="text-end"
                        onClick={(event) => event.stopPropagation()}
                      >
                        <DataTableActionsMenu row={row} actions={rowActions} />
                      </TableCell>
                    ) : null}
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>

      {pagination ? (
        <Pagination
          paginationMeta={pagination}
          preserveScroll
          only={paginationOnly}
        />
      ) : null}
    </div>
  );
}
