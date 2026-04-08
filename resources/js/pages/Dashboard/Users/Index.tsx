import { useTranslation } from 'react-i18next';
import MasterLayout from "@/_metronic/layout/MasterLayout";
import { PageTitle } from "@/_metronic/layout/core";
import { ToolbarWrapper } from "@/_metronic/layout/components/toolbar";
import { Content } from "@/_metronic/layout/components/content";
import { Head, Link, router } from "@inertiajs/react";
import { PaginationResource } from "@/types";
import { User } from "@/types/models";

import Pagination from "../../../components/Table/partials/Pagination";
import UserCard from "@/components/User/UserCard";
import { KTIcon } from "@/_metronic/helpers";
import UserController from "@/actions/App/Http/Controllers/Dashboard/UserController";

type Props = {
  rows: PaginationResource<User>,
  prams: SearchPrams | null;
  total_count: number;
  active_count: number;
  blocked_count: number;
};

type SearchPrams = {
  per_page: number;
  search: string;
};

const Index = ({
  rows,
  prams,
  total_count,
  active_count,
  blocked_count
}: Props) => {
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
    router.get(UserController.index().url, searchPrams);
  };

  return (
    <>
      <Head title={t('users')} />
      <PageTitle breadcrumbs={[]}>
        {t('users')}
      </PageTitle>
      <ToolbarWrapper />
      <Content>
        {/* Statistics Cards */}
        <div className='row g-6 g-xl-9 mb-6'>
          <div className='col-md-4'>
            <div className='card h-100 shadow-sm border-0'>
              <div className='card-body d-flex align-items-center p-6'>
                <div className='symbol symbol-50px me-5'>
                  <span className='symbol-label bg-light-primary'>
                    <KTIcon iconName='profile-user' className='fs-2qx text-primary' />
                  </span>
                </div>
                <div className='d-flex flex-column'>
                  <span className='fs-2hx fw-bold text-gray-900'>{total_count || 0}</span>
                  <span className='text-muted fw-semibold fs-6'>{t('total_users')}</span>
                </div>
              </div>
            </div>
          </div>
          <div className='col-md-4'>
            <div className='card h-100 shadow-sm border-0'>
              <div className='card-body d-flex align-items-center p-6'>
                <div className='symbol symbol-50px me-5'>
                  <span className='symbol-label bg-light-success'>
                    <KTIcon iconName='check-circle' className='fs-2qx text-success' />
                  </span>
                </div>
                <div className='d-flex flex-column'>
                  <span className='fs-2hx fw-bold text-gray-900'>{active_count || 0}</span>
                  <span className='text-muted fw-semibold fs-6'>{t('active_users')}</span>
                </div>
              </div>
            </div>
          </div>
          <div className='col-md-4'>
            <div className='card h-100 shadow-sm border-0'>
              <div className='card-body d-flex align-items-center p-6'>
                <div className='symbol symbol-50px me-5'>
                  <span className='symbol-label bg-light-danger'>
                    <KTIcon iconName='information-5' className='fs-2qx text-danger' />
                  </span>
                </div>
                <div className='d-flex flex-column'>
                  <span className='fs-2hx fw-bold text-gray-900'>{blocked_count || 0}</span>
                  <span className='text-muted fw-semibold fs-6'>{t('blocked_users')}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Toolbar: Search and Filter */}
        <div className='card mb-6 shadow-sm border-0'>
          <div className='card-body p-4'>
            <div className='d-flex flex-stack flex-wrap gap-4'>
              <div className='d-flex align-items-center position-relative my-1'>
                <KTIcon iconName='magnifier' className='fs-2 position-absolute ms-4' />
                <input
                  type='text'
                  defaultValue={searchPrams.search}
                  className='form-control form-control-solid w-250px ps-12'
                  placeholder={t('search_users')}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      searchPramsChanged('search', e.currentTarget.value)
                    }
                  }}
                />
              </div>

              <div className='d-flex align-items-center gap-3'>
                <Link
                  href={UserController.create().url}
                  className="btn btn-primary d-flex align-items-center gap-2"
                >
                  <KTIcon iconName='plus' className='fs-2' />
                  {t('add_user')}
                </Link>
              </div>
            </div>
          </div>
        </div>

        {/* User Grid */}
        <div className='row g-6 g-xl-9'>
          {rows.data.map((row) => (
            <div className='col-sm-6 col-xl-4' key={'user-' + row.id}>
              <UserCard user={row} />
            </div>
          ))}
        </div>

        <div className='mt-10'>
          <Pagination paginationMeta={rows.meta} preserveScroll />
        </div>
      </Content>
    </>
  );
}

Index.layout = (page: React.ReactNode) => <MasterLayout children={page} />;

export default Index
