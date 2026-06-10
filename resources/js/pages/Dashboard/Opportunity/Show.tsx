import CommentController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/CommentController';
import OfferController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OfferController';
import OpportunityController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OpportunityController';
import { KTIcon, KTCard, KTCardBody } from '@/_metronic/helpers';
import MasterLayout from '@/_metronic/layout/MasterLayout';
import { Content } from '@/_metronic/layout/components/content';
import { PageTitle } from '@/_metronic/layout/core';
import { Head, Link, router } from '@inertiajs/react';
import { ReactElement } from 'react';
import { useTranslation } from 'react-i18next';

type Author = {
  id: string | number;
  name: string;
  type: 'user' | 'provider';
};

type OfferItem = {
  id: string;
  price: string | number;
  description?: string | null;
  status: { value: string; label: string; color: string };
  author?: Author;
  created_at: string;
};

type CommentItem = {
  id: string;
  body: string;
  author?: Author;
  created_at: string;
};

type MediaItem = {
  uuid: string;
  url: string;
  mime_type: string;
};

type OpportunityResource = {
  id: string;
  title: string;
  description: string;
  budget: string | number;
  status: { value: string; label: string; color: string };
  author?: Author;
  region?: { title?: string };
  city?: { title?: string };
  offers?: OfferItem[];
  comments?: CommentItem[];
  accepted_offer?: OfferItem | null;
  media?: MediaItem[];
  offers_count?: number;
  comments_count?: number;
  expires_at?: string | null;
  created_at: string;
};

type Props = {
  opportunity: OpportunityResource;
};

const statusBadgeClass: Record<string, string> = {
  new: 'badge-light-primary',
  offer_accepted: 'badge-light-warning',
  in_progress: 'badge-light-info',
  ended: 'badge-light-success',
  cancelled: 'badge-light-danger',
};

const offerStatusBadgeClass: Record<string, string> = {
  pending: 'badge-light-primary',
  accepted: 'badge-light-success',
  rejected: 'badge-light-danger',
  cancelled: 'badge-light-danger',
};

