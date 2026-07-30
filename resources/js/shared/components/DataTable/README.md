# DataTable — unified list UI

Canonical list pattern for Admin / Provider index pages.
**One table format project-wide** — no card-mode toggle. Complex data lives in
smart column cells; full detail lives on a detail route via `onRowClick` (Show **or**
Edit — page decides).

Do **not** invent a second list layout (cards grid, dual render paths) alongside this.

## Contract

| Piece | Source |
| --- | --- |
| Markup | shadcn `@/shared/components/ui/table` |
| Pagination | Existing `@/shared/components/Table/partials/Pagination` (Laravel `meta.links`) |
| Money / dates | `@/shared/lib/formatters` via smart cells |
| Permissions | Gate actions with `visible: hasPermission(...)` from `usePermissions` |
| Delete confirm | `confirm: { type: 'swal' }` → shared `confirmWithSweetAlert` (same as ConfirmAction) |
| Row click | Optional `onRowClick` — Show, Edit, or omit |

## Minimal index page

```tsx
import { router } from '@inertiajs/react';
import {
  AvatarCell,
  CurrencyCell,
  DataTable,
  type DataTableColumn,
  DateCell,
  StatusBadgeCell,
} from '@/shared/components/DataTable';
import usePermissions from '@/shared/hooks/use-permissions';
import type { PaginationResource } from '@/shared/types';
import ExampleController from '@/actions/.../ExampleController';

type Row = {
  id: number;
  name: string;
  email: string;
  image?: string | null;
  amount: number;
  status: { label: string; color: string };
  created_at: string;
};

const columns: DataTableColumn<Row>[] = [
  {
    id: 'name',
    header: 'Name',
    cell: (row) => (
      <AvatarCell name={row.name} image={row.image} description={row.email} />
    ),
  },
  {
    id: 'status',
    header: 'Status',
    cell: (row) => (
      <StatusBadgeCell label={row.status.label} color={row.status.color} />
    ),
  },
  {
    id: 'amount',
    header: 'Amount',
    cell: (row) => <CurrencyCell value={row.amount} options={{ currencyLabel: 'SAR' }} />,
  },
  {
    id: 'created_at',
    header: 'Created',
    cell: (row) => <DateCell value={row.created_at} />,
    sortable: true,
  },
];

type Props = {
  rows: PaginationResource<Row>;
  prams: { search?: string } | null;
};

export default function Index({ rows, prams }: Props) {
  const { hasPermission } = usePermissions();
  const canEdit = hasPermission('edit examples');
  const canDelete = hasPermission('delete examples');

  return (
    <DataTable
      columns={columns}
      data={rows.data}
      pagination={rows.meta}
      searchable
      searchValue={prams?.search ?? ''}
      onSearch={(search) =>
        router.get(ExampleController.index().url, { search }, { preserveState: true })
      }
      // Enter-only by default (majority CRUD pattern). For live search:
      // searchDebounceMs={400}
      onRowClick={(row) => router.visit(ExampleController.show(row.id).url)}
      actions={() => [
        {
          id: 'edit',
          label: 'Edit',
          href: (row) => ExampleController.edit(row.id).url,
          visible: canEdit,
        },
        {
          id: 'delete',
          label: 'Delete',
          variant: 'destructive',
          visible: canDelete,
          confirm: { type: 'swal' },
          onSelect: (row) => router.delete(ExampleController.destroy(row.id).url),
        },
      ]}
    />
  );
}
```

## Row click patterns

`onRowClick` is generic — it does **not** assume a Show page exists.

```tsx
// A) Has Show — open detail (Orders / Guarantor style)
onRowClick={(row) => router.visit(ExampleController.show(row.id).url)}

// B) No Show (resource except show) — open Edit when permitted (Admins pilot)
onRowClick={
  canEdit
    ? (row) => router.visit(ExampleController.edit(row.id).url)
    : undefined
}

// C) Non-clickable rows — omit onRowClick (actions-only)
```

## Actions column auto-hide

Pass `actions` whenever the page *could* show actions. DataTable hides the Actions
header/cells when **every** resolved row has zero visible actions (permission gating).
You do **not** need `actions={canEdit || canDelete ? … : undefined}`.

## Confirm strategies

```tsx
// Project default for deletes (SweetAlert — same as ConfirmAction)
confirm: { type: 'swal' }
confirm: { type: 'swal', title: t('custom_title') } // optional title override

// Browser confirm
confirm: { type: 'browser', message: 'Are you sure?' }
```

Shared helper: `@/shared/lib/confirm-action` (`confirmWithSweetAlert`).

## Search default

| Behavior | Config |
| --- | --- |
| **Enter-only (default)** | `searchDebounceMs` omitted / `0` — matches ~25 legacy Table indexes |
| Live debounce | `searchDebounceMs={400}` — Guarantor / Opportunity style |

Enter always flushes immediately even when debounce is enabled.

## Smart cells

Compose **inside** `column.cell` — they never replace the table row:

| Cell | Use for |
| --- | --- |
| `AvatarCell` | User / requester identity (image + name + email/phone) |
| `StatusBadgeCell` | Order / Guarantor status or type badges (semantic `--success` / `--warning` / `--info` / `--primary`) |
| `CurrencyCell` | Amounts and budget ranges (`value` + optional `endValue`) |
| `DateCell` | `created_at` and other date-only fields |

## Notes

- Legacy `@/shared/components/Table` (Bootstrap) stays until pages migrate; **new** indexes should use `DataTable`.
- Filters beyond search (status select, date range) stay **outside** DataTable in the page toolbar.
- Empty list: pass `emptyState` for a custom message/icon; otherwise a default muted line is shown.
