import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Container } from 'react-bootstrap';

type ErrorPageProps = {
    status: number;
    title?: string;
    message?: string;
};

const KNOWN_STATUSES = [401, 403, 404, 419, 429, 500, 503] as const;

export default function ErrorPage({ status, title, message }: ErrorPageProps) {
    const { t } = useTranslation();

    const isKnown = (KNOWN_STATUSES as readonly number[]).includes(status);
    const titleKey = isKnown ? `error_${status}_title` : 'error_generic_title';
    const messageKey = isKnown ? `error_${status}_message` : 'error_generic_message';

    const resolvedTitle = title ?? t(titleKey);
    const resolvedMessage = message ?? t(messageKey);

    return (
        <>
            <Head title={`${status} — ${resolvedTitle}`} />
            <Container
                className="bg-primary one-side-border-bottom-lg mt-0 d-flex align-items-center justify-content-center"
                style={{ paddingTop: '60px', minHeight: '290px' }}
                fluid
            >
                <Container className="w-fit-content position-relative m-auto h-100 pt-20">
                    <p className="fs-5x w-fit-content mb-0 text-center text-white">{status}</p>
                    <div className="underline-warning"></div>
                </Container>
            </Container>
            <Container>
                <div className="line-height-lg py-20">
                    <div
                        className="text-center position-relative fs-3 bg-white p-20 mx-auto"
                        style={{ borderRadius: '33px', maxWidth: '640px' }}
                    >
                        <div className="mb-8">
                            <img src="/media/logos/default.svg" alt="Logo" />
                        </div>
                        <p className="text-success fw-bold fs-2x mb-4">{resolvedTitle}</p>
                        <p className="text-muted fs-4 mb-10">{resolvedMessage}</p>
                        <Link href="/" className="btn btn-primary btn-lg">
                            {t('back_to_home')}
                        </Link>
                        <span className="top-center-badge bg-success">{status}</span>
                    </div>
                </div>
            </Container>
        </>
    );
}
