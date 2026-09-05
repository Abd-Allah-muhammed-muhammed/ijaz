import FrontendLayout from '@/apps/web/layouts/FrontendLayout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Accordion, Col, Container, Row } from 'react-bootstrap';
import './style.css';

const Help = () => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('help')} />
      <Container className="bg-primary mt-0 one-side-border-bottom-lg" style={{ paddingTop: '60px', minHeight: '290px' }} fluid>
        <Container className="h-100 pt-20 w-fit-content m-auto position-relative">
          <p className="text-white fs-5x text-center mb-0 w-fit-content">{t('help')}</p>
          <div className="underline-warning"></div>
        </Container>
      </Container>
      <Container data-pan="help-page">
        <div className="py-20 line-height-lg">
          <Row className="mt-10 px-20 py-10 bg-white" style={{ borderRadius: '33px' }}>
            <Col xxl={7} xs={12}>
              <p className="fs-4x">{t('we_answer_your_question')}</p>
              <Accordion>
                {Array.from({ length: 14 }, (_el, i) => (
                  <Accordion.Item className="mb-5" eventKey={`question-${i}`} key={i}>
                    <Accordion.Header>
                      <p className="fs-2x mb-0 text-black">{t(`question_${i + 1}_header`)}</p>
                    </Accordion.Header>
                    <Accordion.Body
                      className="fs-4"
                      dangerouslySetInnerHTML={{ __html: t(`question_${i + 1}_body`) }}
                    />
                  </Accordion.Item>
                ))}
              </Accordion>
            </Col>
            <Col xxl={5} xs={12} className="p-9">
              <img style={{ width: '100%' }} src="/media/landing/FAQ-image.svg" alt="" />
            </Col>
          </Row>
        </div>
      </Container>
    </>
  );
};

Help.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default Help;
