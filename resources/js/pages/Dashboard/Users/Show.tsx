import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import {Content} from "@/_metronic/layout/components/content";
import {router} from "@inertiajs/react";
import { User, WalletTransaction } from '@/types/models';
import { Card } from 'react-bootstrap';
import Table from '@/components/Table';
import ShowLayout from '@/pages/Dashboard/Users/ShowLayout';
import { PaginationResource } from '@/types';


type Props = {
  transactions: PaginationResource<WalletTransaction>,
  user: User,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;

};
const Show = ({transactions, user, prams }: Props) => {
  const { t } = useTranslation();
  const searchPrams: SearchPrams = prams || {
    per_page: 10,
    search: '',
  };
  const searchPramsChanged = (name: keyof SearchPrams, value: string | number) => {
    if (value) {
      searchPrams[name] = value as never;
    } else {
      delete searchPrams[name];
    }
    router.reload<SearchPrams>({
      only: ['transactions'],
      data: searchPrams,
      // @ts-ignore
      preserveState: true,
      preserveScroll: true,
      replace: true
    });
  };

  return (
    <>
      <Content>
        <Card>
          <Card.Body>
            <Table<WalletTransaction>
              name={"transactions"}
              rows={transactions}
              search={{
                value: '',
                callback: (value) => {
                  searchPramsChanged('search', value);
                },
              }}
              only={[
                'transactions'
              ]}
              headers={[
                {
                  title: t('reference_number'),
                  property: "id",
                  render: (row) => `#${row.id}`,
                },
                {
                  title: t('operation'),
                  property: "operation_type",
                  render: (row) => `${row.operation_type}(#${row.operation_id})`,
                },
                {
                  title: t('credit'),
                  property: "credit"
                },
                {
                  title: t('debit'),
                  property: "debit"
                },
                {
                  title: t('pending_credit'),
                  property: "pending_credit"
                },
                {
                  title: t('pending_debit'),
                  property: "pending_debit"
                },
                {
                  title: t('balance_before'),
                  property: "balance_before"
                },
                {
                  title: t('balance_after'),
                  property: "balance_after"
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
Show.layout = (page: any) => (
  <MasterLayout>
    <ShowLayout {...page.props}>
      {page}
    </ShowLayout>
  </MasterLayout>
);

export default Show
