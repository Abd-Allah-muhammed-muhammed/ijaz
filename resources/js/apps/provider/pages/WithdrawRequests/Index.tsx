import { useMemo, type ReactElement } from 'react';
import { useTranslation } from 'react-i18next';
import { Head, router } from '@inertiajs/react';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import WithdrawController from '@/actions/Modules/Wallet/Http/Controllers/Provider/WithdrawController';
import { WithdrawTrigger } from '@/apps/provider/components/wallet/WalletQuickActions';
import { OperationStatusEnum } from '@/Enums/Enums';
import {
  STATEMENT_PAGE_SIZE,
  formatListDate,
  formatShortReference,
} from '@/shared/lib/formatters';
import Pagination from '@/shared/components/Table/partials/Pagination';
import ConfirmAction from '@/shared/components/Table/partials/confirm-action';
import {
  ActionCell,
  LinkAction,
} from '@/shared/components/Table';
import {
  EmptyState,
  PageFilterBar,
  StackedDataTable,
  StatusBadge,
  type PageFilterField,
  type StackedDataTableColumn,
} from '@/shared/components/ui';
import type { PaginationResource } from '@/shared/types';
import type { WithdrawRequest } from '@/shared/types/models';

type SearchParams = {
  per_page: number;
  search: string;
};

export type WithdrawIndexProps = {
  rows: PaginationResource<WithdrawRequest>;
  prams: SearchParams | null;
};

function WithdrawRowActions({ row }: { row: WithdrawRequest }) {
  const { t } = useTranslation();

  return (
    <ActionCell>
      <div className="menu-item px-3">
        <LinkAction
          href={WithdrawController.show(row.id as string).url}
          title={t('show')}
        />
      </div>
      {row.status.value === OperationStatusEnum.Pending ? (
        <div className="menu-item px-3">
          <ConfirmAction
            callback={() => {
              router.delete(WithdrawController.destroy(String(row.id)).url);
            }}
            title={t('delete')}
          />
        </div>
      ) : null}
    </ActionCell>
  );
}

function WithdrawStatusBadges({ row }: { row: WithdrawRequest }) {
  const { t } = useTranslation();

  return (
    <span className="d-flex flex-column align-items-start gap-1">
      <span className="d-flex align-items-center gap-1 flex-wrap">
        <span className="text-muted fs-9 fw-semibold text-nowrap">
          {t('status')}:
        </span>
        <StatusBadge status={row.status} className="fs-8" />
      </span>
      <span className="d-flex align-items-center gap-1 flex-wrap">
        <span className="text-muted fs-9 fw-semibold text-nowrap">
          {t('transfer_status')}:
        </span>
        <StatusBadge status={row.transfer_status} className="fs-8" />
      </span>
    </span>
  );
}

const Index = ({ rows, prams }: WithdrawIndexProps) => {
  const { t, i18n } = useTranslation();
  const searchParams: SearchParams = prams || {
    per_page: STATEMENT_PAGE_SIZE,
    search: '',
  };

  const searchParamsChanged = (name: keyof SearchParams, value: string | number) => {
    if (value) {
      searchParams[name] = value as never;
    } else {
      delete searchParams[name];
    }
    router.reload({
      only: ['rows'],
      data: searchParams,
    });
  };

  const filters: PageFilterField[] = [
    {
      name: 'search',
      type: 'search',
      value: prams?.search || '',
      placeholder: t('search'),
    },
  ];

  const columns: StackedDataTableColumn<WithdrawRequest>[] = useMemo(
    () => [
      {
        id: 'reference',
        header: t('reference'),
        grow: true,
        mobile: 'title',
        cell: (row) => {
          const fullId = String(row.id);
          const shortRef = formatShortReference(fullId);

          return (
            <span
              className="font-monospace fw-semibold text-gray-800"
              title={fullId}
            >
              #{shortRef}
            </span>
          );
        },
      },
      {
        id: 'amount',
        header: t('amount'),
        widthClassName: 'w-125px',
        mobile: 'meta',
        cell: (row) => (
          <span className="fw-bold text-gray-800">{row.amount}</span>
        ),
      },
      {
        id: 'status',
        header: t('status'),
        widthClassName: 'w-225px',
        mobile: 'badge',
        cell: (row) => <WithdrawStatusBadges row={row} />,
      },
      {
        id: 'date',
        header: t('date'),
        widthClassName: 'w-125px',
        mobile: 'meta',
        cell: (row) => formatListDate(row.created_at, i18n.language),
      },
      {
        id: 'actions',
        header: t('actions'),
        widthClassName: 'w-125px',
        align: 'end',
        cell: (row) => <WithdrawRowActions row={row} />,
      },
    ],
    [t, i18n.language],
  );

  return (
    <>
      <Head title={t('withdraw_requests')} />
      <PageTitle
        breadcrumbs={[
          {
            title: '',
            path: '',
            isSeparator: true,
            isActive: false,
          },
        ]}
      >
        {t('withdraw_requests')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 mb-4">
          <div className="flex-grow-1 min-w-0">
            <PageFilterBar
              filters={filters}
              onFilterChange={(name, value) => {
                if (name === 'search') {
                  searchParamsChanged('search', value);
                }
              }}
              className="mb-0"
            />
          </div>
          <WithdrawTrigger reloadOnly={['rows']} className="me-0 align-self-md-end" />
        </div>
        <StackedDataTable
          rows={rows.data}
          columns={columns}
          getRowKey={(row) => row.id}
          mobileTrailing={(row) => <WithdrawRowActions row={row} />}
          emptyState={
            <EmptyState title={t('no_matching_records_found')} compact />
          }
        />
        <div className="mt-4">
          <Pagination paginationMeta={rows.meta} only={['rows']} preserveScroll />
        </div>
      </Content>
    </>
  );
};

Index.layout = (page: ReactElement) => <ProviderLayout>{page}</ProviderLayout>;

export default Index;
