import {PageTitle} from '@/_metronic/layout/core'
import {ToolbarWrapper} from '@/_metronic/layout/components/toolbar'
import {Content} from '@/_metronic/layout/components/content'
import { useTranslation } from 'react-i18next';
import {Head, Link} from "@inertiajs/react";
import ProviderLayout from "@/layouts/provider/ProviderLayout";
import { Card, Col, Image, Nav, Row, Tab } from 'react-bootstrap';
import {useRecommendedOrdersContext} from "@/store/recommend-orders-context";
import { Banner, Order } from '@/types/models';
import {useEffect} from "react";
import OrderController from "@/actions/App/Http/Controllers/Provider/OrderController";
import { Swiper, SwiperSlide } from 'swiper/react';
import { Pagination } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/pagination';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faMessage } from '@fortawesome/free-solid-svg-icons';
import ChartWidget1 from '@/components/charts/ChartWidget1';
import ChartWidget2 from '@/components/charts/ChartWidget2';

type Props = {
  totalOrders: number
  totalFinishedOrders: number,
  recommendOrders: Order[],
  banners: Banner[],
  pendingOrders: Order[],
  approvedOrders: Order[],
  inProgressOrders: Order[],
  endedByProviderOrders: Order[],
};

const Home = (
  {totalOrders, totalFinishedOrders, recommendOrders, banners, pendingOrders, approvedOrders, inProgressOrders, endedByProviderOrders}: Props
) => {
  const { t } = useTranslation();
  const {setOrders, orders} = useRecommendedOrdersContext();
  useEffect(() => {
    setOrders(recommendOrders)
  }, []);

  return (
    <>
      <Head title={t('dashboard')} />
      <PageTitle breadcrumbs={[]}>{t('dashboard')}</PageTitle>
      <ToolbarWrapper />
      <Content>
        <Row className="mb-5">
          <Col md={6}>
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
                  {banners.map((el) => (
                    <SwiperSlide>
                      <Link href={el?.link ?? '#'}>
                        <Image src={el.image} alt={'banner-' + el.id} className="w-100" />
                      </Link>
                    </SwiperSlide>
                  ))}
                </Swiper>
                {/*<Carousel controls={true} indicators={true} >*/}
                {/*  {banners.map(el => (*/}
                {/*    <Carousel.Item>*/}
                {/*      <Image src={el.image} alt={"banner-" + el.id} className="w-100"/>*/}
                {/*    </Carousel.Item>*/}
                {/*  ))}*/}
                {/*</Carousel>*/}
              </Card.Body>
            </Card>
          </Col>
        </Row>

        <Row className="mb-5">
          <Col md={4}>
            <Card>
              <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4">
                <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">{t('wallet')}</h3>
                <div className="d-flex justify-content-between w-100">
                  <p>{t('orders provided by client')}</p>
                  <p>
                    2500
                    <img src="/media/svg/Riyal.svg" alt="Wallet" className="mx-1" style={{ width: '20px' }} />
                  </p>
                </div>
              </Card.Header>
              <Card.Body>
                <ChartWidget1 data={[3500, 5700, 2800, 5900, 4200, 5600, 4300, 4500, 5900, 4500, 5700, 4800, 5700]}/>
              </Card.Body>
            </Card>
          </Col>
          <Col md={4}>
            <Card>
              <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4">
                <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">{t('conversations')}</h3>
                <p className="w-100">{t('orders provided by client')}</p>
              </Card.Header>
              <Card.Body>
                {endedByProviderOrders.map((order, i) => (
                  <>
                    <div className="m-0">
                      <div className="d-flex align-items-sm-center mb-5">
                        <div className="d-flex align-items-center flex-row-fluid flex-wrap">
                          <img src="/media/icons/wallet.svg" alt="Wallet" className="me-1" style={{ width: '30px' }} />
                          <div className="me-2 flex-grow-1">
                            <span className="fw-bold d-block fs-5 text-gray-800">{order.title}</span>
                            <span className="fw-bold fs-6 text-gray-500">متاح الان</span>
                          </div>
                          <Link href="#" className="btn btn-success">
                            <FontAwesomeIcon  icon={faMessage} className="me-1"/>
                            محادثة الان
                          </Link>
                        </div>
                      </div>
                    </div>
                    {i != endedByProviderOrders.length -1 && (<div className="separator separator-dashed mt-5 mb-6"></div>)}
                  </>
                ))}
              </Card.Body>
            </Card>
          </Col>
          <Col md={4}>
            <Card>
              <Card.Header className="align-items-center border-bottom-0 min-h-auto pt-4">
                <h3 className="card-title fs-3 fw-bold mb-0 py-0 text-gray-900">{t('order_count')}</h3>
                <div className="d-flex justify-content-between w-100">
                  <p>{t('orders provided by client')}</p>
                  <p>
                    2500
                    <img src="/media/svg/Riyal.svg" alt="Wallet" className="mx-1" style={{ width: '20px' }} />
                  </p>
                </div>
              </Card.Header>
              <Card.Body className="bg-none">
                <ChartWidget2 data={[34.5, 34.5, 35, 35, 35.5, 35.5, 35, 35, 35.5, 35.5, 35, 35, 34.5, 34.5, 35, 35, 35.5, 35.5, 35]} tooltipExtraLabel={"Count"} />
              </Card.Body>
            </Card>
          </Col>
        </Row>
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

