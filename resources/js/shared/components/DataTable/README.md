# DataTable — unified list UI

Canonical list pattern for Admin / Provider index pages.
**One table format project-wide** — no card-mode toggle. Complex data lives in
smart column cells; full detail lives on the record Show page via `onRowClick`.

Do **not** invent a second list layout (cards grid, dual render paths) alongside this.

## Contract

| Piece | Source |
| --- | --- |
| Markup | shadcn `@/shared/components/ui/table` |
| Pagination | Existing `@/shared/components/Table/partials/Pagination` (Laravel `meta.links`) |
| Money / dates | `@/shared/lib/formatters` via smart cells |
| Permissions | Gate actions with `visible: hasPermission(...)` from `usePermissions` |
| Detail | `onRowClick` → Inertia visit to Show route |

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
      onRowClick={(row) => router.visit(ExampleController.show(row.id).url)}
      onSort={(columnId) =>
        router.get(ExampleController.index().url, { sort: columnId }, { preserveState: true })
      }
      actions={(row) => [
        {
          id: 'edit',
          label: 'Edit',
          href: ExampleController.edit(row.id).url,
          visible: hasPermission('edit examples'),
        },
        {
          id: 'delete',
          label: 'Delete',
          variant: 'destructive',
          confirmMessage: 'Are you sure?',
          visible: hasPermission('delete examples'),
          onSelect: () => router.delete(ExampleController.destroy(row.id).url),
        },
      ]}
    />
  );
}
```

## Smart cells

Compose **inside** `column.cell` — they never replace the table row:

| Cell | Use for |
| --- | --- |
| `AvatarCell` | User / requester identity (image + name + email/phone) |
| `StatusBadgeCell` | Order / Guarantor status or type badges |
| `CurrencyCell` | Amounts and budget ranges (`value` + optional `endValue`) |
| `DateCell` | `created_at` and other date-only fields |

## Notes

- Legacy `@/shared/components/Table` (Bootstrap) stays until pages migrate; **new** indexes should use `DataTable`.
- `LinkAction` / `ConfirmAction` from the old Table remain available for unmigrated pages; prefer `actions` + `confirmMessage` on DataTable for new work.
- Filters beyond search (status select, date range) stay **outside** DataTable in the page toolbar — same as Orders/Guarantor filter bars today.
- Empty list: pass `emptyState` for a custom message/icon; otherwise a default muted line is shown.
