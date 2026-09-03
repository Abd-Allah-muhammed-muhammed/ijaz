import { Head, router, useForm, usePage } from '@inertiajs/react';
import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import { Order, OrderOffer } from '@/shared/types/models';
import { useTranslation } from 'react-i18next';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import { Content } from '@/vendor/metronic/layout/components/content';
import { url, zodValidate } from '@/shared/helpers/general';
import { KTIcon } from '@/vendor/metronic/helpers';
import React, { ChangeEvent, useState } from 'react';
import { OfferSchema, OfferSchemaType } from '@/apps/provider/pages/Orders/offer-schema';
import { ReviewSchema, ReviewSchemaType } from '@/apps/provider/pages/Orders/review-schema';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Provider/OrderController';
import InputError from '@/shared/components/inputs/InputError';
import ActionButton from '@/shared/components/action-button';
import {
  ConfirmDialog,
  DetailSection,
  EmptyState,
  SectionCard,
  StatTile,
  StatusBadge,
} from '@/shared/components/ui';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faStar } from '@fortawesome/free-solid-svg-icons';
import axios from '@/shared/helpers/axios';
import ProviderChatIndexController from '@/actions/Modules/Orders/Http/Controllers/Provider/ProviderChatIndexController';
import ProviderOrderChatController from '@/actions/Modules/Chat/Http/Controllers/Provider/OrderChatController';
import { formatCurrency, formatDateTime } from '@/shared/lib/formatters';
import { ORDERS_PAGE_TITLE_KEY } from '@/shared/i18n/orders-label';
import withReactContent from 'sweetalert2-react-content';
import Swal from 'sweetalert2';
import {
  canAddOffer,
  canDeleteOffer,
  canEditOffer,
  canEndOrder,
  canShowChatCta,
  getOfferStatusBadgeClass,
  getOrderStatusBadgeClass,
  offerCountLabelKey,
  shouldShowOrderEndedAlert,
  shouldShowProviderReviewForm,
  truncateText,
} from '@/apps/provider/pages/Orders/order-show-utils';

type Props = {
  order: Order;
};

const emptyOfferForm = (): OfferSchemaType => ({
  price: undefined as unknown as number,
  description: '',
});

