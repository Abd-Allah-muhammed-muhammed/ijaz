import React, { Fragment } from 'react';
import MasterLayout from "@/_metronic/layout/MasterLayout";

import { PageTitle } from '@/_metronic/layout/core'
import { ToolbarWrapper } from '@/_metronic/layout/components/toolbar'
import { Content } from '@/_metronic/layout/components/content'
import { useTranslation } from 'react-i18next';
import { Head, Link } from '@inertiajs/react';
import { Card, Col, Nav, Row, Tab } from 'react-bootstrap';
import OrderController from '@/actions/App/Http/Controllers/Dashboard/OrderController';
import { Order, Provider, User } from '@/types/models';
import UserController from '@/actions/App/Http/Controllers/Dashboard/UserController';
import ProviderController from '@/actions/App/Http/Controllers/Dashboard/ProviderController';
import { KTIcon } from '@/_metronic/helpers';

type Props = {
  stats: {
    totalUsers: number
    totalProviders: number
    totalOrders: number
    totalRevenue: number
  }
  chartData: {
    dates: string[]
    userRegistrations: number[]
    providerRegistrations: number[]
    revenue: number[]
  }
  orderStatusDistribution: Record<string, number>
  latestUsers: User[]
  latestProviders: Provider[]
  pendingOrders: Order[]
  approvedOrders: Order[]
  inProgressOrders: Order[]
  endedByProviderOrders: Order[]
}

import { RegistrationChart, RevenueChart, OrderStatusChart } from './components/DashboardCharts'
import { StatisticsWidget5 } from '@/_metronic/partials/widgets'

