import {PageTitle} from '@/vendor/metronic/layout/core'
import {ToolbarWrapper} from '@/vendor/metronic/layout/components/toolbar'
import {Content} from '@/vendor/metronic/layout/components/content'
import { useTranslation } from 'react-i18next';
import {Head, Link, usePage} from "@inertiajs/react";
import ProviderLayout from "@/apps/provider/layouts/ProviderLayout";
import { Card, Col, Image, Nav, Row, Tab } from 'react-bootstrap';
import {useRecommendedOrdersContext} from "@/store/recommend-orders-context";
import { Banner, Order, Provider, Wallet, WalletTransaction } from '@/shared/types/models';
import {useEffect} from "react";
import OrderController from "@/actions/Modules/Orders/Http/Controllers/Provider/OrderController";
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import WalletQuickActions from '@/apps/provider/components/wallet/WalletQuickActions';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Pagination } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/pagination';

type Props = {
  totalOrders: number
  totalFinishedOrders: number,
  wallet?: Wallet,
  recentTransactions?: WalletTransaction[],
  recommendOrders: Order[],
  banners: Banner[],
  pendingOrders: Order[],
  approvedOrders: Order[],
  inProgressOrders: Order[],
  endedByProviderOrders: Order[],
};

const Home = (
  {totalOrders, totalFinishedOrders, wallet, recentTransactions = [], recommendOrders, banners, pendingOrders, approvedOrders, inProgressOrders, endedByProviderOrders}: Props
) => {
  const { t } = useTranslation();
  const user = usePage().props.auth.user as unknown as Provider
  const {setOrders} = useRecommendedOrdersContext();
  useEffect(() => {
    setOrders(recommendOrders)
  }, []);

  const displayBanners = banners.filter((banner) => Boolean(banner.image))
  const hasBanners = displayBanners.length > 0
  const metrics = [
    {value: wallet?.balance, label: t('balance')},
    {value: wallet?.amount_in_transfer ?? 0, label: t('amount_in_transfer')},
    {value: totalOrders, label: t('total_orders')},
    {value: totalFinishedOrders, label: t('completed_orders')},
  ]

  return (
    <>
      <Head title={t('dashboard')} />
      <PageTitle breadcrumbs={[]}>{t('dashboard')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <div className="card mb-5">
          <div className="card-body py-5">
            <div className="d-flex flex-wrap justify-content-between align-items-center gap-3">
              <div className="d-flex align-items-center">
                <div className="symbol symbol-50px symbol-circle me-4">
                  {user.logo ? (
                    <img src={user.logo} alt={user.name} className="object-fit-contain" />
                  ) : (
                    <span className="symbol-label fs-3 fw-bold text-primary">
                      {(user.name ?? '').charAt(0)}
                    </span>
                  )}
                </div>
                <h2 className="fs-3 fw-bold text-gray-900 mb-0">
                  {t('welcome_back', { name: user.name })}
                </h2>
              </div>
              <WalletQuickActions
                className="d-flex"
                reloadOnly={['wallet', 'recentTransactions']}
              />
            </div>
          </div>
        </div>

        <Row className="mb-5 g-5">
          {metrics.map((metric) => (
            <Col key={metric.label} xs={6} md={3}>
              <Card className="h-100">
                <Card.Body className="py-5">
                  <div className="fs-2 fw-bolder text-gray-900">{metric.value}</div>
                  <div className="fw-bold fs-6 text-gray-500">{metric.label}</div>
                </Card.Body>
              </Card>
            </Col>
          ))}
        </Row>

        <Row className="mb-5">
          <Col md={hasBanners ? 6 : 12}>
            <Card>
              <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4">
                <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">{t('my orders')}</h3>
                <div className="card-toolbar mb-0">
                  <Link href={OrderController.index().url} className="btn btn-sm btn-light">
                    {t('show_all')}
                  </Link>
                </div>
              </Card.Header>
              <Card.Body className="pt-0">
                <Tab.Container defaultActiveKey="pending">
                  <Nav variant="pills" className="nav-pills-custom position-relative mb-9 gap-5">
                    <Nav.Item>
                      <Nav.Link
                        className="btn btn-color-gray-600 btn-active-color-primary d-flex justify-content-center h-100 w-100 border-0 px-0"
                        eventKey="pending"
                      >
                        <span className="nav-text fw-bold fs-6 text-gray-600">{t('waiting_for_offer_approval')}</span>
                        <span
                          className="bullet-custom position-absolute z-index-2 h-3px bottom-0 w-100 rounded"
                          style={{ backgroundColor: '#00686D' }}
                        ></span>
                      </Nav.Link>
                    </Nav.Item>
                    <Nav.Item>
                      <Nav.Link
                        className="btn btn-color-gray-600 btn-active-color-primary d-flex justify-content-center h-100 w-100 border-0 px-0"
                        eventKey="approved"
                      >
                        <span className="nav-text fw-bold fs-6 text-gray-600">{t('waiting_for_payment')}</span>
                        <span
                          className="bullet-custom position-absolute z-index-2 h-3px bottom-0 w-100 rounded"
                          style={{ backgroundColor: '#00686D' }}
                        ></span>
                      </Nav.Link>
                    </Nav.Item>
                    <Nav.Item>
                      <Nav.Link
                        className="btn btn-color-gray-600 btn-active-color-primary d-flex justify-content-center h-100 w-100 border-0 px-0"
                        eventKey="in_progress"
                      >
                        <span className="nav-text fw-bold fs-6 text-gray-600">{t('in_progress')}</span>
                        <span
                          className="bullet-custom position-absolute z-index-2 h-3px bottom-0 w-100 rounded"
                          style={{ backgroundColor: '#00686D' }}
                        ></span>
                      </Nav.Link>
                    </Nav.Item>
                    <Nav.Item>
                      <Nav.Link
                        className="btn btn-color-gray-600 btn-active-color-primary d-flex justify-content-center h-100 w-100 border-0 px-0"
                        eventKey="ended_by_provider"
                      >
                        <span className="nav-text fw-bold fs-6 text-gray-600">{t('waiting_for_client_review')}</span>
                        <span
                          className="bullet-custom position-absolute z-index-2 h-3px bottom-0 w-100 rounded"
                          style={{ backgroundColor: '#00686D' }}
                        ></span>
                      </Nav.Link>
                    </Nav.Item>
                  </Nav>
                  <Tab.Content>
                    <Tab.Pane eventKey="pending">
                      {pendingOrders.map((order, i) => (
                        <>
                          <Link href={OrderController.show(order.id as string).url}>
                            <div className="m-0">
                              <div className="d-flex align-items-sm-center mb-5">
                                <div className="d-flex align-items-center flex-row-fluid flex-wrap">
                                  <div className="me-2 flex-grow-1">
                                    <span className="fw-bold d-block fs-5 text-gray-800">{order.title}</span>
                                    <div className="d-flex gap-5">
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/svg/note.svg" alt="Note" className="me-1" style={{ width: '20px' }} />
                                        {new Date(order.created_at).toLocaleDateString()}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/wallet.svg" alt="Wallet" className="me-1" style={{ width: '20px' }} />
                                        {t('from') + ' ' + order.budget_start}
                                        {}
                                        <img src="/media/svg/Riyal.svg" alt="Wallet" className="mx-1" style={{ width: '20px' }} />
                                        {t('to') + ' ' + order.budget_end}
                                        <img src="/media/svg/Riyal.svg" alt="Wallet" className="mx-1" style={{ width: '20px' }} />
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/clock.svg" alt="Clock" className="me-1" style={{ width: '20px' }} />
                                        {order.expected_time}
                                      </span>
                                    </div>
                                    <span className="fw-semibold fs-6 text-gray-500">{order.description}</span>
                                  </div>
                                  <span className={`badge badge-lg badge-light-success fw-bold fs-7 badge-${order.status.color}`}>
                                    {order.status.label}
                                  </span>
                                </div>
                              </div>
                            </div>
                          </Link>
                          {i != pendingOrders.length - 1 &&(<div className="separator separator-dashed mt-5 mb-6"></div>)}
                        </>
                      ))}
                    </Tab.Pane>
                    <Tab.Pane eventKey="approved">
                      {approvedOrders.map((order, i) => (
                        <>
                          <Link href={OrderController.show(order.id as string).url}>
                            <div className="m-0">
                              <div className="d-flex align-items-sm-center mb-5">
                                <div className="d-flex align-items-center flex-row-fluid flex-wrap">
                                  <div className="me-2 flex-grow-1">
                                    <span className="fw-bold d-block fs-5 text-gray-800">{order.title}</span>
                                    <div className="d-flex gap-5">
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/svg/note.svg" alt="Note" className="me-1" style={{ width: '20px' }} />
                                        {new Date(order.created_at).toLocaleDateString()}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/wallet.svg" alt="Wallet" className="me-1" style={{ width: '20px' }} />
                                        {t('from')} {order.budget_start} {t('to')} {order.budget_end}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/clock.svg" alt="Clock" className="me-1" style={{ width: '20px' }} />
                                        {order.expected_time}
                                      </span>
                                    </div>
                                    <span className="fw-semibold fs-6 text-gray-500">{order.description}</span>
                                  </div>
                                  <span className={`badge badge-lg badge-light-success fw-bold fs-7 badge-${order.status.color}`}>
                                    {order.status.label}
                                  </span>
                                </div>
                              </div>
                            </div>
                          </Link>
                          {i != approvedOrders.length - 1 &&(<div className="separator separator-dashed mt-5 mb-6"></div>)}
                        </>
                      ))}
                    </Tab.Pane>
                    <Tab.Pane eventKey="in_progress">
                      {inProgressOrders.map((order, i) => (
                        <>
                          <Link href={OrderController.show(order.id as string).url}>
                            <div className="m-0">
                              <div className="d-flex align-items-sm-center mb-5">
                                <div className="d-flex align-items-center flex-row-fluid flex-wrap">
                                  <div className="me-2 flex-grow-1">
                                    <span className="fw-bold d-block fs-5 text-gray-800">{order.title}</span>
                                    <div className="d-flex gap-5">
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/svg/note.svg" alt="Note" className="me-1" style={{ width: '20px' }} />
                                        {new Date(order.created_at).toLocaleDateString()}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/wallet.svg" alt="Wallet" className="me-1" style={{ width: '20px' }} />
                                        {t('from')} {order.budget_start} {t('to')} {order.budget_end}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/clock.svg" alt="Clock" className="me-1" style={{ width: '20px' }} />
                                        {order.expected_time}
                                      </span>
                                    </div>
                                    <span className="fw-semibold fs-6 text-gray-500">{order.description}</span>
                                  </div>
                                  <span className={`badge badge-lg badge-light-success fw-bold fs-7 badge-${order.status.color}`}>
                                    {order.status.label}
                                  </span>
                                </div>
                              </div>
                            </div>
                          </Link>
                          {i != inProgressOrders.length - 1 &&(<div className="separator separator-dashed mt-5 mb-6"></div>)}
                        </>
                      ))}
                    </Tab.Pane>
                    <Tab.Pane eventKey="ended_by_provider">
                      {endedByProviderOrders.map((order, i) => (
                        <>
                          <Link href={OrderController.show(order.id as string).url}>
                            <div className="m-0">
                              <div className="d-flex align-items-sm-center mb-5">
                                <div className="d-flex align-items-center flex-row-fluid flex-wrap">
                                  <div className="me-2 flex-grow-1">
                                    <span className="fw-bold d-block fs-5 text-gray-800">{order.title}</span>
                                    <div className="d-flex gap-5">
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/svg/note.svg" alt="Note" className="me-1" style={{ width: '20px' }} />
                                        {new Date(order.created_at).toLocaleDateString()}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/wallet.svg" alt="Wallet" className="me-1" style={{ width: '20px' }} />
                                        {t('from')} {order.budget_start} {t('to')} {order.budget_end}
                                      </span>
                                      <span className="fw-semibold fs-6 d-flex align-items-center text-gray-500">
                                        <img src="/media/icons/clock.svg" alt="Clock" className="me-1" style={{ width: '20px' }} />
                                        {order.expected_time}
                                      </span>
                                    </div>
                                    <span className="fw-semibold fs-6 text-gray-500">{order.description}</span>
                                  </div>
                                  <span className={`badge badge-lg badge-light-success fw-bold fs-7 badge-${order.status.color}`}>
                                    {order.status.label}
                                  </span>
                                </div>
                              </div>
                            </div>
                          </Link>
                          {i != endedByProviderOrders.length - 1 &&(<div className="separator separator-dashed mt-5 mb-6"></div>)}
                        </>
                      ))}
                    </Tab.Pane>
                  </Tab.Content>
                </Tab.Container>
              </Card.Body>
            </Card>
          </Col>
          {hasBanners && (
          <Col md={6}>
            <Card>
              <Card.Body>
                <Swiper
                  slidesPerView={1}
                  modules={[Pagination]}
                  pagination={{
                    clickable: true,
                  }}
                  className="mySwiper d-block"
                >
                  {displayBanners.map((el) => (
                    <SwiperSlide key={el.id}>
                      <Link href={el?.link ?? '#'}>
                        <Image
                          src={el.image ?? undefined}
                          alt={'banner-' + el.id}
                          className="w-100 object-fit-cover"
                          style={{ aspectRatio: '16 / 9' }}
                        />
                      </Link>
                    </SwiperSlide>
                  ))}
                </Swiper>
              </Card.Body>
            </Card>
          </Col>
          )}
        </Row>

        <Card className="mb-5">
          <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4">
            <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">{t('recent_wallet_activity')}</h3>
            <div className="card-toolbar mb-0">
              <Link href={AuthController.statements().url} className="btn btn-sm btn-light">
                {t('view_statements')}
              </Link>
            </div>
          </Card.Header>
          <Card.Body>
            {recentTransactions.length === 0 ? (
              <div className="text-gray-500">{t('no_data')}</div>
            ) : (
              recentTransactions.map((transaction, i) => {
                const amount = Number(transaction.amount) || 0
                const isPending = Boolean(transaction.is_pending)
                const isCredit = ! isPending && Number(transaction.credit) > 0

                return (
                  <div key={transaction.id}>
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="fw-semibold fs-6 text-gray-800">{transaction.description}</span>
                      {isPending ? (
                        <span className="d-flex align-items-center gap-2">
                          <span className="fw-bold fs-6 text-gray-500">{amount}</span>
                          <span className="badge badge-light-warning fs-8">{t('pending')}</span>
                        </span>
                      ) : (
                        <span className={`fw-bold fs-6 ${isCredit ? 'text-success' : 'text-gray-800'}`}>
                          {isCredit ? `+${amount}` : `-${amount}`}
                        </span>
                      )}
                    </div>
                    {i !== recentTransactions.length - 1 && (
                      <div className="separator separator-dashed my-4"></div>
                    )}
                  </div>
                )
              })
            )}
          </Card.Body>
        </Card>
      </Content>
    </>
  );
}


Home.layout = (page: any) => {
  return (
    <ProviderLayout {...page.props}>
      {page}
    </ProviderLayout>
  )
}
export default Home

