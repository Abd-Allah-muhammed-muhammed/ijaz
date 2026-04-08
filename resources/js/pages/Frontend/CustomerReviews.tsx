import FrontendLayout from "@/layouts/FrontendLayout";
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import React, { ReactNode } from 'react';
import './style.css';
import { Card, Col, Container, Row } from 'react-bootstrap';

const CustomerReviews = () => {
  const { t } = useTranslation();
  return (
      <>
          <Head title={t('about_us')} />
          <Container>
            <div className="pt-20 d-flex justify-content-between align-items-center">
              <h3 className="fs-4x">{t('customer_reviews')}</h3>
            </div>
          </Container>

          <Container className="mt-5 mb-10">
            <Row className="gy-7">
              {[{
                title: "ربط المتاجر الالكترونية ببوابات الدفع و التوصيل",
                body: "تعاملنا مع ايجاز لانشاء متجر الكتروني و ربطه ببوابات الدفع و التوصيل, و كانت العملية سهلة و سريعة. دعمهم الفني ممتاز.",
                name: "عبدالعزيز الاشقر",
              },
              {
                title: "برمجة تطبيقات احترافية",
                body: "خدمة برمجة التطبيقات عبر ايجاز كانت احترافية جدا, حصلنا على تطبيق متكامل بميزات متطورة و باداء عالي.",
                name: "احمد الغامدي",
              },
              {
                title: "دخل اضافي من تقديم الخدمات عبر منصة ايجاز",
                body: "بفضل منصة ايجاز, تمكنت من تحقيق دخل اضافي من تقديم خدمات التصميم و التسويق الرقمي. المنصة تربطك بعملاء جادين و تضمن عمليات دفع امنة.",
                name: "شروق القحطاني",
              },
              {
                title: "منصة رائعة لزيادة الدخل كمزود خدمة",
                body: "انضمامي الي ايجاز كمزود خدمة ساعدني في الوصول الى عملاء جدد و زيادة دخلي بشكل ملحوظ. المنصة توفر بيئة عمل احترافية و سهلة الاستخدام.",
                name: "عبدالهادي الغامدي",
              },
              {
                title: "تصديق العقود و الوثائق الحكومية",
                body: "احتجت الي تصديق بعض العقود الرسمية, و ساعدني فريق ايجاز في انهاء الاجراءات بسرعة و بدون اي تعقيدات.",
                name: "راشد الهذلي",
              },
              {
                title: "اصدار تاشيرات العمل للموظفين",
                body: "خدمة اصدار تاشيرات العمل عبر ايجاز كانت مميزة جدا. وفروا لي كل التفاصيل المطلوية و سهلوا علي الاجراءات الحكومية.",
                name: "رائد الزهراني",
              },
              {
                title: "خدمات لوجستية و تنظيم عمليات المنشات",
                body: "طلبت خدمات لوجستية و تنظيم العمليات عبر منصة ايجاز, و كانت التجربة احترافية جدا, مما ساعدني في تحسين اداء منشأتي.",
                name: "سلطان الدوسري",
              },
              {
                title: "استخراج التراخيص و تجديد السجلات التجارية",
                body: "منصة ايجاز قدمت لي حلولا سريعة في استخراج التراخيص و تجديد السجلات, دون الحاجة الي متابعة الاجراءات المعقدة بنفسي.",
                name: "الجوهرة بنت سعود",
              }
            ].map((el, i) => (
                <Col xl={3} lg={4} md={6} xs={12} className="h-100" style={{minHeight: "100%"}}>
                  <Card key={i} style={{ borderRadius: '32px', overflow: 'hidden' }} className="h-100">
                    <Card.Body>
                      <Card.Title className="fs-1">{el.title}</Card.Title>
                      <Card.Text>{el.body}</Card.Text>
                    </Card.Body>
                    <Card.Footer>
                      <div className="d-flex justify-content-between">
                        <div className="d-flex gap-3">
                          <img
                            width={48}
                            height={48}
                            src="https://api.anjizha.com/storage/image/website/customer_opinions/eMJ3dQVouYWstO6CyXnX8wwme6KfCPQPGn0sY1SE.jpg"
                            alt="icon"
                          />
                          <div className="d-flex flex-column justify-content-center gap-2">
                            <p className="fw-bold mb-0">{el.name}</p>
                          </div>
                        </div>
                        <div className="d-flex align-items-center gap-1">
                          <p className="fw-bold mb-0">5.0</p>
                          <svg className="ms-2" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                              d="M10.5565 0.851822C10.7433 0.493021 11.2567 0.493021 11.4435 0.851822L14.5245 6.76948C14.5969 6.9087 14.7306 7.00581 14.8854 7.03172L21.4655 8.13324C21.8645 8.20002 22.0231 8.68827 21.7396 8.97681L17.0636 13.7356C16.9536 13.8476 16.9026 14.0047 16.9258 14.16L17.9115 20.7584C17.9713 21.1585 17.556 21.4602 17.1939 21.2797L11.2231 18.3032C11.0826 18.2332 10.9174 18.2332 10.7769 18.3032L4.80606 21.2797C4.44403 21.4602 4.0287 21.1585 4.08847 20.7584L5.07423 14.16C5.09742 14.0047 5.04637 13.8476 4.93636 13.7356L0.260406 8.97681C-0.0231071 8.68827 0.135534 8.20002 0.5345 8.13324L7.1146 7.03172C7.2694 7.00581 7.40306 6.9087 7.47554 6.76948L10.5565 0.851822Z"
                              fill="#F7C000"
                            ></path>
                          </svg>
                        </div>
                      </div>
                    </Card.Footer>
                  </Card>
                </Col>
              ))}
            </Row>
          </Container>
      </>
  );
};

CustomerReviews.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default CustomerReviews;
