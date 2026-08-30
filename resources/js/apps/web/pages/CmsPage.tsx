import FrontendLayout from '@/apps/web/layouts/FrontendLayout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { Container } from 'react-bootstrap';
import './style.css';

export type CmsPageProps = {
  page: {
    id: number;
    slug: string;
    title: string;
    /** Already card/badge-wrapped HTML from the shared server render Action. */
    content: string;
  };
};

/**
 * Website chrome only: dark hero banner + already-wrapped CMS HTML from the server.
 * Badge/card markup lives in the Blade partial (shared with the API) — not here.
 */
export function CmsPageView({ page }: CmsPageProps) {
  return (
    <>
      <Container
        className="bg-primary one-side-border-bottom-lg mt-0"
        style={{ paddingTop: '60px', minHeight: '290px', paddingBottom: '60px' }}
        fluid
      >
        <Container className="w-fit-content position-relative m-auto h-100 pt-20">
          <p className="fs-5x w-fit-content mb-0 text-center text-white">{page.title}</p>
          <div className="underline-warning" />
        </Container>
      </Container>
      <Container>
        <div
          className="cms-page-server-content"
          data-testid="cms-page-content"
          dangerouslySetInnerHTML={{ __html: page.content }}
        />
      </Container>
    </>
  );
}

const CmsPage = ({ page }: CmsPageProps) => {
  return (
    <>
      <Head title={page.title} />
      <CmsPageView page={page} />
    </>
  );
};

CmsPage.layout = (page: ReactNode) => <FrontendLayout children={page} />;

export default CmsPage;
