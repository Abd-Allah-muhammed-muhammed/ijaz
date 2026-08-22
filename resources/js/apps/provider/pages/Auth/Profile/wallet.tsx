import {ReactElement, useState} from "react";
import AccountLayout from '@/apps/provider/layouts/AccountLayout'
import {Content} from "@/vendor/metronic/layout/components/content";
import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";
import {Provider, WalletTransaction} from "@/shared/types/models";
import {Head} from "@inertiajs/react";
import {Card} from "react-bootstrap";
import {PaginationResource} from "@/shared/types";
import Table from "@/shared/components/Table";
import { useTranslation } from 'react-i18next';
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import {applyFilterParam, visitWithFilters} from '@/shared/lib/filters';

type Props = {
  transactions: PaginationResource<WalletTransaction>,
  provider: Provider,
  prams: SearchPrams | null;
}

type SearchPrams = {
  per_page: number;
  search: string;
};

const Wallet = ({transactions, prams}: Props) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
  };
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    const next = applyFilterParam(
      { ...searchPrams } as Record<string, unknown>,
      name,
      value,
    );
    visitWithFilters(AuthController.statements().url, next, { only: ['transactions', 'prams'] });
  };
  return (
    <>
      <Head title={t('wallet')}/>
      <Content>
        <Card className="overflow-hidden">
          <Card.Body className="table-responsive">
            <Table<WalletTransaction>
              name={"transactions"}
              rows={transactions}
              search={{
                value: prams?.search || '',
                callback: (value) => {
                  searchPramsChanged('search', value);
                },
              }}
              only={[
                'transactions',
                'prams',
              ]}
              headers={[
                {
                  title: t('operation'),
                  property: "description",
                  render: (row) => (
                    <span className="text-gray-800">{row.description || row.operation_type}</span>
                  ),
                },
                {
                  title: t('reference'),
                  property: "reference_short",
                  render: (row) => (
                    <span
                      className="font-monospace text-gray-700"
                      title={String(row.id)}
                    >
                      {row.reference_short ?? String(row.id).slice(-8).toUpperCase()}
                    </span>
                  ),
                },
                {
                  title: t('amount'),
                  property: "amount",
                  render: (row) => {
                    const amount = Number(row.amount) || 0
                    const isPending = Boolean(row.is_pending)

                    if (isPending) {
                      return (
                        <span className="d-flex align-items-center gap-2 flex-wrap">
                          <span className="fw-bold text-gray-500">{amount.toFixed(2)}</span>
                          <span className="badge badge-light-warning fs-8">{t('pending')}</span>
                        </span>
                      )
                    }

                    const isCredit = Boolean(row.is_credit)

                    return (
                      <span className={`fw-bold ${isCredit ? 'text-success' : 'text-gray-800'}`}>
                        {isCredit ? `+${amount.toFixed(2)}` : `-${amount.toFixed(2)}`}
                      </span>
                    )
                  },
                },
                {
                  title: t('balance_after'),
                  property: "balance_after",
                },
                {
                  title: t('status'),
                  property: 'transfer_status',
                  render: (row) => (
                    row.transfer_status
                      ? <span className={`badge badge-light-${row.transfer_status.color}`}>{row.transfer_status.label}</span>
                      : <span className="text-muted">—</span>
                  )
                },
                {
                  title: t('date'),
                  property: "created_at",
                  render: (row) => new Date(row.created_at).toLocaleDateString() + ' ' + new Date(row.created_at).toLocaleTimeString(),
                }
              ]}/>
          </Card.Body>
        </Card>
      </Content>
    </>
  );
}


Wallet.layout = (page: ReactElement) => {

  return (
    <ProviderLayout>
      {/* @ts-ignore */}
      <AccountLayout {...page.props}>
        {page}
      </AccountLayout>
    </ProviderLayout>

  )
}

export default Wallet
