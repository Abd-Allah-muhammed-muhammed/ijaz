import { useTranslation } from 'react-i18next';
import FrontendLayout from '@/layouts/FrontendLayout';
import { Head, usePage } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import { Badge, Card, Col, Container, Row } from 'react-bootstrap';
import './style.css';

const OurServices = () => {
  const { t } = useTranslation();
    const locale = usePage().props.app.locale;
    const id = window.location.pathname.replace(/\/$/,'').split('/').pop();
    return (
        <>
            <Head title={t('about_us')} />
            <Container className="py-20" data-pan="service-page">
                <Row className="bg-white" style={{ borderRadius: '52px' , minHeight: "420px"}}>
                    <Col lg={9} className="line-height-lg px-10 py-10">
                        <p className="fs-3x fw-bold">{t(`service_${id}_title`)}</p>
                        <p className="fs-2">
                          {t('service_body')}
                        </p>
                    </Col>
                    <Col lg={3}>
                        <div
                            className="bg-primary position-lg-relative d-flex justify-content-center align-items-center p-10"
                            style={{
                                height: 'calc(100% - 60px)',
                                left: (['ar', 'ur']).includes(locale.toLowerCase()) ? '-30%' : "unset",
                                right: !(['ar', 'ur']).includes(locale.toLowerCase()) ? '-30%' : "unset",
                                transform: 'translateY(30px)',
                                borderRadius: '32px',
                            }}
                        >
                            <img src={`/media/landing/service_${id}.svg`} alt="Service" className="w-100" />
                        </div>
                    </Col>
                </Row>

                <h3 className="fs-3x my-10">{t('service_categories')}</h3>

                <Row>
                  {([1, 2]).map(function(j) {
                    const text = t(`service_${id}_badge_${j}`);
                    if (text != `service_${id}_badge_${j}`) {
                      return (
                        <Col lg={3} md={4} xs={6}>
                            <Card className="p-2 h-100" style={{ borderRadius: '20px' }}>
                                <Card.Header className="bg-primary d-flex justify-content-center position-relative" style={{ borderRadius: '20px' }}>
                                    <Card.Img variant="top" src={`/media/landing/service_${id}.svg`} style={{ width: '173px', height: '173px' }} />
                                    <span className="bottom-right-small-badge bg-success">{t(`service_${id}_title`)}</span>
                                </Card.Header>
                                <Card.Body className="pt-8 pb-3 ps-0">
                                    <Card.Title className="fs-1">{t(`service_${id}_badge_${j}`)}</Card.Title>
                                </Card.Body>
                            </Card>
                        </Col>
                      );
                    }
                  })}
                </Row>
            </Container>
        </>
    );
};

OurServices.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default OurServices;
