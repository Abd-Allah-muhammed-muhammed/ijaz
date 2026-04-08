import { useTranslation } from 'react-i18next';
import FrontendLayout from '@/layouts/FrontendLayout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { Container } from 'react-bootstrap';
import './style.css';

const PrivacyPolicy = () => {
  const { t } = useTranslation();
    return (
        <>
            <Head title={t('about_us')} />
            <Container className="bg-primary one-side-border-bottom-lg mt-0" style={{ paddingTop: '60px', minHeight: '290px' }} fluid>
                <Container className="w-fit-content position-relative m-auto h-100 pt-20">
                    <p className="fs-5x w-fit-content mb-0 text-center text-white">{t('privacy_policy')}</p>
                    <div className="underline-warning"></div>
                </Container>
            </Container>
            <Container>
                <div className="line-height-lg py-20">
                    <div className="position-relative fs-3 bg-white p-20" style={{ borderRadius: '33px' }}>
                        <div className="text-center">
                            <img src="/media/logos/default.svg" alt="Logo" />
                        </div>
                        <p className="text-success fw-bold">{t('privacy_policy')}</p>
                        <div dangerouslySetInnerHTML={{__html: t('privacy_policy_content')}}></div>
                        <span className="top-center-badge bg-success">{t('privacy_policy')}</span>
                    </div>
                </div>
            </Container>
        </>
    );
};

PrivacyPolicy.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default PrivacyPolicy;
