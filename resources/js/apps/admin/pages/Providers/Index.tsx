import { useTranslation } from 'react-i18next';
import MasterLayout from "@/apps/admin/layouts/MasterLayout";
import { PageTitle } from "@/vendor/metronic/layout/core";
import { ToolbarWrapper } from "@/vendor/metronic/layout/components/toolbar";
import { Content } from "@/vendor/metronic/layout/components/content";
import { Head, Link, router } from "@inertiajs/react";
import { PaginationResource } from "@/shared/types";
import { Provider } from "@/shared/types/models";

import Pagination from "@/shared/components/Table/partials/Pagination";
import ProviderCard from "@/shared/components/provider/ProviderCard";
import { KTIcon } from "@/vendor/metronic/helpers";
import ProviderController from "@/actions/App/Http/Controllers/Dashboard/ProviderController";


type Props = {
  rows: PaginationResource<Provider>,
  prams: SearchPrams | null;
  stats: {
    total: number;
    approved: number;
    pending: number;
    blocked: number;
  };
};

type SearchPrams = {
  per_page: number;
  search: string;

};
const Index = (
  {
    rows,
    prams,
    stats,
  }: Props
) => {
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
    router.get(ProviderController.index().url, searchPrams);
  };
  return (
    <>
      <Head title={t('providers')} />
      <PageTitle breadcrumbs={[
        {
          title: '',
          path: '',
          isSeparator: true,
          isActive: false,
        },
      ]}>
        {t('providers')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        {/* Statistics Cards — totals from backend statusCounts (not page-scoped rows.data) */}
        <div className='row g-6 g-xl-9 mb-6'>
          <div className='col-md-6 col-lg-4 col-xl-3'>
            <div className='card h-100 border-0 shadow-sm'>
              <div className='card-body d-flex flex-column p-7'>
                <div className='d-flex align-items-center mb-2'>
                  <div className='symbol symbol-40px me-3'>
                    <div className='symbol-label bg-light-primary'>
                      <KTIcon iconName='profile-user' className='fs-2 text-primary' />
                    </div>
                  </div>
                  <div className='d-flex flex-column'>
                    <span className='fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2'>{stats.total}</span>
                    <span className='text-muted fw-semibold fs-7'>{t('total_providers')}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className='col-md-6 col-lg-4 col-xl-3'>
            <div className='card h-100 border-0 shadow-sm'>
              <div className='card-body d-flex flex-column p-7'>
                <div className='d-flex align-items-center mb-2'>
                  <div className='symbol symbol-40px me-3'>
                    <div className='symbol-label bg-light-success'>
                      <KTIcon iconName='check-circle' className='fs-2 text-success' />
                    </div>
                  </div>
                  <div className='d-flex flex-column'>
                    <span className='fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2'>
                      {stats.approved}
                    </span>
                    <span className='text-muted fw-semibold fs-7'>{t('active_providers')}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className='col-md-6 col-lg-4 col-xl-3'>
            <div className='card h-100 border-0 shadow-sm'>
              <div className='card-body d-flex flex-column p-7'>
                <div className='d-flex align-items-center mb-2'>
                  <div className='symbol symbol-40px me-3'>
                    <div className='symbol-label bg-light-warning'>
                      <KTIcon iconName='loading' className='fs-2 text-warning' />
                    </div>
                  </div>
                  <div className='d-flex flex-column'>
                    <span className='fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2'>
                      {stats.pending}
                    </span>
                    <span className='text-muted fw-semibold fs-7'>{t('pending_approval')}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className='col-md-6 col-lg-4 col-xl-3'>
            <div className='card h-100 border-0 shadow-sm'>
              <div className='card-body d-flex flex-column p-7'>
                <div className='d-flex align-items-center mb-2'>
                  <div className='symbol symbol-40px me-3'>
                    <div className='symbol-label bg-light-danger'>
                      <KTIcon iconName='cross-circle' className='fs-2 text-danger' />
                    </div>
                  </div>
                  <div className='d-flex flex-column'>
                    <span className='fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2'>
                      {stats.blocked}
                    </span>
                    <span className='text-muted fw-semibold fs-7'>{t('blocked_providers')}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className='d-flex flex-wrap flex-stack mb-6'>
          <h3 className='fw-bolder my-2'>
            {t('providers')}
            <span className='fs-6 text-gray-400 fw-bold ms-1'>({stats.total})</span>
          </h3>

          <div className='d-flex align-items-center my-2'>
            <div className='d-flex align-items-center position-relative me-4'>
              <KTIcon iconName='magnifier' className='fs-3 position-absolute ms-3' />
              <input
                type='text'
                defaultValue={searchPrams.search}
                className='form-control form-control-white form-control-sm w-250px ps-10'
                placeholder={t('search_providers')}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    searchPramsChanged('search', e.currentTarget.value)
                  }
                }}
              />
            </div>

            <Link
              href={ProviderController.create().url}
              className="btn btn-primary btn-sm"
            >
              <KTIcon iconName='plus' className='fs-2' />
              {t('add_provider')}
            </Link>
          </div>
        </div>
        <div className='row g-6 g-xl-9'>
          {rows.data.map((row) => (
            <div className='col-sm-6 col-xl-4' key={'provider-' + row.id}>
              <ProviderCard provider={row} />
            </div>
          ))}
        </div>
        <Pagination paginationMeta={rows.meta} preserveScroll />
      </Content>
    </>
  );
}
Index.layout = (page: any) => <MasterLayout children={page} />;

export default Index