const Show = ({ opportunity }: Props) => {
  const { t } = useTranslation();
  const badgeClass = statusBadgeClass[opportunity.status?.value] ?? 'badge-light-secondary';

  const confirmDelete = (callback: () => void) => {
    if (window.confirm(t('are_you_sure_delete'))) {
      callback();
    }
  };

  return (
    <Content>
      <Head title={`${t('opportunity')} #${opportunity.id}`} />
      <PageTitle
        breadcrumbs={[
          { title: t('opportunities'), path: OpportunityController.index().url, isSeparator: false, isActive: false },
        ]}
      >
        {t('opportunity')}
      </PageTitle>

      <div className="d-flex flex-column gap-lg-10 gap-7">
        <KTCard className="border-0 shadow-sm">
          <KTCardBody className="p-9">
            <div className="d-flex justify-content-between align-items-start flex-wrap mb-6">
              <div>
                <div className="d-flex align-items-center gap-2 mb-3 flex-wrap">
                  <h1 className="fs-2 fw-bolder text-gray-900 mb-0">{opportunity.title}</h1>
                  <span className={`badge ${badgeClass} fw-bold px-3 py-2`}>{opportunity.status?.label}</span>
                </div>
                <div className="d-flex flex-wrap gap-4 text-muted fw-semibold fs-6">
                  {opportunity.author && (
                    <span>
                      <KTIcon iconName="profile-circle" className="fs-6 me-1" />
                      {opportunity.author.name}
                      <span className="badge badge-light ms-2 fs-8">
                        {opportunity.author.type === 'user' ? t('user') : t('provider')}
                      </span>
                    </span>
                  )}
                  {opportunity.city?.title && (
                    <span>
                      <KTIcon iconName="map" className="fs-6 me-1" />
                      {opportunity.city.title}
                      {opportunity.region?.title ? ` - ${opportunity.region.title}` : ''}
                    </span>
                  )}
                  <span>
                    <KTIcon iconName="calendar-8" className="fs-6 me-1" />
                    {new Date(opportunity.created_at).toLocaleString()}
                  </span>
                </div>
              </div>
              <div className="d-flex gap-2">
                <Link href={OpportunityController.index().url} className="btn btn-sm btn-light">
                  <KTIcon iconName="arrow-left" className="fs-6 px-1" />
                  {t('back')}
                </Link>
                <button
                  type="button"
                  className="btn btn-sm btn-light-danger"
                  onClick={() =>
                    confirmDelete(() => router.delete(OpportunityController.destroy(opportunity.id).url))
                  }
                >
                  {t('delete')}
                </button>
              </div>
            </div>

            <div className="d-flex flex-wrap gap-6">
              <div className="min-w-125px rounded border border-dashed border-gray-300 px-4 py-3">
                <div className="fs-2 fw-bolder text-primary">
                  {Number(opportunity.budget).toLocaleString()} <span className="fs-6 text-gray-600">{t('SAR')}</span>
                </div>
                <div className="fw-bold fs-6 text-gray-500">{t('budget')}</div>
              </div>
              <div className="min-w-100px rounded border border-dashed border-gray-300 px-4 py-3">
                <div className="fs-2 fw-bolder text-gray-900">{opportunity.offers_count ?? 0}</div>
                <div className="fw-bold fs-6 text-gray-500">{t('offers')}</div>
              </div>
              <div className="min-w-100px rounded border border-dashed border-gray-300 px-4 py-3">
                <div className="fs-2 fw-bolder text-gray-900">{opportunity.comments_count ?? 0}</div>
                <div className="fw-bold fs-6 text-gray-500">{t('comments')}</div>
              </div>
            </div>
          </KTCardBody>
        </KTCard>

        <KTCard className="border-0 shadow-sm">
          <KTCardBody className="p-9">
            <h3 className="fw-bolder mb-5">{t('description')}</h3>
            <p className="fs-5 fw-semibold text-gray-700 whitespace-pre-wrap mb-0">
              {opportunity.description || <span className="text-muted fst-italic">{t('no_description')}</span>}
            </p>
          </KTCardBody>
        </KTCard>

        {opportunity.media && opportunity.media.length > 0 && (
          <KTCard className="border-0 shadow-sm">
            <KTCardBody className="p-9">
              <h3 className="fw-bolder mb-5">{t('media')}</h3>
              <div className="row g-4">
                {opportunity.media.map((med) => (
                  <div className="col-md-4 col-sm-6" key={med.uuid}>
                    {med.mime_type?.startsWith('image/') ? (
                      <a href={med.url} target="_blank" rel="noreferrer">
                        <div
                          className="rounded-3"
                          style={{
                            height: '150px',
                            background: `url(${med.url}) center/cover no-repeat`,
                            border: '1px solid #e4e6ef',
                          }}
                        />
                      </a>
                    ) : (
                      <a href={med.url} target="_blank" rel="noreferrer" className="btn btn-light-primary btn-sm">
                        {t('download')}
                      </a>
                    )}
                  </div>
                ))}
              </div>
            </KTCardBody>
          </KTCard>
        )}

        {opportunity.accepted_offer && (
          <KTCard className="border-0 shadow-sm">
            <KTCardBody className="p-9">
              <h3 className="fw-bolder mb-5">{t('accepted_offer')}</h3>
              <div className="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                  <div className="fs-4 fw-bolder text-gray-900 mb-1">
                    {Number(opportunity.accepted_offer.price).toLocaleString()} {t('SAR')}
                  </div>
                  {opportunity.accepted_offer.author && (
                    <div className="text-muted fw-semibold">{opportunity.accepted_offer.author.name}</div>
                  )}
                  {opportunity.accepted_offer.description && (
                    <p className="text-gray-700 mt-3 mb-0">{opportunity.accepted_offer.description}</p>
                  )}
                </div>
                <span
                  className={`badge ${offerStatusBadgeClass[opportunity.accepted_offer.status?.value] ?? 'badge-light-secondary'} fw-bold`}
                >
                  {opportunity.accepted_offer.status?.label}
                </span>
              </div>
            </KTCardBody>
          </KTCard>
        )}

        <KTCard className="border-0 shadow-sm">
          <KTCardBody className="p-9">
            <h3 className="fw-bolder mb-5">{t('offers')}</h3>
            {!opportunity.offers?.length ? (
              <p className="text-muted fst-italic mb-0">{t('no_offers')}</p>
            ) : (
              <div className="d-flex flex-column gap-4">
                {opportunity.offers.map((offer) => (
                  <div
                    key={offer.id}
                    className="d-flex justify-content-between align-items-start flex-wrap gap-3 border border-dashed border-gray-300 rounded p-4"
                  >
                    <div>
                      <div className="fs-5 fw-bolder text-gray-900 mb-1">
                        {Number(offer.price).toLocaleString()} {t('SAR')}
                      </div>
                      {offer.author && <div className="text-muted fw-semibold fs-7">{offer.author.name}</div>}
                      {offer.description && <p className="text-gray-700 mt-2 mb-0 fs-7">{offer.description}</p>}
                      <div className="text-muted fs-8 mt-2">{new Date(offer.created_at).toLocaleString()}</div>
                    </div>
                    <div className="d-flex align-items-center gap-2">
                      <span className={`badge ${offerStatusBadgeClass[offer.status?.value] ?? 'badge-light-secondary'} fw-bold`}>
                        {offer.status?.label}
                      </span>
                      <button
                        type="button"
                        className="btn btn-sm btn-light-danger"
                        onClick={() =>
                          confirmDelete(() => router.delete(OfferController.destroy(offer.id).url, { preserveScroll: true }))
                        }
                      >
                        {t('delete')}
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </KTCardBody>
        </KTCard>

        <KTCard className="border-0 shadow-sm">
          <KTCardBody className="p-9">
            <h3 className="fw-bolder mb-5">{t('comments')}</h3>
            {!opportunity.comments?.length ? (
              <p className="text-muted fst-italic mb-0">{t('no_comments')}</p>
            ) : (
              <div className="d-flex flex-column gap-4">
                {opportunity.comments.map((comment) => (
                  <div
                    key={comment.id}
                    className="d-flex justify-content-between align-items-start flex-wrap gap-3 border border-dashed border-gray-300 rounded p-4"
                  >
                    <div>
                      {comment.author && <div className="fw-bold text-gray-900 mb-1">{comment.author.name}</div>}
                      <p className="text-gray-700 mb-1">{comment.body}</p>
                      <div className="text-muted fs-8">{new Date(comment.created_at).toLocaleString()}</div>
                    </div>
                    <button
                      type="button"
                      className="btn btn-sm btn-light-danger"
                      onClick={() =>
                        confirmDelete(() =>
                          router.delete(CommentController.destroy(comment.id).url, { preserveScroll: true }),
                        )
                      }
                    >
                      {t('delete')}
                    </button>
                  </div>
                ))}
              </div>
            )}
          </KTCardBody>
        </KTCard>
      </div>
    </Content>
  );
};

Show.layout = (page: ReactElement) => <MasterLayout children={page} />;

export default Show;
