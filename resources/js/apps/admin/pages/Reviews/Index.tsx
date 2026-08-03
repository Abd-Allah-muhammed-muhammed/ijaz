import { useTranslation } from 'react-i18next';
import MasterLayout from "@/vendor/metronic/layout/MasterLayout";
import {PageTitle} from "@/vendor/metronic/layout/core";
import {ToolbarWrapper} from "@/vendor/metronic/layout/components/toolbar";
import {Content} from "@/vendor/metronic/layout/components/content";
import {Head, router} from "@inertiajs/react";
import {KTCard} from "@/vendor/metronic/helpers";
import Table from "@/shared/components/Table";
import usePermissions from '@/shared/hooks/use-permissions';
import {PaginationResource} from "@/shared/types";
import {Review} from "@/shared/types/models";
import ConfirmAction from "@/shared/components/Table/partials/confirm-action";
import {ReactElement} from "react";
import ReviewController from "@/actions/Modules/Reviews/Http/Controllers/Dashboard/ReviewController";


type Props = {
  rows: PaginationResource<Review>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  rating?: number | string;
  reviewer_type?: string;
  reviewee_type?: string;
};
const Index = (
  {
    rows,
    prams,
  }: Props
) => {
  const { t } = useTranslation();
  const { hasPermission } = usePermissions();
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
    router.get(ReviewController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('reviews')}/>
      <PageTitle breadcrumbs={[
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('reviews')}
      </PageTitle>
      <ToolbarWrapper/>
      <Content>
        <KTCard>
          <Table
            <Review>
            name='reviews'
            rows={rows}
            search={{
              value: prams?.search || '',
              callback: (value) => {
                searchPramsChanged('search', value);
              },
            }}
            headers={[
              {
                title: t('reviewer'),
                property: 'reviewer',
                render: (row) => (
                  <span>
                    {row.reviewer?.name || '—'}
                    {row.reviewer_type ? ` (${row.reviewer_type})` : ''}
                  </span>
                ),
              },
              {
                title: t('reviewee'),
                property: 'reviewee',
                render: (row) => (
                  <span>
                    {row.reviewee?.name || '—'}
                    {row.reviewee_type ? ` (${row.reviewee_type})` : ''}
                  </span>
                ),
              },
              {
                title: t('rating'),
                property: 'rating',
              },
              {
                title: t('comment'),
                property: 'comment',
              },
              {
                title: t('created_at'),
                property: 'created_at',
              },
            ]}
            actions={[
              {
                show: hasPermission('delete reviews'),
                ele: (row) => (
                  <ConfirmAction
                    key={`delete-review-${row.id}`}
                    callback={() => {
                      router.delete(ReviewController.destroy(row.id as number).url)
                    }}
                    title={t('delete')}
                  />
                ),
              },
            ]}
          />
        </KTCard>
      </Content>
    </>
  );
}

Index.layout = (page: ReactElement) => <MasterLayout children={page}/>;

export default Index;
