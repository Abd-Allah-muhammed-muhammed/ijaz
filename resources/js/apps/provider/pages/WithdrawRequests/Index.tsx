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
import { formatListDate } from '@/shared/lib/formatters';
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

const Index = ({ rows, prams }: WithdrawIndexProps) => {
  const { t, i18n } = useTranslation();
  const searchParams: SearchParams = prams || {
    per_page: 10,
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
        cell: (row) => (
          <span className="font-monospace fw-semibold text-gray-800">
            #{row.id}
          </span>
        ),
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
        widthClassName: 'w-200px',
        mobile: 'badge',
        cell: (row) => (
          <span className="d-flex flex-wrap align-items-center gap-1">
            <StatusBadge status={row.status} className="fs-8" />
            <StatusBadge status={row.transfer_status} className="fs-8" />
          </span>
        ),
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
        <StackedDataTable
          rows={rows.data}
          columns={columns}
          getRowKey={(row) => row.id}
          mobileTrailing={(row) => <WithdrawRowActions row={row} />}
          toolbar={
            <div className="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
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
          }
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
