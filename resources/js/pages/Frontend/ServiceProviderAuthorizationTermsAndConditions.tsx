import { useTranslation } from 'react-i18next';
import FrontendLayout from '@/layouts/FrontendLayout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { Container } from 'react-bootstrap';
import './style.css';

const PrivacyAndPolicies = () => {
  const { t } = useTranslation();
    return (
        <>
            <Head title={t('about_us')} />
            <Container className="bg-primary one-side-border-bottom-lg mt-0" style={{ paddingTop: '60px', minHeight: '290px', paddingBottom: '60px' }} fluid>
                <Container className="w-fit-content position-relative m-auto h-100 pt-20">
                    <p className="fs-5x w-fit-content mb-0 text-center text-white">{t('terms_and_conditions_of_service_provider_authorization')}</p>
                    <div className="underline-warning"></div>
                </Container>
            </Container>
            <Container>
                <div className="line-height-lg py-20">
                    <div className="position-relative fs-3 bg-white p-20" style={{ borderRadius: '33px' }}>
                        <div className="text-center">
                            <img src="/media/logos/default.svg" alt="Logo" />
                        </div>
                        <p className="text-success fw-bold">{t('terms_and_conditions_of_service_provider_authorization')}</p>
                        <div dangerouslySetInnerHTML={{__html: t('terms_and_conditions_of_service_provider_authorization_content')}}></div>
                        <span className="top-center-badge bg-success">{t('terms_and_conditions_of_service_provider_authorization')}</span>
                    </div>
                </div>
            </Container>
        </>
    );
};

PrivacyAndPolicies.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default PrivacyAndPolicies;
