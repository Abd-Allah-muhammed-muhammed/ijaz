import FrontendLayout from "@/layouts/FrontendLayout";
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import React, { ReactNode } from 'react';
import './style.css';
import { Badge, Card, Col, Container, Row } from 'react-bootstrap';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';

const OurServices = () => {
  const { t } = useTranslation();
  return (
      <>
          <Head title={t('about_us')} />
          <Container>
            <Row className="pt-20 justify-content-between align-items-center">
              <Col md={9} xs={12}>
                <h3 className="fs-4x">{t('our_services')}</h3>
              </Col>
              <Col md={3} xs={12} className="position-relative">
                <div className="position-absolute top-50 ms-5 translate-middle-y" style={{left: "20px"}}>
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M11 20C15.9706 20 20 15.9706 20 11C20 6.02944 15.9706 2 11 2C6.02944 2 2 6.02944 2 11C2 15.9706 6.02944 20 11 20Z"
                      stroke="#5E6278" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path opacity="0.4"
                          d="M18.9299 20.6898C19.4599 22.2898 20.6699 22.4498 21.5999 21.0498C22.4499 19.7698 21.8899 18.7198 20.3499 18.7198C19.2099 18.7098 18.5699 19.5998 18.9299 20.6898Z"
                          stroke="#5E6278" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                </div>
                <input type="text" className="form-control" />
              </Col>
            </Row>
          </Container>

          <Container className="mt-5 mb-10" data-pan="service-page" >
            <Row className="gy-7">
              {([1, 2, 3, 4]).map(i => (
                <Col xs={12} md={4}>
                  <Link href={GeneralController.ourService({service: i}).url}>
                    <Card style={{ borderRadius: '32px', overflow: 'hidden' }} className="cursor-pointer h-100">
                    <Card.Header className="bg-primary d-flex justify-content-center position-relative py-10">
                      <Card.Img
                        variant="top"
                        src={`/media/landing/service_${i}.svg`}
                        style={{width: "300px", height: "300px"}}
                      />
                    </Card.Header>
                    <Card.Body className="bg-success">
                      <Card.Title className="fs-3x text-white">{t(`service_${i}_title`)}</Card.Title>
                      <Card.Text>
                        {([1, 2, 3]).map(function(j) {
                          const text = t(`service_${i}_badge_${j}`);
                          if (text != `service_${i}_badge_${j}`) {
                            return (<Badge className="bg-warning text-white me-2">{text}</Badge>);
                          }
                        })}
                      </Card.Text>
                    </Card.Body>
                  </Card>
                  </Link>
                </Col>
              ))}
            </Row>
          </Container>
      </>
  );
};

OurServices.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default OurServices;