const Home = ({
  stats,
  chartData,
  orderStatusDistribution,
  latestUsers,
  latestProviders,
  pendingOrders,
  approvedOrders,
  inProgressOrders,
  endedByProviderOrders,
}: Props) => {
  const { t } = useTranslation()
  return (
    <>
      <Head title={t('dashboard')} />
      <PageTitle breadcrumbs={[]}>{t('dashboard')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        {/* KPI Row */}
        <Row className='mb-5'>
          <Col md={3} sm={6} className='mb-xl-8'>
            <StatisticsWidget5
              className='card-xl-stretch mb-xl-8'
              color='primary'
              svgIcon='profile-user'
              iconColor='white'
              title={stats.totalUsers.toString()}
              description={t('total_users')}
              titleColor='white'
              descriptionColor='white'
            />
          </Col>
          <Col md={3} sm={6} className='mb-xl-8'>
            <StatisticsWidget5
              className='card-xl-stretch mb-xl-8'
              color='success'
              svgIcon='briefcase'
              iconColor='white'
              title={stats.totalProviders.toString()}
              description={t('total_providers')}
              titleColor='white'
              descriptionColor='white'
            />
          </Col>
          <Col md={3} sm={6} className='mb-xl-8'>
            <StatisticsWidget5
              className='card-xl-stretch mb-xl-8'
              color='info'
              svgIcon='basket'
              iconColor='white'
              title={stats.totalOrders.toString()}
              description={t('total_orders')}
              titleColor='white'
              descriptionColor='white'
            />
          </Col>
          <Col md={3} sm={6} className='mb-xl-8'>
            <StatisticsWidget5
              className='card-xl-stretch mb-xl-8'
              color='warning'
              svgIcon='cheque'
              iconColor='white'
              title={stats.totalRevenue.toLocaleString() + ' ' + t('SAR')}
              description={t('total_revenue')}
              titleColor='white'
              descriptionColor='white'
            />
          </Col>
        </Row>

        <Row className='mb-5'>
          <Col xl={4} className='mb-5 mb-xl-8'>
            <Card className='card-xl-stretch h-100'>
              <Card.Header className='border-0 pt-5'>
                <h3 className='card-title align-items-start flex-column'>
                  <span className='card-label fw-bold fs-3 mb-1'>{t('registrations_trend')}</span>
                  <span className='text-muted fw-semibold fs-7'>{t('last_30_days')}</span>
                </h3>
              </Card.Header>
              <Card.Body>
                <RegistrationChart
                  dates={chartData.dates}
                  userRegistrations={chartData.userRegistrations}
                  providerRegistrations={chartData.providerRegistrations}
                />
              </Card.Body>
            </Card>
          </Col>
          <Col xl={4} className='mb-5 mb-xl-8'>
            <Card className='card-xl-stretch h-100'>
              <Card.Header className='border-0 pt-5'>
                <h3 className='card-title align-items-start flex-column'>
                  <span className='card-label fw-bold fs-3 mb-1'>{t('revenue_trend')}</span>
                  <span className='text-muted fw-semibold fs-7'>{t('last_30_days')}</span>
                </h3>
              </Card.Header>
              <Card.Body>
                <RevenueChart dates={chartData.dates} revenue={chartData.revenue} />
              </Card.Body>
            </Card>
          </Col>
          <Col xl={4} className='mb-5 mb-xl-8'>
            <Card className='card-xl-stretch h-100'>
              <Card.Header className='border-0 pt-5'>
                <h3 className='card-title align-items-start flex-column'>
                  <span className='card-label fw-bold fs-3 mb-1'>{t('order_status_distribution')}</span>
                  <span className='text-muted fw-semibold fs-7'>{t('all_time')}</span>
                </h3>
              </Card.Header>
              <Card.Body>
                <OrderStatusChart distribution={orderStatusDistribution} />
              </Card.Body>
            </Card>
          </Col>
        </Row>

        <Row className='mb-5'>
          <Col md={6} className='mb-5'>
            <Card className='h-100'>
              <Card.Header className='align-items-center border-bottom-0 min-h-auto pt-4'>
                <div>
                  <h3 className='card-title fs-3 fw-bold mb-0 py-0 text-gray-900'>{t('users')}</h3>
                  <p className='fs-6 fw-bold'>{t('users_registered_on_the_app')}</p>
                </div>
                <div className='card-toolbar mb-0'>
                  <Link href={UserController.index().url} className='btn btn-sm btn-light'>
                    {t('show_all')}
                  </Link>
                </div>
              </Card.Header>
              <Card.Body className='pt-0'>
                {latestUsers?.map((user, index) => (
                  <UserItem key={user.id} user={user} isLast={index === latestUsers.length - 1} t={t} />
                ))}
              </Card.Body>
            </Card>
          </Col>

          <Col md={6} className='mb-5'>
            <Card className='h-100'>
              <Card.Header className='align-items-center border-bottom-0 min-h-auto pt-4'>
                <div>
                  <h3 className='card-title fs-3 fw-bold mb-0 py-0 text-gray-900'>{t('providers')}</h3>
                </div>
                <div className='card-toolbar mb-0'>
                  <Link href={ProviderController.index().url} className='btn btn-sm btn-light'>
                    {t('show_all')}
                  </Link>
                </div>
              </Card.Header>
              <Card.Body className='pt-0'>
                {latestProviders?.map((provider, index) => (
                  <ProviderItem key={provider.id} provider={provider} isLast={index === latestProviders.length - 1} t={t} />
                ))}
              </Card.Body>
            </Card>
          </Col>

          <Col md={12} className='mb-5'>
            <Card className='h-100'>
              <Card.Header className='align-items-center border-bottom-0 min-h-auto pt-4'>
                <h3 className='card-title fs-3 fw-bold mb-0 py-0 text-gray-900'>{t('my orders')}</h3>
                <div className='card-toolbar mb-0'>
                  <Link href={OrderController.index().url} className='btn btn-sm btn-light'>
                    {t('show_all')}
                  </Link>
                </div>
              </Card.Header>
              <Card.Body className='pt-0'>
                <Tab.Container defaultActiveKey='pending'>
                  <ul className='nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold'>
                    <li className='nav-item'>
                      <Nav.Link
                        className='nav-link text-active-primary py-5 me-6'
                        eventKey='pending'
                      >
                        {t('waiting_for_offer_approval')}
                      </Nav.Link>
                    </li>
                    <li className='nav-item'>
                      <Nav.Link
                        className='nav-link text-active-primary py-5 me-6'
                        eventKey='approved'
                      >
                        {t('waiting_for_payment')}
                      </Nav.Link>
                    </li>
                    <li className='nav-item'>
                      <Nav.Link
                        className='nav-link text-active-primary py-5 me-6'
                        eventKey='in_progress'
                      >
                        {t('in_progress')}
                      </Nav.Link>
                    </li>
                    <li className='nav-item'>
                      <Nav.Link
                        className='nav-link text-active-primary py-5 me-6'
                        eventKey='ended_by_provider'
                      >
                        {t('waiting_for_client_review')}
                      </Nav.Link>
                    </li>
                  </ul>
                  <Tab.Content>
                    <Tab.Pane eventKey='pending'>
                      {pendingOrders.length > 0 ? (
                        pendingOrders.map((order, i) => (
                          <OrderItem key={order.id} order={order} isLast={i === pendingOrders.length - 1} t={t} />
                        ))
                      ) : (
                        <EmptyOrders t={t} />
                      )}
                    </Tab.Pane>
                    <Tab.Pane eventKey='approved'>
                      {approvedOrders.length > 0 ? (
                        approvedOrders.map((order, i) => (
                          <OrderItem key={order.id} order={order} isLast={i === approvedOrders.length - 1} t={t} />
                        ))
                      ) : (
                        <EmptyOrders t={t} />
                      )}
                    </Tab.Pane>
                    <Tab.Pane eventKey='in_progress'>
                      {inProgressOrders.length > 0 ? (
                        inProgressOrders.map((order, i) => (
                          <OrderItem key={order.id} order={order} isLast={i === inProgressOrders.length - 1} t={t} />
                        ))
                      ) : (
                        <EmptyOrders t={t} />
                      )}
                    </Tab.Pane>
                    <Tab.Pane eventKey='ended_by_provider'>
                      {endedByProviderOrders.length > 0 ? (
                        endedByProviderOrders.map((order, i) => (
                          <OrderItem key={order.id} order={order} isLast={i === endedByProviderOrders.length - 1} t={t} />
                        ))
                      ) : (
                        <EmptyOrders t={t} />
                      )}
                    </Tab.Pane>
                  </Tab.Content>
                </Tab.Container>
              </Card.Body>
            </Card >
          </Col >
        </Row >
      </Content >
    </>
  )
}


const UserItem = ({ user, isLast, t }: { user: User; isLast: boolean; t: (key: string) => string }) => (
  <Fragment>
    <div className='d-flex align-items-center mb-7 p-4 rounded hover-bg-light transition-3ms'>
      <div className='symbol symbol-50px symbol-circle me-5'>
        <img src={user.image} alt={user.name} />
      </div>
      <div className='grow'>
        <span className='text-gray-900 fw-bold text-hover-primary fs-6'>
          {user.name}
        </span>
        <div className='d-flex align-items-center flex-wrap gap-2 mt-1'>
          <span className='badge badge-light-primary fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='calendar-8' className='text-primary fs-9 me-1' />
            {new Date(user.created_at).toLocaleDateString()}
          </span>
          <span className='badge badge-light-success fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='basket' className='text-success fs-9 me-1' />
            {user.orders_count || 0} {t('orders')}
          </span>
        </div>
      </div>
      <div className='ms-auto'>
        <Link
          href={UserController.show(user.id as number).url}
          className='btn btn-sm btn-icon btn-bg-light btn-active-color-primary'
        >
          <KTIcon iconName='eye' className='fs-2' />
        </Link>
      </div>
    </div>
    {!isLast && <div className='separator separator-dashed border-gray-300 mb-7'></div>}
  </Fragment>
)

const ProviderItem = ({ provider, isLast, t }: { provider: Provider; isLast: boolean; t: (key: string) => string }) => (
  <Fragment>
    <div className='d-flex align-items-center mb-7 p-4 rounded hover-bg-light transition-3ms'>
      <div className='symbol symbol-50px symbol-circle me-5'>
        <img src={provider.image as string} alt={provider.name} />
      </div>
      <div className='grow'>
        <span className='text-gray-900 fw-bold text-hover-primary fs-6'>
          {provider.name}
        </span>
        <div className='d-flex align-items-center flex-wrap gap-2 mt-1'>
          <span className='badge badge-light-primary fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='calendar-8' className='text-primary fs-9 me-1' />
            {new Date(provider.created_at).toLocaleDateString()}
          </span>
          <span className='badge badge-light-success fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='basket' className='text-success fs-9 me-1' />
            {provider.orders_count || 0} {t('orders')}
          </span>
          <span className='badge badge-light-warning fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='star' className='text-warning fs-9 me-1' />
            {provider.average_rating || 0} ({provider.reviews_count || 0})
          </span>
        </div>
      </div>
      <div className='ms-auto'>
        <Link
          href={ProviderController.show(provider.id as number).url}
          className='btn btn-sm btn-icon btn-bg-light btn-active-color-primary'
        >
          <KTIcon iconName='eye' className='fs-2' />
        </Link>
      </div>
    </div>
    {!isLast && <div className='separator separator-dashed border-gray-300 mb-7'></div>}
  </Fragment>
)

const OrderItem = ({ order, isLast, t }: { order: Order; isLast: boolean; t: (key: string) => string }) => (
  <Fragment>
    <div className='d-flex align-items-center mb-7 p-4 rounded hover-bg-light transition-3ms'>
      {/* Participant Avatars */}
      <div className='symbol-group symbol-hover me-5'>
        <div
          className='symbol symbol-35px symbol-circle'
          data-bs-toggle='tooltip'
          title={order.user?.name}
        >
          <img src={order.user?.image} alt={order.user?.name} />
        </div>
        {order.provider && (
          <div
            className='symbol symbol-35px symbol-circle'
            data-bs-toggle='tooltip'
            title={order.provider?.name}
          >
            <img src={order.provider?.image} alt={order.provider?.name} />
          </div>
        )}
      </div>

      <div className='grow me-2'>
        <Link
          href={OrderController.show(order.id as string).url}
          className='text-gray-900 fw-bold text-hover-primary fs-6'
        >
          {order.title}
        </Link>
        <div className='d-flex align-items-center flex-wrap gap-2 mt-1'>
          <span className='badge badge-light-primary fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='calendar-8' className='text-primary fs-9 me-1' />
            {new Date(order.created_at).toLocaleDateString()}
          </span>
          <span className='badge badge-light-success fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='wallet' className='text-success fs-9 me-1' />
            {order.budget_start} - {order.budget_end} {t('SAR')}
          </span>
          <span className='badge badge-light-info fw-semibold fs-8 px-2 py-1'>
            <KTIcon iconName='timer' className='text-info fs-9 me-1' />
            {order.expected_time}
          </span>
        </div>
      </div>

      <div className='text-end'>
        <span className={`badge badge-light-${order.status.color} fw-bold px-4 py-3`}>
          {order.status.label}
        </span>
      </div>
    </div>
    {!isLast && <div className='separator separator-dashed border-gray-300 mb-7'></div>}
  </Fragment>
)

const EmptyOrders = ({ t }: { t: (key: string) => string }) => (
  <div className='d-flex flex-column flex-center py-10'>
    <KTIcon iconName='basket-ok' className='fs-5x text-gray-300 mb-5' />
    <span className='text-gray-500 fw-bold fs-5'>{t('no_orders_found')}</span>
  </div>
)

Home.layout = (page: React.ReactElement) => {
  return (
    <MasterLayout {...page.props}>
      {page}
    </MasterLayout>
  )
}
export default Home

