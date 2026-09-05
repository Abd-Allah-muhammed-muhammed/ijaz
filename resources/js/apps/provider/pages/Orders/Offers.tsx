import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageTitle } from '@/vendor/metronic/layout/core';
import { ToolbarWrapper } from '@/vendor/metronic/layout/components/toolbar';
import { Content } from '@/vendor/metronic/layout/components/content';
import { KTIcon } from '@/vendor/metronic/helpers';
import Pagination from '@/shared/components/Table/partials/Pagination';
import { PaginationResource } from '@/shared/types';
import { OrderOffer } from '@/shared/types/models';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import { Card, Col, Row } from 'react-bootstrap';
import { OfferStatusEnum } from '@/Enums/Order';
import { formatCurrency, formatDateTime } from '@/shared/lib/formatters';
import { getOfferStatusBadgeClass } from '@/apps/provider/pages/Orders/order-show-utils';
import {
  EmptyState,
  PageFilterBar,
  SectionCard,
  StatusBadge,
  type PageFilterField,
} from '@/shared/components/ui';
import { useOrderFilters } from '@/apps/provider/pages/Orders/hooks/use-order-filters';

type Props = {
  rows: PaginationResource<OrderOffer>;
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;
  status?: string;
};

const Offers = ({ rows, prams }: Props) => {
  const { t, i18n } = useTranslation();
  const currencyLabel = t('SAR');
  const { filters, onFilterChange } = useOrderFilters<SearchPrams>({
    prams,
    defaults: { per_page: 10, search: '' },
    url: OrderController.offers().url,
  });

  const filterFields: PageFilterField[] = [
    {
      name: 'search',
      type: 'search',
      value: filters.search,
      placeholder: t('search'),
    },
    {
      name: 'status',
      type: 'select',
      value: filters.status ?? '',
      options: [
        { value: '', label: t('all') },
        ...Object.values(OfferStatusEnum).map((status) => ({
          value: status,
          label: (t as (key: string) => string)(status),
        })),
      ],
    },
  ];

  return (
    <>
      <Head title={t('offers')} />
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
        {t('offers')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        <PageFilterBar
          filters={filterFields}
          onFilterChange={onFilterChange}
        />

        {rows.data.length === 0 ? (
          <SectionCard>
            <EmptyState
              icon={<KTIcon iconName="price-tag" className="fs-5x mb-5 text-gray-300" />}
              title={t('no_offers')}
            />
          </SectionCard>
        ) : (
          <Row>
            {rows.data.map((row) => {
              const orderTitle = row.order?.title?.trim() || t('Order ID');
              const offerBadge = getOfferStatusBadgeClass(row.status?.value);

              return (
                <Col key={row.id} xl={4} lg={6} md={6} sm={12} className="mb-6">
                  <Link
                    href={OrderController.show(row.order_id).url}
                    className="text-decoration-none"
                  >
                    <Card className="h-100 border-0 shadow-sm rounded-4 hover-elevate-up">
                      <Card.Body className="p-6 d-flex flex-column">
                        <div className="d-flex justify-content-between align-items-start gap-3 mb-4">
                          <h5
                            className="text-gray-900 fw-bolder mb-0 text-truncate lh-base"
                            title={row.order_id}
                          >
                            {orderTitle}
                          </h5>
                          <StatusBadge
                            label={row.status?.label}
                            colorClass={offerBadge}
                            className="flex-shrink-0"
                          />
                        </div>

                        {row.order?.user?.name && (
                          <div className="text-muted fs-7 mb-3 text-truncate" title={row.order.user.name}>
                            {row.order.user.name}
                          </div>
                        )}

                        <div className="mb-4">
                          <span className="text-gray-500 fs-8 fw-semibold d-block mb-1 text-uppercase">
                            {t('price')}
                          </span>
                          <span className="text-gray-900 fw-bolder fs-4">
                            {formatCurrency(row.price, {
                              locale: i18n.language,
                              currencyLabel,
                              maximumFractionDigits: 2,
                              minimumFractionDigits: 0,
                            })}
                          </span>
                        </div>

                        <div className="mt-auto d-flex justify-content-end">
                          <small className="text-muted">
                            {row.created_at ? formatDateTime(row.created_at, i18n.language) : '—'}
                          </small>
                        </div>
                      </Card.Body>
                    </Card>
                  </Link>
                </Col>
              );
            })}
          </Row>
        )}
        <Pagination paginationMeta={rows.meta} preserveScroll />
      </Content>
    </>
  );
};

Offers.layout = (page: React.ReactElement) => <ProviderLayout children={page} />;

export default Offers;
