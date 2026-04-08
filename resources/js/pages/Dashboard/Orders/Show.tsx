import { Head } from '@inertiajs/react';
import { Order } from "@/types/models";
import { useTranslation } from 'react-i18next';
import { Badge } from "react-bootstrap";
import { Content } from "@/_metronic/layout/components/content";
import { KTIcon } from "@/_metronic/helpers";
import React, { useState } from 'react';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import clsx from 'clsx';
import OverviewTap from './components/overview-tap';
import OffersTap from './components/offers-tap';
import ReviewsTap from './components/reviews-tap';
import ChatTap from './components/chat-tap';

type Props = {
  order: Order
}

const Show = ({ order }: Props) => {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState('details');

  // Status Badge Helper
  const getStatusBadge = (statusColor: string, statusLabel: string) => (
    <span className={`badge bg-light-${statusColor} text-${statusColor} fw-bold fs-7 px-4 py-2 rounded-pill border border-${statusColor} border-opacity-25`}>
      {statusLabel}
    </span>
  );

  return (
    <>
      <Head title={`${t('order')} #${order.id}`} />
      <Content>
        {/* Modern Hero Section */}
        <div className="card mb-6 mb-xl-9 shadow-sm border-0 rounded-4 overflow-hidden">
          {/* Gradient Background Header */}
          <div className="card-body pt-9 pb-0 bg-light-primary bg-opacity-10 position-relative">
            {/* Decorative Background Element */}
            <div className="position-absolute top-0 end-0 opacity-10 pe-5 pt-5">
              <KTIcon iconName="document" className="fs-5x text-primary" />
            </div>

            <div className="d-flex flex-wrap flex-sm-nowrap">
              {/* User Avatar Section */}
              <div className="me-7 mb-4">
                <div className="symbol symbol-75px symbol-lg-100px symbol-fixed position-relative bg-white p-2 rounded-circle shadow-sm">
                  {order.user?.image ? (
                    <img src={order.user.image} alt="User" className=" object-fit-cover rounded-circle" height={100} width={100} />
                  ) : (
                    <div className="symbol-label fs-1 bg-light-info text-info fw-bold rounded-circle w-100 h-100 d-flex align-items-center justify-content-center">
                      {order.user?.name?.charAt(0) || 'U'}
                    </div>
                  )}
                  <div className={`position-absolute translate-middle bottom-0 start-85 mb-3 bg-${order.status.color} rounded-circle border border-4 border-white h-20px w-20px`} title={order.status.label}></div>
                </div>
              </div>

              <div className="flex-grow-1">
                <div className="d-flex justify-content-between align-items-start flex-wrap mb-2">
                  <div className="d-flex flex-column">
                    <div className="d-flex align-items-center mb-1">
                      <h1 className="text-gray-900 fs-2 fw-bolder me-2 mb-0">{order.user?.name}</h1>
                      <span className="text-muted fs-6 fw-semibold ms-2 badge bg-white border border-gray-300 rounded-pill px-3 py-1">#{order.id}</span>
                    </div>
                    <div className="d-flex flex-wrap fw-semibold fs-6 mb-4 align-items-center text-gray-500">
                      <span className="d-flex align-items-center me-5 mb-2">
                        <KTIcon iconName="geolocation" className="fs-4 me-1 text-primary" />
                        {order.city?.title || 'Unknown City'}, {order.region?.title || 'Region'}
                      </span>
                      <span className="d-flex align-items-center me-5 mb-2">
                        <KTIcon iconName="calendar-8" className="fs-4 me-1 text-warning" />
                        {new Date(order.created_at).toLocaleDateString()}
                      </span>
                      <span className="d-flex align-items-center mb-2">
                        <KTIcon iconName="category" className="fs-4 me-1 text-info" />
                        {order.category?.title || 'General'}
                      </span>
                    </div>
                  </div>

                  <div className="d-flex my-4">
                    {getStatusBadge(order.status.color, order.status.label)}
                  </div>
                </div>

                {/* Stats Cards Row */}
                <div className="d-flex flex-wrap flex-stack mb-6">
                  <div className="d-flex flex-column flex-grow-1">
                    <div className="d-flex flex-wrap gap-4">
                      {/* Budget Card */}
                      <div className="d-flex align-items-center bg-white rounded-3 p-3 shadow-xs border border-gray-100 min-w-150px">
                        <div className="symbol symbol-40px me-3">
                          <span className="symbol-label bg-light-success text-success">
                            <KTIcon iconName="wallet" className="fs-2" />
                          </span>
                        </div>
                        <div className="d-flex flex-column">
                          <div className="fw-bold fs-5 text-gray-900">{order.budget_start} - {order.budget_end}</div>
                          <div className="text-muted fs-8 fw-semibold text-uppercase">{t('budget')}</div>
                        </div>
                      </div>

                      {/* Time Card */}
                      <div className="d-flex align-items-center bg-white rounded-3 p-3 shadow-xs border border-gray-100 min-w-150px">
                        <div className="symbol symbol-40px me-3">
                          <span className="symbol-label bg-light-warning text-warning">
                            <KTIcon iconName="timer" className="fs-2" />
                          </span>
                        </div>
                        <div className="d-flex flex-column">
                          <div className="fw-bold fs-5 text-gray-900">{order.expected_time}</div>
                          <div className="text-muted fs-8 fw-semibold text-uppercase">{t('expected_time')}</div>
                        </div>
                      </div>

                      {/* Final Price Card (if accepted) */}
                      {order.accepted_offer && (
                        <div className="d-flex align-items-center bg-white rounded-3 p-3 shadow-xs border border-gray-100 min-w-150px">
                          <div className="symbol symbol-40px me-3">
                            <span className="symbol-label bg-light-info text-info">
                              <KTIcon iconName="dollar" className="fs-2" />
                            </span>
                          </div>
                          <div className="d-flex flex-column">
                            <div className="fw-bold fs-5 text-gray-900">{order.accepted_offer.price}</div>
                            <div className="text-muted fs-8 fw-semibold text-uppercase">{t('final_price')}</div>
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Tabs Navigation */}
            <div className="d-flex overflow-auto h-55px w-100 px-5 border-top bg-white">
              <ul className="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold flex-nowrap">
                <li className="nav-item">
                  <a href="#" className={clsx("nav-link text-active-primary me-6", activeTab === 'details' && "active")} onClick={(e) => { e.preventDefault(); setActiveTab('details'); }}>
                    <KTIcon iconName="element-11" className="fs-3 me-2" />
                    {t('overview')}
                  </a>
                </li>

                <li className="nav-item">
                  <a href="#" className={clsx("nav-link text-active-primary me-6", activeTab === 'offers' && "active")} onClick={(e) => { e.preventDefault(); setActiveTab('offers'); }}>
                    <KTIcon iconName="clipboard" className="fs-3 me-2" />
                    {t('offers')} <Badge bg="light-primary" text="primary" className="ms-2 fw-bolder fs-8">{order.offers_count}</Badge>
                  </a>
                </li>
                <li className="nav-item">
                  <a href="#" className={clsx("nav-link text-active-primary me-6", activeTab === 'reviews' && "active")} onClick={(e) => { e.preventDefault(); setActiveTab('reviews'); }}>
                    <KTIcon iconName="star" className="fs-3 me-2 " />
                    {t('reviews')}
                  </a>
                </li>

                <li className="nav-item">
                  <a href="#" className={clsx("nav-link text-active-primary me-6", activeTab === 'chat' && "active")} onClick={(e) => { e.preventDefault(); setActiveTab('chat'); }}>
                    <KTIcon iconName="message-text-2" className="fs-3 me-2" />
                    {t('chat')}
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
        {/* Tab Content */}
        <div className="card-body">
          {activeTab === 'details' && <OverviewTap order={order} />}
          {activeTab === 'offers' && <OffersTap order={order} />}
          {activeTab === 'reviews' && <ReviewsTap order={order} />}
          {activeTab === 'chat' && <ChatTap order={order} />}
        </div>
      </Content>
    </>
  );
}

Show.layout = (page: React.ReactElement) => <MasterLayout children={page} />
export default Show;
