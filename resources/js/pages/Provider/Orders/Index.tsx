import ProviderLayout from "@/layouts/provider/ProviderLayout";
import {Head, router} from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import {PageTitle} from "@/_metronic/layout/core";
import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {KTIcon} from "@/_metronic/helpers";
import Pagination from "../../../components/Table/partials/Pagination";
import {PaginationResource} from "@/types";
import {Order, Provider} from "@/types/models";
import OrderController from "@/actions/App/Http/Controllers/Provider/OrderController";
import OrderCard from "@/components/order/order-card";
import {Col, Row} from "react-bootstrap";

type Props = {
  rows: PaginationResource<Order>,
  prams: SearchPrams | null;
};

type SearchPrams = {
  per_page: number;
  search: string;

};
const Index = (
  {
    rows,
    prams,
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

    router.reload<SearchPrams>({
      only: ['rows', 'prams'],
      data: searchPrams,
      // @ts-ignore
      preserveState: true,
      preserveScroll: true,
    });
  };
  return (
    <>
      <Head title={t('providers')}/>
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
      <ToolbarWrapper/>
      <Content>
        <div className='d-flex flex-wrap flex-stack mb-6'>
          <h3 className='fw-bolder my-2'>
            <div className='d-flex align-items-center position-relative my-1'>
              <KTIcon iconName='magnifier' className='fs-1 position-absolute ms-6'/>
              <input
                type='text'
                defaultValue={searchPrams.search}
                data-kt-user-table-filter='search'
                className='form-control  ps-14'
                placeholder='Search'
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    searchPramsChanged('search', e.currentTarget.value)
                  }
                }}
              />
            </div>
          </h3>

          <div className='d-flex align-items-center my-2'>
            <div className=' me-5'>
              <select
                name='status'
                data-control='select2'
                data-hide-search='true'
                className='form-select form-select-white form-select-sm'
                defaultValue='1'
              >
                <option value='1'>30 Days</option>
                <option value='2'>90 Days</option>
                <option value='3'>6 Months</option>
                <option value='4'>1 Year</option>
              </select>
            </div>
          </div>
        </div>
        <Row className='row'>
          {rows.data.map((row) => (
            <Col sm={6} xl={3} key={'order-' + row.id}>
              <OrderCard url={OrderController.show(row.id as string).url} order={row}/>
            </Col>
          ))}
        </Row>
        <Pagination paginationMeta={rows.meta} preserveScroll/>
      </Content>
    </>
  )
}


Index.layout = (page: any) => {
  return <ProviderLayout {...page.props}>{page}</ProviderLayout>
}

export default Index
