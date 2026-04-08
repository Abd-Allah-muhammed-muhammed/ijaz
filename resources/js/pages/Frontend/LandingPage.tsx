import FrontendLayout from "@/layouts/FrontendLayout";
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import React, { ReactNode, useEffect, useRef } from 'react';
import { Accordion, Badge, Button, Card, Col, Container, Row } from 'react-bootstrap';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';
import './style.css';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faAngleLeft, faAngleRight } from '@fortawesome/free-solid-svg-icons';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation } from 'swiper/modules';

import AOS from 'aos';
import 'aos/dist/aos.css';

import 'swiper/css';
import 'swiper/css/pagination';
import { url, whenLocale } from "@/helpers/general";
import { IconDefinition } from "@fortawesome/fontawesome-svg-core";
import I18nextEffect from "@/lang/I18next-effect";

const LandingPage = ({ showRegisterModal }: { showRegisterModal?: () => void }) => {
  const locale = usePage().props.app.locale;
  const serviceCarouselPrevRef = useRef(null);
  const serviceCarouselNextRef = useRef(null);
  const customerReviewsCarousePrevlRef = useRef(null);
  const customerReviewsCarouselNextRef = useRef(null);
  const ourPartnerCarouselPrevRef = useRef(null);
  const ourPartnerCarouselNextRef = useRef(null);
  const { t } = useTranslation();

  const handleShowModal = () => showRegisterModal?.();


  useEffect(() => {
    AOS.init({
      duration: 800,
      once: true,
    });
  }, []);

  return (
    <I18nextEffect>
      <Head title={t('home')} />
      <Container id="home" className="bg-primary one-side-border-bottom-lg overflow-hidden px-10 pb-20" fluid data-pan="home-page">
        <Container className="h-100 pt-20">
          <div className="position-absolute" style={{ top: 0, bottom: 0, left: 0, right: 0, marginTop: "9rem", pointerEvents: "none" }}>
            <video autoPlay={true} muted loop playsInline={true} className="opacity-75">
              <source src="/media/landing/intro.mp4" type="video/mp4" />
            </video>
          </div>
          <Row className="h-100">
            <Col xxl={6} xl={8}>
              <p className="fs-4x text-white" style={{
                // paddingLeft: (['ar', 'ur']).includes(locale.toLowerCase()) ? "15rem" : "0",
                // paddingRight: !(['ar', 'ur']).includes(locale.toLowerCase()) ? "15rem" : "0"
              }}>{t('discover_your_journey')}</p>
              <p className="fs-2 text-white">{t('are_you_looking_for_services')}</p>
              <div className="d-flex flex-md-row flex-column ms-md-6 gap-6">
                <Button variant="success" onClick={handleShowModal}>
                  {t('try_the_platform')}
                  <svg className="ms-3" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <g opacity="0.4">
                      <path
                        d="M13 10.9998L21.2 2.7998"
                        stroke="white"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      ></path>
                      <path
                        d="M21.9992 6.8V2H17.1992"
                        stroke="white"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      ></path>
                    </g>
                    <path
                      d="M11 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22H15C20 22 22 20 22 15V13"
                      stroke="white"
                      strokeWidth="1.5"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    ></path>
                  </svg>
                </Button>

                <a className="btn btn-dark text-nowrap text-white" href="#">
                  {t('view_our_services')}
                  <svg className="ms-3" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M18.07 14.43L12 20.5L5.93 14.43"
                      stroke="white"
                      strokeWidth="1.5"
                      strokeMiterlimit="10"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    ></path>
                    <path
                      opacity="0.4"
                      d="M12 3.5V20.33"
                      stroke="white"
                      strokeWidth="1.5"
                      strokeMiterlimit="10"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    ></path>
                  </svg>
                </a>
              </div>
            </Col>
            {/*<Col md={6} className="d-flex flex-column justify-content-end">*/}
            {/*  <img src="/media/landing/home.svg" alt="Image" style={{width: "866px", maxWidth: "100%"}}/>*/}
            {/*</Col>*/}
          </Row>
        </Container>
      </Container>
      <Container id="about" className="p-10">
        <Row className="mt-17">
          <Col lg={8} xs={12} data-aos={['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"} data-aos-offset="300">
            <div className="fs-4x align-items-start"
              dangerouslySetInnerHTML={{ __html: t('with_ijaz_hand_over_your_worries') }}></div>
            <div className="mt-10 position-relative bg-white fs-2" id="home_about">
              <p>{t('ijaz_platform_is_an_officially_licensed_destination_for_business_integration')}</p>
              <Link href={GeneralController.aboutUs().url} className="btn btn-success">
                {t('learn_more_about_anjizha')}
                <FontAwesomeIcon icon={locale === 'ar' ? faAngleLeft : faAngleRight} />
              </Link>
            </div>
          </Col>
          <Col lg={4} xs={12} className="mt-15 mt-md-0 d-none d-sm-block" data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"} data-aos-offset="300">
            <img style={{ width: "567px", maxWidth: "100%" }} src="/media/landing/about.svg" alt="about_image" />
          </Col>
        </Row>
      </Container>
      <Container id="our-services" data-aos="fade-up">
        <div className="mt-17">
          <p
            className="fs-3x mb-0 bg-white px-10 pt-15 w-fit-content text-center fw-bold"
            style={{ borderTopLeftRadius: '52px', borderTopRightRadius: '52px' }}
            dangerouslySetInnerHTML={{ __html: t('we_provide_services') }}
          >
          </p>
          <Row id="services-container" className="mx-0 flex-nowrap gap-6 bg-white p-10">
            <Swiper
              slidesPerView={3}
              spaceBetween={10}
              pagination={{
                clickable: true,
              }}
              breakpoints={{
                0: {
                  slidesPerView: 1,
                },
                1200: {
                  slidesPerView: 3,
                },
                1400: {
                  slidesPerView: 4,
                },
              }}
              className="mySwiper"
              modules={[Navigation]}
              navigation={{
                prevEl: serviceCarouselPrevRef.current,
                nextEl: serviceCarouselNextRef.current,
              }}
              onBeforeInit={(swiper) => {
                // @ts-expect-error ts(2339)
                swiper.params.navigation.prevEl = serviceCarouselPrevRef.current;
                // @ts-expect-error ts(2339)
                swiper.params.navigation.nextEl = serviceCarouselNextRef.current;
              }}>
              {([1, 2, 3, 4]).map((i) => (
                <SwiperSlide key={i}>
                  <Card className="w-100 px-4 border-0 bg-transparent h-100" data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"} data-aos-delay={(i + 2) * 100}>
                    <Card.Header className="bg-primary d-flex justify-content-center position-relative">
                      <Card.Img
                        variant="top"
                        src={`/media/landing/service_${i}.svg`}
                        style={{ width: "173px", height: "173px" }}
                      />
                      <Link className="px-10 py-3 bg-success position-absolute service-send cursor-pointer"
                        href={GeneralController.ourService({ service: i }).url}>
                        <img src="/media/landing/send.svg" alt="Send" />
                      </Link>
                    </Card.Header>
                    <Card.Body className="bg-success">
                      {/* @ts-expect-error ts(2339) */}
                      <Card.Title className="fs-3x text-white">{t(`service_${i}_title`)}</Card.Title>
                      <Card.Text>
                        {/* @ts-expect-error ts(2339) */}
                        {([1, 2, 3]).map((j) => (<Badge className="bg-warning text-white rounded rounded-3 me-2" key={i + "-" + j}>{t(`service_${i}_badge_${j}`)}</Badge>))}
                      </Card.Text>
                    </Card.Body>
                  </Card>
                </SwiperSlide>
              ))}
            </Swiper>
          </Row>
          <div className="d-flex justify-content-between me-0 mt-6 mb-5 pb-5">
            <div className="d-flex gap-2">
              <button type="button" ref={serviceCarouselPrevRef} className="btn bg-white">&lt;</button>
              <button type="button" ref={serviceCarouselNextRef} className="btn bg-white">&gt;</button>
            </div>
            <Link href={GeneralController.ourServices().url} className="btn btn-lg w-auto bg-white px-15">
              {t('show_all')}
              <FontAwesomeIcon icon={whenLocale<IconDefinition>('ar', faAngleLeft, faAngleRight)} />
            </Link>
          </div>
        </div>
      </Container>

      <Container id="guarantee" className="mt-20">
        <Row>
          <Col xl={7} data-aos={['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <div className="d-flex align-items-center gap-4 mb-7">
              <img src="/media/landing/success.svg" alt="Success" />
              <p
                className="d-inline-block text-success fs-3x fw-bold mb-0">{t('ijaz_guarantee_your_financial_partner')}</p>
            </div>
            <div className="fs-2 position-relative bg-white px-8 py-10" style={{ borderRadius: '31px' }}
              id="guarantee-container">
              <div dangerouslySetInnerHTML={{ __html: t('with_ijaz_we_guarantee') }}></div>
              <Link href={GeneralController.aboutUs().url} className="btn btn-success">
                {t('learn_more_about_anjizha')}
                <FontAwesomeIcon icon={locale === 'ar' ? faAngleLeft : faAngleRight} />
              </Link>
            </div>
          </Col>
          <Col xl={5} data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <img src="/media/landing/Guarantee.svg" alt="Guarantee" className="w-100" />
          </Col>
        </Row>
      </Container>
      <Container id="jobs" className="mt-20">
        <Row className="align-items-center">
          <Col xl={3} data-aos={['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <img src="/media/landing/easiest_1.svg" alt="Easiest" className="w-100" />
          </Col>
          <Col xl={6} data-aos="slide-up">
            <div className="d-flex justify-content-center align-items-center gap-4 mb-3">
              <img src="/media/landing/note-favorite.svg" alt="Note" />
              <p className="d-inline-block text-success fs-2x fw-bold w-fit-content mb-0"
                dangerouslySetInnerHTML={{ __html: t('easiest_way_to_get_a_job') }}>
              </p>
            </div>

            <div className="fs-3 position-relative bg-white px-8 py-10" style={{ borderRadius: '31px' }}
              id="easiest-container">
              <div
                dangerouslySetInnerHTML={{ __html: t('ijaz_is_one_of_the_biggest_platforms_specialized_in_posting_jobs') }}></div>
              <Link href={GeneralController.aboutUs().url} className="btn btn-success">
                {t('learn_more_about_anjizha')}
                <FontAwesomeIcon icon={locale === 'ar' ? faAngleLeft : faAngleRight} />
              </Link>
            </div>
          </Col>
          <Col className="d-none" xl={3} data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <img src="/media/landing/easiest_2.svg" alt="Easiest" className="w-100" />
          </Col>
        </Row>
      </Container>

      <Container id="attendance" className="mt-20">
        <Row className="flex-column-reverse flex-xl-row">
          <Col xl={7} data-aos={['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <div className="d-flex align-items-center gap-4 mb-7">
              <img src="/media/landing/success.svg" alt="Success" />
              <p
                className="d-inline-block text-success fs-3x fw-bold mb-0">{t('manage_attendance_and_departure')}</p>
            </div>
            <div className="fs-2 position-relative bg-white px-8 py-10" style={{ borderRadius: '31px' }}
              id="attendance-container">
              <div
                dangerouslySetInnerHTML={{ __html: t('ijaz_provides_an_integrated_solution_for_managing_employee_attendance') }}></div>
              <Link href={GeneralController.aboutUs().url} className="btn btn-success">
                {t('learn_more_about_anjizha')}
                <FontAwesomeIcon icon={locale === 'ar' ? faAngleLeft : faAngleRight} />
              </Link>
            </div>
          </Col>
          <Col xl={5} data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <img src="/media/landing/Attendance.svg" alt="Attendance" className="w-100" />
          </Col>
        </Row>
      </Container>

      <Container id="cards" className="mt-20">
        <Row>
          <Col xl={5} data-aos={['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <img src="/media/landing/Cards.svg" alt="Cards" className="w-100" />
          </Col>
          <Col xl={7} data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"}>
            <div className="d-flex align-items-center gap-4 mb-7">
              <img src="/media/landing/card-tick.svg" alt="card tick" />
              <p
                className="d-inline-block text-success fs-3x fw-bold mb-0">{t('get_all_your_digital_cards_in_one_place')}</p>
            </div>
            <div className="fs-2 position-relative bg-white px-8 py-10" style={{ borderRadius: '31px' }}
              id="attendance-container">
              <div dangerouslySetInnerHTML={{ __html: t('top_up_balance') }}></div>
              <Link href={GeneralController.aboutUs().url} className="btn btn-success">
                {t('learn_more_about_anjizha')}
                <FontAwesomeIcon icon={locale === 'ar' ? faAngleLeft : faAngleRight} />
              </Link>
            </div>
          </Col>
        </Row>
      </Container>
      <br />
      <br />
      <Container className="bg-dark position-relative my-20 p-10" style={{ borderRadius: '52px', minHeight: '360px' }} data-aos="fade-up">
        <div className="w-md-50 w-100">
          <p className="fs-3x mb-0 text-white">{t('more_than_just_a_platform')}</p>
          <p className="fs-3x text-success">{t('your_partners_in_success')}</p>
          <p
            className="fs-1 text-white">{t('ijaz_is_an_all_in_one_platform_that_provides_you_with_everything_you_need')}</p>
          <div className="d-flex flex-md-row flex-column gap-3">
            <Button variant="success" onClick={handleShowModal}>
              <svg className="me-2" width="24" height="24" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <g opacity="0.4">
                  <path
                    d="M13 10.9998L21.2 2.7998"
                    stroke="white"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  ></path>
                  <path
                    d="M21.9992 6.8V2H17.1992"
                    stroke="white"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  ></path>
                </g>
                <path
                  d="M11 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22H15C20 22 22 20 22 15V13"
                  stroke="white"
                  strokeWidth="1.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
              </svg>
              {t('try_the_platform')}
            </Button>
            <Link href={GeneralController.index().url} className="btn btn-text-dark bg-white">
              {t('create_an_account')}
              <svg className="ms-2" width="24" height="24" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                  stroke="#3F4254"
                  strokeWidth="1.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
                <path
                  opacity="0.4"
                  d="M3.41 22C3.41 18.13 7.26 15 12 15C12.96 15 13.89 15.13 14.76 15.37"
                  stroke="#3F4254"
                  strokeWidth="1.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
                <path
                  d="M22 18C22 18.32 21.96 18.63 21.88 18.93C21.79 19.33 21.63 19.72 21.42 20.06C20.73 21.22 19.46 22 18 22C16.97 22 16.04 21.61 15.34 20.97C15.04 20.71 14.78 20.4 14.58 20.06C14.21 19.46 14 18.75 14 18C14 16.92 14.43 15.93 15.13 15.21C15.86 14.46 16.88 14 18 14C19.18 14 20.25 14.51 20.97 15.33C21.61 16.04 22 16.98 22 18Z"
                  stroke="#3F4254"
                  strokeWidth="1.5"
                  strokeMiterlimit="10"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
                <path
                  d="M19.49 17.98H16.51"
                  stroke="white"
                  strokeWidth="1.5"
                  strokeMiterlimit="10"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
                <path
                  d="M18.0002 16.52V19.51"
                  stroke="white"
                  strokeWidth="1.5"
                  strokeMiterlimit="10"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                ></path>
              </svg>
            </Link>
          </div>
          <div id="home_details_image_container"
            className="position-md-absolute bg-white p-0 mt-15 mt-md-0 overflow-hidden">
            <img src="/media/landing/home-details.svg" alt="details-image" style={{ width: '100%', height: '100%' }} />
          </div>
        </div>
      </Container>
      <Container id="customer-reviews" className="mb-20">
        <p className="fs-4x">{t('we_make_a_positive_impact_on_society')}</p>
        <Row className="d-flex gap-3 overflow-x-scroll p-10 flex-nowrap">
          <Swiper
            slidesPerView={1}
            spaceBetween={10}
            pagination={{
              clickable: true,
            }}
            breakpoints={{
              0: {
                slidesPerView: 1,
              },
              992: {
                slidesPerView: 3,
              },
              1400: {
                slidesPerView: 4,
              },
            }}
            className="mySwiper"
            modules={[Navigation]}
            navigation={{
              prevEl: customerReviewsCarousePrevlRef.current,
              nextEl: customerReviewsCarouselNextRef.current,
            }}
            onBeforeInit={(swiper) => {
              // @ts-expect-error ts(2339)
              swiper.params.navigation.prevEl = customerReviewsCarousePrevlRef.current;
              // @ts-expect-error ts(2339)
              swiper.params.navigation.nextEl = customerReviewsCarouselNextRef.current;
            }}>
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
              <SwiperSlide key={i}>
                <Card key={i} style={{ borderRadius: '32px', overflow: 'hidden' }} className="w-100 h-100" data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"} data-aos-delay={(i + 2) * 100}>
                  <Card.Body>
                    <Card.Title className="fs-1">{el.title}</Card.Title>
                    <Card.Text>{el.body}</Card.Text>
                  </Card.Body>
                  <Card.Footer>
                    <div className="d-flex justify-content-between">
                      <div className="d-flex gap-3 flex-grow-1">
                        <img
                          style={{
                            width: "48px",
                            height: "48px",
                          }}
                          src="/media/landing/Customer.jpg"
                          alt="icon"
                        />
                        <div className="d-flex flex-column justify-content-center gap-2 flex-grow-1">
                          <p className="fw-bold mb-0">{el.name}</p>
                        </div>
                      </div>
                      <div className="d-flex align-items-center gap-1">
                        <p className="fw-bold mb-0">5.0</p>
                        <svg
                          className="ms-2"
                          width="22"
                          height="22"
                          viewBox="0 0 22 22"
                          fill="none"
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path
                            d="M10.5565 0.851822C10.7433 0.493021 11.2567 0.493021 11.4435 0.851822L14.5245 6.76948C14.5969 6.9087 14.7306 7.00581 14.8854 7.03172L21.4655 8.13324C21.8645 8.20002 22.0231 8.68827 21.7396 8.97681L17.0636 13.7356C16.9536 13.8476 16.9026 14.0047 16.9258 14.16L17.9115 20.7584C17.9713 21.1585 17.556 21.4602 17.1939 21.2797L11.2231 18.3032C11.0826 18.2332 10.9174 18.2332 10.7769 18.3032L4.80606 21.2797C4.44403 21.4602 4.0287 21.1585 4.08847 20.7584L5.07423 14.16C5.09742 14.0047 5.04637 13.8476 4.93636 13.7356L0.260406 8.97681C-0.0231071 8.68827 0.135534 8.20002 0.5345 8.13324L7.1146 7.03172C7.2694 7.00581 7.40306 6.9087 7.47554 6.76948L10.5565 0.851822Z"
                            fill="#F7C000"
                          ></path>
                        </svg>
                      </div>
                    </div>
                  </Card.Footer>
                </Card>
              </SwiperSlide>
            ))}
          </Swiper>
        </Row>

        <div className="d-flex justify-content-between me-0 mt-6 mb-5 pb-5">
          <div className="d-flex gap-2">
            <button type="button" ref={customerReviewsCarousePrevlRef} className="btn bg-white">&lt;</button>
            <button type="button" ref={customerReviewsCarouselNextRef} className="btn bg-white">&gt;</button>
          </div>
          <Link href={GeneralController.customerReviews().url} className="btn btn-lg w-auto bg-white px-15">
            {t('show_all')}
            <FontAwesomeIcon icon={locale === 'ar' ? faAngleLeft : faAngleRight} />
          </Link>
        </div>
      </Container>

      <Container id="partners" className="mb-20">
        <p className="fs-4x">{t('our_partners_in_success')}</p>
        <Row className="d-flex gap-5 overflow-x-scroll p-10 flex-nowrap">
          <Swiper
            slidesPerView={1}
            spaceBetween={10}
            pagination={{
              clickable: true,
            }}
            breakpoints={{
              0: {
                slidesPerView: 1,
              },
              576: {
                slidesPerView: 2,
              },
              992: {
                slidesPerView: 4,
              },
            }}
            className="mySwiper"
            modules={[Navigation]}
            navigation={{
              prevEl: ourPartnerCarouselPrevRef.current,
              nextEl: ourPartnerCarouselNextRef.current,
            }}
            onBeforeInit={(swiper) => {
              // @ts-expect-error ts(2339)
              swiper.params.navigation.prevEl = ourPartnerCarouselPrevRef.current;
              // @ts-expect-error ts(2339)
              swiper.params.navigation.nextEl = ourPartnerCarouselNextRef.current;
            }}>
            {([1, 2, 3, 4]).map(i => (
              <SwiperSlide key={i}>
                <Card className="w-100 h-100 pt-5"
                  style={{ borderRadius: '32px', overflow: 'hidden', textAlign: 'center' }}
                  data-aos={!['ar', 'ur'].includes(locale) ? 'fade-left' : "fade-right"} data-aos-delay={(i + 2) * 100}>
                  <Card.Body className="text-center d-flex flex-column justify-content-between">
                    <Card.Img
                      variant="bottom"
                      style={{ width: '150px', alignSelf: 'center' }}
                      src={`/media/landing/our_partner_${i}.svg`}
                    />
                    {/* @ts-expect-error ts(2339) */}
                    <p className="fs-3">{t(`our_partner_${i}_name`)}</p>
                  </Card.Body>
                </Card>
              </SwiperSlide>
            ))}
          </Swiper>
        </Row>
        <div className="d-flex justify-content-between me-0 mt-6 mb-5 pb-5">
          <div className="d-flex gap-2">
            <button type="button" ref={ourPartnerCarouselPrevRef} className="btn bg-white">&lt;</button>
            <button type="button" ref={ourPartnerCarouselNextRef} className="btn bg-white">&gt;</button>
          </div>
        </div>
      </Container>

      <Container id="faq" className="mb-20 bg-white p-10" style={{ borderRadius: '52px' }}>
        <Row>
          <Col xl={7} xs={12}>
            <p className="fs-4x">{t('we_answer_your_question')}</p>
            <Accordion>
              {Array.from({ length: 14 }, (_, i) => (
                <Accordion.Item className="mb-5" eventKey={"question-" + i} key={i}
                  data-aos="fade-up" data-aos-delay={(i + 2) * 100} data-aos-anchor="#faq">
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
                      {/* @ts-expect-error ts(2339) */}
                      <p className="fs-2x mb-0 text-black">{t(`question_${i + 1}_header`)}</p>
                    </div>
                  </Accordion.Header>
                  {/* @ts-expect-error ts(2339) */}
                  <Accordion.Body className="fs-4" dangerouslySetInnerHTML={{ __html: t(`question_${i + 1}_body`) }}>
                  </Accordion.Body>
                </Accordion.Item>
              ))}
            </Accordion>
          </Col>
          <Col xl={5} xs={12} className="p-9 d-none d-sm-block">
            <img style={{ width: '100%' }} src={url('/media/landing/FAQ-image.svg')} alt="image" />
          </Col>
        </Row>
      </Container>
    </I18nextEffect>
  );
};

LandingPage.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default LandingPage;
