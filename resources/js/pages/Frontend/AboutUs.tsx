import FrontendLayout from "@/layouts/FrontendLayout";
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import React, { ReactNode } from 'react';
import './style.css';
import { Accordion, Col, Container, Row } from 'react-bootstrap';

const AboutUs = () => {
  const { t } = useTranslation();
  return (
      <>
          <Head title={t('about_us')} />
          <Container className="bg-primary mt-0 one-side-border-bottom-lg" style={{paddingTop: "60px", minHeight: "290px"}} fluid>
              <Container className="h-100 pt-20 w-fit-content m-auto position-relative">
                <p className="text-white fs-5x text-center mb-0 w-fit-content">{t('about_us')}</p>
                <div className="underline-warning"></div>
              </Container>
          </Container>
          <Container data-pan="about-page">
            <div className="py-20 line-height-lg">
                <Row className="px-20 py-10 bg-white position-relative" style={{borderRadius: "33px"}}>
                  <Col lg={3} xs={12} className="d-flex align-items-center">
                    <img src="/logo-success-no-bg.svg" alt="Logo" className="w-100" />
                  </Col>
                  <Col lg={9} xs={12} className="mt-md-0 mt-15 fs-3">
                    <div dangerouslySetInnerHTML={{__html: t('ijaz_is_a_national_digital_platform_that_aims_to_connect_service_seekers_with_service_providers')}}></div>
                  </Col>
                  <span className="top-right-badge bg-success">{t('who_are_we')}</span>
                </Row>

                <Row className="mt-20 px-20 py-10 bg-white position-relative" style={{borderRadius: "33px"}}>
                  <Col lg={9} xs={12} className="mt-md-0 mt-15 fs-3">
                    <div dangerouslySetInnerHTML={{__html: t('at_ejaz_we_believe_that_technology_is_the_key_to_the_digital_future')}}></div>
                  </Col>
                  <Col lg={3} xs={12} className="d-flex align-items-center">
                    <img src="/media/landing/message-notification.svg" alt="Logo" className="w-100" />
                  </Col>
                  <span className="top-left-badge bg-success">{t('our_message')}</span>
                </Row>

                <Row className="mt-20 px-20 py-10 bg-white position-relative" style={{borderRadius: "33px"}}>
                  <Col lg={3} xs={12} className="d-flex align-items-center">
                    <img src="/media/landing/Illustration.svg" alt="Logo" className="w-100" />
                  </Col>
                  <Col lg={9} xs={12} className="mt-md-0 mt-15 fs-3">
                    <div dangerouslySetInnerHTML={{__html: t('to_make_ijaz_the_leading_and_primary_destination_in_the_world_of_e-services')}}></div>
                  </Col>
                  <span className="top-right-badge bg-success">{t('our_vision')}</span>
                </Row>

                <Row className="mt-20 px-20 py-10 bg-white position-relative" style={{borderRadius: "33px"}}>
                  <Col lg={9} xs={12} className="mt-md-0 mt-15 fs-3">
                    <div dangerouslySetInnerHTML={{__html: t('empowering_individuals_and_organizations_through_a_comprehensive_digital_platform')}}></div>
                  </Col>
                  <Col lg={3} xs={12} className="d-flex align-items-center">
                    <img src="/media/landing/Goals.svg" alt="Logo" className="w-100" />
                  </Col>
                  <span className="top-left-badge bg-success">{t('our_goals')}</span>
                </Row>

                <Row className="mt-20 px-20 py-10 bg-white" style={{borderRadius: "33px"}}>
                  <Col xxl={7} xs={12}>
                      <p className="fs-4x">{t('we_answer_your_question')}</p>
                      <Accordion>
                          {Array.from({ length: 14 }, (el, i) => (
                            <Accordion.Item className="mb-5" eventKey={"question-" + i} key={i}>
                              <Accordion.Header>
                                <div className="d-flex align-items-center gap-4">
                                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                      d="M17 18.43H13L8.54999 21.39C7.88999 21.83 7 21.36 7 20.56V18.43C4 18.43 2 16.43 2 13.43V7.42993C2 4.42993 4 2.42993 7 2.42993H17C20 2.42993 22 4.42993 22 7.42993V13.43C22 16.43 20 18.43 17 18.43Z"
                                      stroke="#40A75F"
                                      strokeWidth="1.5"
                                      strokeMiterlimit="10"
                                      strokeLinecap="round"
                                      strokeLinejoin="round"
                                    ></path>
                                    <path
                                      opacity="0.4"
                                      d="M12 11.3599V11.1499C12 10.4699 12.42 10.1099 12.84 9.81989C13.25 9.53989 13.66 9.1799 13.66 8.5199C13.66 7.5999 12.92 6.85986 12 6.85986C11.08 6.85986 10.34 7.5999 10.34 8.5199"
                                      stroke="#40A75F"
                                      strokeWidth="1.5"
                                      strokeLinecap="round"
                                      strokeLinejoin="round"
                                    ></path>
                                    <path
                                      opacity="0.4"
                                      d="M11.9955 13.75H12.0045"
                                      stroke="#292D32"
                                      strokeWidth="1.5"
                                      strokeLinecap="round"
                                      strokeLinejoin="round"
                                    ></path>
                                  </svg>
                                  <p className="fs-2x mb-0 text-black">{t(`question_${i + 1}_header`)}</p>
                                </div>
                              </Accordion.Header>
                              <Accordion.Body className="fs-4" dangerouslySetInnerHTML={{__html:t(`question_${i + 1}_body`)}}>
                              </Accordion.Body>
                            </Accordion.Item>
                          ))}
                      </Accordion>
                  </Col>
                  <Col xxl={5} xs={12} className="p-9">
                      <img style={{ width: '100%' }} src="/media/landing/FAQ-image.svg" alt="image" />
                  </Col>
                </Row>
            </div>
          </Container>
      </>
  );
};

AboutUs.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default AboutUs;
