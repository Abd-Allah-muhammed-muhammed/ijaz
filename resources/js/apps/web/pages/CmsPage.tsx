import FrontendLayout from '@/apps/web/layouts/FrontendLayout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { Container } from 'react-bootstrap';
import './style.css';

/** Brand teal — matches website `--bs-success` / existing privacy badge. */
export const CMS_PAGE_BADGE_BG = '#00686D';

export type CmsPageProps = {
  page: {
    id: number;
    slug: string;
    title: string;
    content: string;
  };
};

/**
 * Reusable CMS page view: colored title badge + self-styled content HTML.
 * Works for any slug; content is already inline-branded from the admin save pipeline.
 */
export function CmsPageView({ page }: CmsPageProps) {
  return (
    <>
      <Container className="bg-primary one-side-border-bottom-lg mt-0" style={{ paddingTop: '60px', minHeight: '290px', paddingBottom: '60px' }} fluid>
        <Container className="w-fit-content position-relative m-auto h-100 pt-20">
          <p className="fs-5x w-fit-content mb-0 text-center text-white">{page.title}</p>
          <div className="underline-warning" />
        </Container>
      </Container>
      <Container>
        <div className="line-height-lg py-20">
          <div className="position-relative fs-3 bg-white p-20" style={{ borderRadius: '33px' }}>
            <div
              className="cms-page-content"
              data-testid="cms-page-content"
              dangerouslySetInnerHTML={{ __html: page.content }}
            />
            <span
              className="top-center-badge"
              data-testid="cms-page-title-badge"
              style={{ backgroundColor: CMS_PAGE_BADGE_BG, color: '#ffffff' }}
            >
              {page.title}
            </span>
          </div>
        </div>
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