const Show = ({ order }: Props) => {
  const { t, i18n } = useTranslation();
  const swal = withReactContent(Swal);
  const [createOfferModal, setCreateOfferModal] = useState(false);
  const [editOfferModal, setEditOfferModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedOfferId, setSelectedOfferId] = useState<string | null>(null);

  const reviews = order.reviews;
  const providerReview = reviews?.find((i) => i.reviewer_type === 'Provider');
  const userReview = reviews?.find((i) => i.reviewer_type === 'User');

  const OfferForm = useForm<OfferSchemaType>(emptyOfferForm());
  const reviewForm = useForm<ReviewSchemaType>({
    rating: providerReview?.rating ?? 0,
    comment: providerReview?.comment ?? '',
  });

  const auth = usePage().props.auth.user;
  const currencyLabel = t('SAR');
  const statusBadgeClass = getOrderStatusBadgeClass(order.status?.value);
  const mediaItems = order.media ?? [];
  const offers = order.offers ?? [];

  const locationBits = [order.city?.title, order.region?.title].filter(Boolean);
  const subtitle = [
    ...locationBits,
    order.created_at ? formatDateTime(order.created_at, i18n.language) : null,
  ]
    .filter(Boolean)
    .join(' · ');

  const budgetDisplay = [
    formatCurrency(order.budget_start, {
      locale: i18n.language,
      currencyLabel: '',
      maximumFractionDigits: 2,
      minimumFractionDigits: 0,
    }),
    formatCurrency(order.budget_end, {
      locale: i18n.language,
      currencyLabel,
      maximumFractionDigits: 2,
      minimumFractionDigits: 0,
    }),
  ]
    .filter(Boolean)
    .join(' – ');

  const setReviewRating = (rating: number) => {
    reviewForm.setData('rating', rating);
  };

  const setReviewComment = (e: ChangeEvent<HTMLTextAreaElement>) => {
    reviewForm.setData('comment', e.target.value);
  };

  const closeCreateOfferModal = () => {
    setCreateOfferModal(false);
  };

  const closeEditOfferModal = () => {
    setEditOfferModal(false);
  };

  const submitOfferForm = () => {
    if (!zodValidate<OfferSchemaType>(OfferSchema, OfferForm)) {
      return;
    }
    OfferForm.submit(OrderController.submitOffer(order.id as string), {
      onSuccess: () => {
        closeCreateOfferModal();
        OfferForm.setData(emptyOfferForm());
        OfferForm.clearErrors();
      },
      preserveScroll: true,
      preserveState: true,
    });
  };

  const submitEditOfferForm = () => {
    if (!zodValidate<OfferSchemaType>(OfferSchema, OfferForm)) {
      return;
    }
    OfferForm.submit(OrderController.updateOffer([order.id as string, selectedOfferId as string]), {
      onSuccess: () => {
        closeEditOfferModal();
        OfferForm.setData(emptyOfferForm());
        OfferForm.clearErrors();
      },
      preserveScroll: true,
      preserveState: true,
    });
  };

  const submitReview = () => {
    if (!zodValidate<ReviewSchemaType>(ReviewSchema, reviewForm)) {
      return;
    }
    reviewForm.submit(OrderController.updateReview(order.id as string), {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const handleCreateClick = () => {
    OfferForm.setData(emptyOfferForm());
    OfferForm.clearErrors();
    setCreateOfferModal(true);
  };

  const handleDeleteClick = (offerId: string) => {
    setSelectedOfferId(offerId);
    setShowDeleteModal(true);
  };

  const handleEditClick = (offer: OrderOffer) => {
    setSelectedOfferId(offer.id as string);
    OfferForm.setData({
      price: Number(offer.price),
      description: offer.description ?? '',
    });
    OfferForm.clearErrors();
    setEditOfferModal(true);
  };

  const confirmDeleteOffer = () => {
    setShowDeleteModal(false);
    router.delete(
      OrderController.deleteOffer({
        order: order.id as string,
        offer: selectedOfferId as string,
      }).url,
      {
        preserveScroll: true,
        preserveState: true,
      },
    );
  };

  const confirmEndOrder = () => {
    swal
      .fire({
        title: t('are_you_sure'),
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: t('cancel'),
        confirmButtonText: t('yes'),
      })
      .then((result) => {
        if (result.isConfirmed) {
          router.post(
            OrderController.end({
              order: order.id as string,
            }).url,
          );
        }
      });
  };

  const StartChat = () => {
    axios
      .post(ProviderOrderChatController.store().url, {
        order_id: order.id,
      })
      .then((res) => {
        const response = res.data;
        if (response.success) {
          router.visit(
            ProviderChatIndexController.url({
              query: {
                conversation: response.data.id,
              },
            }),
          );
        }
      });
  };

  const showChat = canShowChatCta(
    order.status?.value,
    order.provider?.socket_id,
    auth?.socket_id,
  );
  const showEndOrder = canEndOrder(order.status?.value);
  const showAddOffer = canAddOffer(order.status?.value, offers);

  return (
    <>
      <Head title={t(ORDERS_PAGE_TITLE_KEY)} />
      <Content>
        <Row className="g-5 g-lg-7">
          <Col sm={12} lg={8}>
            <SectionCard
              variant="hero"
              className="mb-5"
              footer={(
                <DetailSection label={t('description')} value={order.description} />
              )}
            >
              <div className="d-flex justify-content-between align-items-start flex-wrap gap-5 mb-6">
                <div className="d-flex align-items-start gap-4 min-w-0">
                  <div className="symbol symbol-55px symbol-circle flex-shrink-0">
                    {order.user?.image ? (
                      <img
                        src={order.user.image}
                        alt=""
                        className="symbol-label object-fit-cover"
                      />
                    ) : (
                      <span className="symbol-label bg-white text-primary shadow-sm fw-bolder fs-3">
                        {order.user?.name?.[0]?.toUpperCase() ?? '?'}
                      </span>
                    )}
                  </div>
                  <div className="min-w-0">
                    <div className="d-flex align-items-center flex-wrap gap-2 mb-2">
                      <h2 className="fw-bolder text-gray-900 mb-0 fs-2 text-truncate">
                        {order.user?.name ?? '—'}
                      </h2>
                      <StatusBadge
                        label={order.status?.label}
                        colorClass={statusBadgeClass}
                      />
                    </div>
                    {subtitle && (
                      <div className="text-muted fw-semibold fs-6">{subtitle}</div>
                    )}
                    {order.skills && order.skills.length > 0 && (
                      <div className="d-flex flex-wrap gap-2 mt-3">
                        {order.skills.map((skill) => (
                          <span
                            key={skill.id}
                            className="badge badge-light-primary rounded-pill fw-semibold px-3 py-2"
                          >
                            {skill.title}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                </div>

                <div className="d-flex gap-2 flex-wrap align-items-center">
                  {showChat && (
                    <button
                      type="button"
                      className="btn btn-sm btn-light-primary rounded-pill d-inline-flex align-items-center gap-2"
                      onClick={StartChat}
                    >
                      <KTIcon iconName="message-text-2" className="fs-5" />
                      {t('Start Conversation')}
                    </button>
                  )}
                  {showEndOrder && (
                    <button
                      type="button"
                      className="btn btn-sm btn-light-danger rounded-pill d-inline-flex align-items-center gap-2"
                      onClick={confirmEndOrder}
                    >
                      <KTIcon iconName="check-circle" className="fs-5" />
                      {t('end_order')}
                    </button>
                  )}
                </div>
              </div>

              {shouldShowOrderEndedAlert(order.status?.value) && (
                <div className="alert alert-info d-flex align-items-center p-4 mb-6" role="alert">
                  <i className="ki-duotone ki-information fs-2hx text-info me-4">
                    <span className="path1" />
                    <span className="path2" />
                    <span className="path3" />
                  </i>
                  <span className="fs-6 text-gray-700">
                    {t('sorry this order has been ended')}
                  </span>
                </div>
              )}

              <div className="row g-4">
                <div className="col-6 col-md-3">
                  <StatTile label={t('budget')} value={budgetDisplay || '—'} />
                </div>
                <div className="col-6 col-md-3">
                  <StatTile
                    label={t('expected_time')}
                    value={order.expected_time || '—'}
                  />
                </div>
                <div className="col-6 col-md-3">
                  <StatTile label={t('attachments')} value={mediaItems.length} />
                </div>
                <div className="col-6 col-md-3">
                  <StatTile
                    label={t('offer count')}
                    value={order.offers_count ?? offers.length}
                  />
                </div>
              </div>
            </SectionCard>

            <SectionCard
              className="mb-5"
              header={(
                <>
                  <div className="d-flex align-items-center gap-2 m-0 min-w-0">
                    <h3 className="fw-bolder text-gray-900 mb-0">{t('my offers')}</h3>
                    <span className="badge badge-light-primary text-primary fs-7 rounded-pill fw-semibold px-3 py-2">
                      {t(offerCountLabelKey(offers.length), { count: offers.length })}
                    </span>
                  </div>
                  {showAddOffer ? (
                    <button
                      type="button"
                      className="btn btn-sm btn-primary rounded-pill d-inline-flex align-items-center gap-2 flex-shrink-0"
                      onClick={handleCreateClick}
                    >
                      <KTIcon iconName="plus" className="fs-4" />
                      {t('add_offer')}
                    </button>
                  ) : null}
                </>
              )}
            >
              {offers.length > 0 ? (
                <div className="table-responsive">
                  <table className="table align-middle table-row-dashed gy-4 mb-0">
                    <thead>
                      <tr className="text-muted fw-bold fs-7 text-uppercase">
                        <th className="min-w-40px">#</th>
                        <th>{t('status')}</th>
                        <th>{t('price')}</th>
                        <th>{t('description')}</th>
                        <th>{t('created_at')}</th>
                        <th className="text-end">{t('actions')}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {offers.map((offer, index) => {
                        const offerStatus = offer.status?.value;
                        const offerBadge = getOfferStatusBadgeClass(offerStatus);
                        const description = offer.description ?? '';
                        const rowNumber = index + 1;

                        return (
                          <tr key={offer.id}>
                            <td>
                              <span
                                className="fw-bold text-gray-800"
                                title={String(offer.id)}
                              >
                                {rowNumber}
                              </span>
                            </td>
                            <td>
                              <StatusBadge
                                label={offer.status?.label}
                                colorClass={offerBadge}
                              />
                            </td>
                            <td className="fw-semibold text-gray-900">
                              {formatCurrency(offer.price, {
                                locale: i18n.language,
                                currencyLabel,
                                maximumFractionDigits: 2,
                                minimumFractionDigits: 0,
                              })}
                            </td>
                            <td>
                              <span
                                className="text-gray-700 text-truncate d-inline-block"
                                style={{ maxWidth: 220 }}
                                title={description}
                              >
                                {truncateText(description, 80)}
                              </span>
                            </td>
                            <td className="text-muted fs-7">
                              {offer.created_at
                                ? formatDateTime(offer.created_at, i18n.language)
                                : '—'}
                            </td>
                            <td className="text-end">
                              <div className="d-inline-flex align-items-center gap-1">
                                {canEditOffer(offerStatus, order.status?.value) && (
                                  <button
                                    type="button"
                                    className="btn btn-icon btn-sm btn-light-warning rounded-circle"
                                    aria-label={t('edit')}
                                    title={t('edit')}
                                    onClick={() => handleEditClick(offer)}
                                  >
                                    <KTIcon iconName="pencil" className="fs-5" />
                                  </button>
                                )}
                                {canDeleteOffer(offerStatus, order.status?.value) && (
                                  <button
                                    type="button"
                                    className="btn btn-icon btn-sm btn-light-danger rounded-circle"
                                    aria-label={t('delete')}
                                    title={t('delete')}
                                    onClick={() => handleDeleteClick(offer.id as string)}
                                  >
                                    <KTIcon iconName="trash" className="fs-5" />
                                  </button>
                                )}
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              ) : (
                <EmptyState
                  icon={<KTIcon iconName="price-tag" className="fs-3x text-gray-300 mb-4" />}
                  title={t('no_offers_submitted_yet')}
                  description={t('no_offers_submitted_yet_hint')}
                  action={
                    showAddOffer ? (
                      <button
                        type="button"
                        className="btn btn-primary rounded-pill px-6"
                        onClick={handleCreateClick}
                      >
                        <KTIcon iconName="plus" className="fs-4" />
                        {t('add_offer')}
                      </button>
                    ) : undefined
                  }
                />
              )}
            </SectionCard>

            {shouldShowProviderReviewForm(order.status?.value) && (
              <SectionCard className="mb-5" title={t('review')}>
                <div className="d-flex gap-1 mb-4">
                  {[1, 2, 3, 4, 5].map((i) => (
                    <button
                      key={i}
                      type="button"
                      className={`btn btn-icon btn-sm border-0 ${
                        i <= reviewForm.data.rating ? 'text-warning' : 'text-gray-300'
                      } fs-2`}
                      aria-label={`${t('rating')} ${i}`}
                      onClick={() => setReviewRating(i)}
                    >
                      <FontAwesomeIcon icon={faStar} />
                    </button>
                  ))}
                </div>
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold text-gray-700">
                    {t('comment')}
                  </Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={4}
                    className="rounded-3"
                    value={reviewForm.data.comment}
                    onChange={setReviewComment}
                  />
                  <InputError message={reviewForm.errors.rating} />
                  <InputError message={reviewForm.errors.comment} />
                </Form.Group>
                <div className="text-end">
                  <Button
                    variant="primary"
                    className="rounded-pill px-6"
                    onClick={submitReview}
                    disabled={
                      !(
                        providerReview?.rating !== reviewForm.data.rating
                        || providerReview?.comment !== reviewForm.data.comment
                      )
                    }
                  >
                    {reviewForm.processing ? (
                      <span className="indicator-progress" style={{ display: 'block' }}>
                        {t('Please wait...')}
                        <span className="spinner-border spinner-border-sm align-middle ms-2" />
                      </span>
                    ) : (
                      <span className="indicator-label">{t('save_review')}</span>
                    )}
                  </Button>
                </div>
              </SectionCard>
            )}

            {userReview && (
              <SectionCard className="mb-5" title={t('user review')}>
                <div className="d-flex gap-1 mb-3">
                  {[1, 2, 3, 4, 5].map((i) => (
                    <div
                      key={i}
                      className={`${i <= userReview.rating ? 'text-warning' : 'text-gray-300'} fs-2`}
                    >
                      <FontAwesomeIcon icon={faStar} />
                    </div>
                  ))}
                </div>
                <p className="fs-6 text-gray-800 mb-0 lh-lg">
                  {userReview.comment || '—'}
                </p>
              </SectionCard>
            )}

            <ConfirmDialog
              show={showDeleteModal}
              title={t('Confirm Delete')}
              message={t(
                'Are you sure you want to delete this offer? This action cannot be undone.',
              )}
              cancelLabel={t('cancel')}
              confirmLabel={t('delete')}
              confirmVariant="danger"
              onCancel={() => setShowDeleteModal(false)}
              onConfirm={confirmDeleteOffer}
            />

            <Modal
              show={createOfferModal}
              onHide={closeCreateOfferModal}
              size="lg"
              centered
            >
              <Modal.Header closeButton>
                <Modal.Title>{t('create new offer')}</Modal.Title>
              </Modal.Header>
              <Modal.Body>
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold text-gray-700">
                    {t('offered amount')}
                  </Form.Label>
                  <div className="input-group">
                    <Form.Control
                      onChange={(e) => {
                        const raw = e.currentTarget.value;
                        OfferForm.setData(
                          'price',
                          raw === '' ? (undefined as unknown as number) : parseFloat(raw),
                        );
                      }}
                      type="number"
                      step={0.01}
                      placeholder={t('offered amount')}
                      value={OfferForm.data.price ?? ''}
                    />
                    <span className="input-group-text">{currencyLabel}</span>
                  </div>
                  <InputError message={OfferForm.errors.price} />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold text-gray-700">
                    {t('description')}
                  </Form.Label>
                  <Form.Control
                    onChange={(e) => {
                      OfferForm.setData('description', e.currentTarget.value);
                    }}
                    as="textarea"
                    placeholder={t('description')}
                    rows={5}
                    value={OfferForm.data.description ?? ''}
                  />
                  <InputError message={OfferForm.errors.description} />
                </Form.Group>
              </Modal.Body>
              <Modal.Footer>
                <Button variant="secondary" onClick={closeCreateOfferModal}>
                  {t('cancel')}
                </Button>
                <ActionButton
                  type="button"
                  isProcessing={OfferForm.processing}
                  text={t('save')}
                  onClick={submitOfferForm}
                />
              </Modal.Footer>
            </Modal>

            <Modal show={editOfferModal} onHide={closeEditOfferModal} size="lg" centered>
              <Modal.Header closeButton>
                <Modal.Title>{t('edit offer')}</Modal.Title>
              </Modal.Header>
              <Modal.Body>
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold text-gray-700">
                    {t('offered amount')}
                  </Form.Label>
                  <div className="input-group">
                    <Form.Control
                      onChange={(e) => {
                        const raw = e.currentTarget.value;
                        OfferForm.setData(
                          'price',
                          raw === '' ? (undefined as unknown as number) : parseFloat(raw),
                        );
                      }}
                      type="number"
                      step={0.01}
                      placeholder={t('offered amount')}
                      value={OfferForm.data.price ?? ''}
                    />
                    <span className="input-group-text">{currencyLabel}</span>
                  </div>
                  <InputError message={OfferForm.errors.price} />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold text-gray-700">
                    {t('description')}
                  </Form.Label>
                  <Form.Control
                    onChange={(e) => {
                      OfferForm.setData('description', e.currentTarget.value);
                    }}
                    as="textarea"
                    placeholder={t('description')}
                    rows={5}
                    value={OfferForm.data.description ?? ''}
                  />
                  <InputError message={OfferForm.errors.description} />
                </Form.Group>
              </Modal.Body>
              <Modal.Footer>
                <Button variant="secondary" onClick={closeEditOfferModal}>
                  {t('cancel')}
                </Button>
                <ActionButton
                  type="button"
                  isProcessing={OfferForm.processing}
                  text={t('save')}
                  onClick={submitEditOfferForm}
                />
              </Modal.Footer>
            </Modal>
          </Col>

          <Col sm={12} lg={4}>
            <SectionCard
              title={t('attachments')}
              headerClassName="card-header border-0 pt-6 px-6 bg-transparent"
              bodyClassName="card-body pt-0 px-6 pb-6"
            >
              {mediaItems.length === 0 ? (
                <EmptyState
                  compact
                  icon={<KTIcon iconName="file" className="fs-3x text-gray-300 mb-4" />}
                  title={t('no_attachments')}
                  description={t('attachments_empty_hint')}
                />
              ) : (
                <div className="d-flex flex-column gap-4">
                  {mediaItems.map((media) => (
                    <div
                      key={media.id}
                      className="d-flex align-items-center gap-3 p-3 rounded-3 border border-gray-100 bg-light"
                    >
                      {media.type === 'image' ? (
                        <div className="symbol symbol-40px flex-shrink-0">
                          <img alt="" src={media.url} className="object-fit-cover rounded" />
                        </div>
                      ) : (
                        <div className="symbol symbol-40px flex-shrink-0">
                          <img
                            alt=""
                            src={url(
                              media.type === 'pdf'
                                ? '/media/svg/files/pdf.svg'
                                : '/media/svg/files/doc.svg',
                            )}
                          />
                        </div>
                      )}
                      <div className="fw-semibold min-w-0 flex-grow-1">
                        <a
                          className="fs-6 fw-bold text-gray-900 text-hover-primary text-truncate d-block"
                          href={media.url}
                          target="_blank"
                          rel="noreferrer"
                        >
                          {media.file_name}
                        </a>
                        <div className="text-muted fs-8">{media.size}</div>
                      </div>
                      <a
                        href={media.url}
                        target="_blank"
                        rel="noreferrer"
                        className="btn btn-icon btn-sm btn-light-primary rounded-circle"
                        aria-label={t('download')}
                        title={t('download')}
                      >
                        <KTIcon iconName="arrow-down" className="fs-4" />
                      </a>
                    </div>
                  ))}
                </div>
              )}
            </SectionCard>
          </Col>
        </Row>
      </Content>
    </>
  );
};

Show.layout = (page: React.ReactElement) => <ProviderLayout children={page} />;
export default Show;
