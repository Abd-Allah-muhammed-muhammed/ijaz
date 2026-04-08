import React, { ReactNode, useEffect, useState } from 'react';
import { Container, Navbar, Nav, Row, Col, Modal, Dropdown, Badge } from 'react-bootstrap';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';
import { Link, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBars } from '@fortawesome/free-solid-svg-icons';

import './style.css';
import { scrollToDiv } from '@/helpers/general';
import LoadingScreen from '@/layouts/loading-screen/LoadingScreen';
import FrontendAuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';
import ProviderAuthController from '@/actions/App/Http/Controllers/Provider/AuthController';
import LangDropdown from '@/layouts/Lang-dropdown';
import { useTranslation } from 'react-i18next';
const FrontendLayout = ({ children }: { children: ReactNode }) => {
    const { url, props } = usePage();
    const locale = props?.app?.locale;
    const isHome : boolean = url.match(new RegExp(`${locale}/?(#.*)?$`))?.length != undefined;
    const [isLoading, SetIsLoading] = useState(document.readyState !== "complete");
    const [hash, setHash] = useState(window.location.hash);
    const [showModal, setShowModal] = useState(false);

    const handleCloseModal = () => setShowModal(false);
    const handleShowModal = () => setShowModal(true);
    const { t } = useTranslation();
    function handleNavbarLinkClick(e: React.MouseEvent<Element, MouseEvent>, id: string) {
      if(isHome) {
        e.preventDefault();
        setHash(id)
      }
    }

    function isUrlMatch(route: string) : boolean {
      return url.replace(new RegExp(`/${locale}/?`), "") == route.replace(/^\//, "");
    }

    useEffect(() => {
      if(hash && !isLoading) {
        scrollToDiv(hash);
      }
    }, [hash, isLoading]);

    window.addEventListener("load", function(){
      SetIsLoading(false);
    })

    return (
        <div className="bg-gray overflow-hidden">
            {isLoading && <LoadingScreen />}
            <Navbar id="navbar" className="bg-body-tertiary bg-primary fixed-top px-5 py-5" expand="lg">
                <Container>
                    <Navbar.Brand href="#home">
                        <img src="/logo-no-bg.svg" alt="Logo" style={{ width: '105px' }} />
                    </Navbar.Brand>
                    <Navbar.Toggle aria-controls="basic-navbar-nav">
                        <FontAwesomeIcon icon={faBars} className="fs-3x text-white" />{' '}
                    </Navbar.Toggle>
                    <Navbar.Collapse id="basic-navbar-nav" className="justify-content-between w-auto" style={{ minWidth: '80%', flexGrow: '0' }}>
                        <Nav className="fs-5 mb-md-0 mb-5 gap-5">
                            <Link
                                className={`nav-link fw-bold text-white ${isUrlMatch(GeneralController.index().url) && ((isHome && hash === "#home") || !hash) ? "active" : ""}`}
                                href={GeneralController.index().url}
                                onClick={(e) => handleNavbarLinkClick(e, '#home')}
                                onFinish={()=>{
                                  setHash('#home');
                                }}
                            >
                                {t('home')}
                            </Link>
                            <Link
                                className={`nav-link fw-bold text-white ${isUrlMatch(GeneralController.aboutUs().url) || isHome && hash === '#about' ? "active" : ""}`}
                                href={GeneralController.index().url}
                                onClick={(e) => handleNavbarLinkClick(e, '#about')}
                                onFinish={()=>{
                                  setHash('#about');
                                }}
                            >
                                {t('about')}
                            </Link>
                            <Link
                                className={`nav-link fw-bold text-white ${isUrlMatch(GeneralController.ourServices().url) || isHome && hash === '#our-services' ? "active" : ""}`}
                                href={GeneralController.index().url}
                                onClick={(e) => handleNavbarLinkClick(e, '#our-services')}
                                onFinish={()=>{
                                  setHash('#our-services');
                                }}
                            >
                                {t('our_services')}
                            </Link>
                            {/*<Link*/}
                            {/*    className={`nav-link fw-bold text-white ${isHome && hash === '#guarantee' ? "active" : ""}`}*/}
                            {/*    href={GeneralController.index().url}*/}
                            {/*    onClick={(e) => handleNavbarLinkClick(e, '#guarantee')}*/}
                            {/*    onFinish={()=>{*/}
                            {/*      setHash('#guarantee');*/}
                            {/*    }}*/}
                            {/*>*/}
                            {/*    {t('guarantee')}*/}
                            {/*</Link>*/}
                            {/*<Link*/}
                            {/*    className={`nav-link fw-bold text-white ${isHome && hash === '#jobs' ? "active" : ""}`}*/}
                            {/*    href={GeneralController.index().url}*/}
                            {/*    onClick={(e) => handleNavbarLinkClick(e, '#jobs')}*/}
                            {/*    onFinish={()=>{*/}
                            {/*      setHash('#jobs');*/}
                            {/*    }}*/}
                            {/*>*/}
                            {/*    {t('jobs')}*/}
                            {/*</Link>*/}
                            {/*<Link*/}
                            {/*    className={`nav-link fw-bold text-white ${isHome && hash === '#attendance' ? "active" : ""}`}*/}
                            {/*    href={GeneralController.index().url}*/}
                            {/*    onClick={(e) => handleNavbarLinkClick(e, '#attendance')}*/}
                            {/*    onFinish={()=>{*/}
                            {/*      setHash('#attendance');*/}
                            {/*    }}*/}
                            {/*>*/}
                            {/*    {t('attendance_and_departure')}*/}
                            {/*</Link>*/}
                            {/*<Link*/}
                            {/*    className={`nav-link fw-bold text-white ${isHome && hash === '#cards' ? "active" : ""}`}*/}
                            {/*    href={GeneralController.index().url}*/}
                            {/*    onClick={(e) => handleNavbarLinkClick(e, '#cards')}*/}
                            {/*    onFinish={()=>{*/}
                            {/*      setHash('#cards');*/}
                            {/*    }}*/}
                            {/*>*/}
                            {/*    {t('digital_cards')}*/}
                            {/*</Link>*/}
                            <Link
                                className={`nav-link fw-bold text-white ${isHome && hash === '#customer-reviews' ? "active" : ""}`}
                                href={GeneralController.index().url}
                                onClick={(e) => handleNavbarLinkClick(e, '#customer-reviews')}
                                onFinish={()=>{
                                  setHash('#customer-reviews');
                                }}
                            >
                                {t('customer_reviews')}
                            </Link>
                            {/*<Link*/}
                            {/*      className={`nav-link fw-bold text-white ${isHome && hash === '#partners' ? "active" : ""}`}*/}
                            {/*      href={GeneralController.index().url}*/}
                            {/*      onClick={(e) => handleNavbarLinkClick(e, '#partners')}*/}
                            {/*      onFinish={()=>{*/}
                            {/*        setHash('#partners');*/}
                            {/*      }}*/}
                            {/*  >*/}
                            {/*      {t('success_partners')}*/}
                            {/*  </Link>*/}
                            <Link
                                className={`nav-link fw-bold text-white ${isHome && hash === '#faq' ? "active" : ""}`}
                                href={GeneralController.index().url}
                                onClick={(e) => handleNavbarLinkClick(e, '#faq')}
                                onFinish={()=>{
                                  setHash('#faq');
                                }}
                            >
                                {t('faq')}
                            </Link>
                        </Nav>
                        <div className="d-flex g-0 align-items-center gap-0 flex-column flex-lg-row">
                            <Row className="w-fit-content flex-column flex-md-row ms-md-0 gap-2 m-auto flex-nowrap align-items-center justify-content-center w-100 me-0 me-sm-2">
                                <Col md={'auto'} className="px-1">
                                    <Nav.Link className="btn btn-success fs-3 rounded-15 text-nowrap" href={ProviderAuthController.login().url}>
                                        <img src="/media/landing/user-square.svg" alt="User Square" className="me-2" />
                                        {t('sign_in')}
                                    </Nav.Link>
                                </Col>
                                <Col md={'auto'} className="px-1">
                                    <Nav.Link data-pan="register-btn" className="btn btn-warning fs-3 rounded-15 text-nowrap" href="#" onClick={handleShowModal}>
                                        <img src="/media/landing/profile-add.svg" alt="Profile Add" className="me-2" />
                                        {t('register')}
                                    </Nav.Link>
                                </Col>
                            </Row>
                            <LangDropdown />
                        </div>
                    </Navbar.Collapse>
                </Container>
            </Navbar>
            <div id="main-content">{React.cloneElement(children, {showRegisterModal: handleShowModal})}</div>

            <Modal show={showModal} onHide={handleCloseModal} aria-labelledby="contained-modal-title-vcenter" centered size={"lg"}>
              <Modal.Header className="border-bottom-0" closeButton>
                <Modal.Title className="fs-3x text-center w-100 text-success">{t('try_platform_as')}</Modal.Title>
              </Modal.Header>
              <Modal.Body>
                <Row className="gap-5 px-5">
                  <Col className="px-5 bg-opacity-20 bg-success pt-5">
                    <Link href="#" data-pan="service-seeker-btn">
                      <img className="w-100" src="/media/landing/Seeker.svg" alt="Service Seeker Image"/>
                      <div className="d-flex align-items-center justify-content-center gap-2 flex-column flex-lg-row pb-3 mt-4">
                        <p className="fs-1 text-center fw-bold text-success mb-0">{t('service_seeker')}</p>
                        <Badge bg='success' className="text-white h-fit-content">{t('soon')}</Badge>
                      </div>
                    </Link>
                  </Col>

                  <Col className="px-5 bg-opacity-20 bg-success pt-5">
                    <Link href={FrontendAuthController.create().url} data-pan="service-provider-btn">
                      <img className="w-100" src="/media/landing/Provider.svg" alt="Service Provider Image"/>
                      <p className="fs-1 text-center fw-bold mt-4 text-success">{t('service_provider')}</p>
                    </Link>
                  </Col>

                  <Col className="px-5 bg-opacity-20 bg-success pt-5">
                    <Link  href="#" data-pan="service-company-btn">
                      <img className="w-100" src="/media/landing/Company.svg" alt="Service Seeker Image"/>
                      <div className="d-flex align-items-center justify-content-center gap-2 flex-column flex-lg-row pb-3 mt-4">
                        <p className="fs-1 text-center fw-bold text-success mb-0">{t('recruitment_agency')}</p>
                        <Badge bg='success' className="text-white h-fit-content">{t('soon')}</Badge>
                      </div>
                    </Link>
                  </Col>
                </Row>
              </Modal.Body>
            </Modal>

            <footer id="footer" className="bg-secondary fw-bold">
                <Container>
                    <Row>
                        <Col xl={4} xs={12} className="pe-20">
                          <img src="/logo.svg" alt="Logo" width={152} style={{paddingBottom: "24px"}}/>
                          <p className="text-gray-500 fs-3">{t('ijaz_your_faster_solutions')}</p>
                          <p className="text-gray-500 fs-3">{t('at_ijaz_we_work_on_providing_modern_services')}</p>
                          <p className="text-gray-500 fs-3">{t('our_goal_is_to_save_you_time_and_effort')}</p>
                        </Col>
                        <Col xl={8} xs={12}>
                          <Row>
                            <Col xl={3} xs={6}>
                              <p className="text-warning fs-2">{t('ijaz')}</p>
                              <p>
                                <Link className="fs-2 text-white fw-normal" href={GeneralController.index().url}>{t('home')}</Link>
                              </p>
                              <p>
                                <Link className="fs-2 text-white fw-normal" href={GeneralController.aboutUs().url}>{t('who_are_we')}</Link>
                              </p>
                              <p>
                                <Link className="fs-2 text-white fw-normal" href={GeneralController.ourServices().url}>{t('our_services')}</Link>
                              </p>
                            </Col>
                            <Col xl={3} xs={6}>
                              <p className="text-warning fs-2">{t('profile')}</p>
                              <p>
                                <Link className="fs-2 text-white fw-normal" href={GeneralController.customerReviews().url}>{t('customer_reviews')}</Link>
                              </p>
                              {/*<p>*/}
                              {/*  <Link className="fs-2 text-white fw-normal" href={GeneralController.index().url}>{t('apply_for_an_online_meeting')}</Link>*/}
                              {/*</p>*/}
                              <p>
                                <Link className="fs-2 text-white fw-normal" href={GeneralController.privacyAndPolicies().url}>{t('policies_and_privacy_on_the_platform')}</Link>
                              </p>
                              {/*<p>*/}
                              {/*  <Link className="fs-2 text-white fw-normal" href={GeneralController.privacyPolicy().url}>{t('privacy_policy')}</Link>*/}
                              {/*</p>*/}
                              {/*<p>*/}
                              {/*  <Link className="fs-2 text-white fw-normal" href={GeneralController.serviceProviderAuthorizationTermsAndConditions().url}>{t('terms_and_conditions_of_service_provider_authorization')}</Link>*/}
                              {/*</p>*/}
                              {/*<p>*/}
                              {/*  <Link className="fs-2 text-white fw-normal" href={GeneralController.howToUseAgency().url}>{t('how_to_use_the_agency')}</Link>*/}
                              {/*</p>*/}
                              {/*<p>*/}
                              {/*  <Link className="fs-2 text-white fw-normal" href={GeneralController.realEstateMarketplaceTermsOfUse().url}>{t('terms_of_use_for_the_real_estate_marketplace')}</Link>*/}
                              {/*</p>*/}
                            </Col>
                            <Col xl={3} xs={6}>
                              <p className="text-warning fs-2">{t('contact_us')}</p>
                              <p>
                                <a className="fs-2 text-white fw-normal" href="https://wa.me/966543120779" target="_blank">
                                  <img src="/media/landing/Whatsapp.svg" alt="Map" className="me-2"/>
                                  0543120779
                                </a>
                              </p>
                              <p>
                                <Link className="fs-2 text-white fw-normal" href={GeneralController.index().url}>
                                  <img src="/media/landing/map.svg" alt="Map" className="me-2"/>
                                  {t('saudi_arabia_jeddah')}
                                </Link>
                              </p>
                              <p>
                                <a className="fs-2 text-white fw-normal" href="mailto:ijaz.cs.sa@gmail.com">
                                  <img src="/media/landing/sms-tracking.svg" alt="SMS Tracking" className="me-2"/>
                                  ijaz.cs.sa@gmail.com
                                </a>
                              </p>
                            </Col>
                            <Col xl={3} xs={6}>
                              <p className="text-warning fs-2">{t('download_app_now')}</p>
                              <Link className="fs-2 mb-2 w-fit-content h-fit-content d-block" href={GeneralController.index().url}>
                                <img src="/media/landing/GooglePlay.svg" alt="Download app now" className="w-100"/>
                              </Link>

                              <Link className="fs-2 mb-2 w-fit-content h-fit-content d-block" href={GeneralController.index().url}>
                                <img src="/media/landing/AppStore.svg" alt="Download app now" className="w-100"/>
                              </Link>

                              <Link className="fs-2 mb-2 w-fit-content h-fit-content d-block" href={GeneralController.index().url}>
                                <img src="/media/landing/AppGallery.svg" alt="Download app now" className="w-100"/>
                              </Link>
                            </Col>
                          </Row>
                        </Col>
                    </Row>
                    <hr className="my-20 border-white bg-white" />
                    <div className="d-flex justify-content-between fw-bold flex-wrap text-nowrap text-white">
                        <Row className="w-100 justify-content-between align-items-center flex-column flex-lg-row gap-10">
                            <div className="w-auto d-flex align-items-center bg-">
                              <a href="https://www.instagram.com/rakizatijaz.sa/" className="me-2 rounded-circle bg-success p-2" target="_blank">
                                <img src="/media/landing/Instagram.svg" alt="Instagram" />
                              </a>
                              <a href="https://www.tiktok.com/@ijaz5514" className="me-2 rounded-circle bg-success p-2" target="_blank">
                                <img src="/media/landing/Tiktok.svg" alt="Tiktok" />
                              </a>
                              {/*<a href="" className="me-2 rounded-circle bg-success p-2" target="_blank">*/}
                              {/*  <img src="/media/landing/Facebook.svg" alt="Facebook" />*/}
                              {/*</a>*/}
                              <a href="https://x.com/rakizatijaz/highlights" className="me-2 rounded-circle bg-success p-2" target="_blank">
                                <img src="/media/landing/Twitter.svg" alt="Twitter" />
                              </a>
                            </div>
                            <div className="w-auto d-flex align-items-center">
                              <p className="mb-0">{t('all_rights_reserved')}</p>
                            </div>
                            <div className="w-auto d-flex align-items-center gap-4 flex-column">
                              <p className="d-inline-block mb-0 text-center">{t('we_work_with_complete_credibility_and_trust')}</p>
                              <div className="d-flex justify-content-between gap-4">
                                <a href="/media/landing/Certificate.pdf" target="_blank">
                                  <img src="/media/landing/credibility_and_trust.svg" alt="Credibility and Trust" />
                                </a>
                                <a href="https://eauthenticate.saudibusiness.gov.sa/certificate-details/0000200388" target="_blank">
                                  <img src="/media/landing/SaudiBusiness.svg" alt="saudibusiness" />
                                </a>
                              </div>
                            </div>
                        </Row>
                    </div>
                </Container>
            </footer>
        </div>
    );
};

export default FrontendLayout;
