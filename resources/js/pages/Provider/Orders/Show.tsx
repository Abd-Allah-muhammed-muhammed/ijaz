import {Head, router, useForm, usePage} from '@inertiajs/react'
import ProviderLayout from "@/layouts/provider/ProviderLayout";
import {Order, OrderOffer, OrderOfferStatus, OrderStatus} from '@/types/models';
import { useTranslation } from 'react-i18next';

import {Button, Card, Col, Form, Modal, OverlayTrigger, Row, Tooltip} from "react-bootstrap";
import {Content} from "@/_metronic/layout/components/content";
import {url, zodValidate} from "@/helpers/general";
import {KTIcon} from "@/_metronic/helpers";
import React, {ChangeEvent, useState} from 'react';
import {OfferSchema, OfferSchemaType} from "@/pages/Provider/Orders/offer-schema";
import {ReviewSchema, ReviewSchemaType} from "@/pages/Provider/Orders/review-schema";
import OrderController from "@/actions/App/Http/Controllers/Provider/OrderController";
import InputError from "@/components/inputs/InputError";
import {OfferStatusEnum, OrderStatusEnum} from "@/Enums/Order";
import ActionButton from "@/components/action-button";
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faStar} from '@fortawesome/free-solid-svg-icons';
import axios from '@/helpers/axios';
import ChatController from '@/actions/App/Http/Controllers/Provider/ChatController';
import OrderChatController from '@/actions/App/Http/Controllers/Provider/OrderChatController';

type Props = {
  order: Order
}

const Show = ({order}: Props) => {
  const { t } = useTranslation();
  const [createOfferModal, setCreateOfferModal] = useState<boolean>(false)
  const [editOfferModal, setEditOfferModal] = useState<boolean>(false)
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  // allow null initially
  const [selectedOfferId, setSelectedOfferId] = useState<string | null>(null);
  const reviews = order.reviews;
  const providerReview = reviews?.find(i => i.reviewer_type === "Provider");
  const userReview = reviews?.find(i => i.reviewer_type === "User");
  const [reviewRating, _setReviewRating] = useState(providerReview?.rating ?? 0);
  const [reviewComment, _setReviewComment] = useState(providerReview?.comment ?? "");
  const OfferForm = useForm<OfferSchemaType>()
  const reviewForm = useForm<ReviewSchemaType>(providerReview);
  const auth = usePage().props.auth.user
  const setReviewRating = (rating: number) => {
    _setReviewRating(rating);
    reviewForm.setData('rating', rating);
  }

  const setReviewComment = (e: ChangeEvent<HTMLTextAreaElement>) => {
    const comment = e.target.value;
    _setReviewComment(comment);
    reviewForm.setData('comment', comment);
  }

  const closeCreateOfferModal = () => {
    setCreateOfferModal(false);
  }

  const closeEditOfferModal = () => {
    setEditOfferModal(false);
  }

  const submitOfferForm = () => {
    if (!zodValidate<OfferSchemaType>(OfferSchema, OfferForm)) {
      return;
    }
    OfferForm.submit(OrderController.submitOffer(order.id as string), {
      onSuccess: () => {
        closeCreateOfferModal();
        OfferForm.reset();
      },
      preserveScroll: true,
      preserveState: true,
    });
  }

  const submitEditOfferForm = () => {
    if (!zodValidate<OfferSchemaType>(OfferSchema, OfferForm)) {
      return;
    }
    OfferForm.submit(OrderController.updateOffer([order.id as string, selectedOfferId as string]), {
      onSuccess: () => {
        closeEditOfferModal();
        OfferForm.reset();
      },
      preserveScroll: true,
      preserveState: true,
    });
  }

  const submitReview = () => {
    if (!zodValidate<ReviewSchemaType>(ReviewSchema, reviewForm)) {
      return;
    }
    reviewForm.submit(OrderController.updateReview(order.id as string), {
      preserveScroll: true,
      preserveState: true,
    });
  }

  const handleCreateClick = () => {
    OfferForm.reset();
    setCreateOfferModal(true);
  };

  const handleDeleteClick = (offerId: string) => {
    setSelectedOfferId(offerId);
    setShowDeleteModal(true);
  };

  const handleEditClick = (offer: OrderOffer) => {
    setSelectedOfferId(offer.id as string);
    OfferForm.setData('price', Number(offer.price));
    OfferForm.setData('description', offer.description);
    setEditOfferModal(true);
  };

  const confirmDeleteOffer = () => {
    setShowDeleteModal(false);
    router.delete(OrderController.deleteOffer({order: order.id as string, offer: selectedOfferId as string}).url, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const EndOrder = () => {
    router.post(OrderController.end({
      order: order.id as string
    }).url)
  };

  const StartChat = () => {
    axios.post(OrderChatController.store().url, {
      order_id: order.id,
    }).then(function (res) {
      const response = res.data;
      if (response.success) {
        router.visit(ChatController.index({
          query: {
            conversation: response.data.id
          },
        }).url);
      }
    });
  };

  return (
    <>
      <Head title={t('orders')}/>
      <Content>
        <Row>
          <Col sm={12} md={8} className="mx-auto">
            <Card className="mb-5 shadow-lg border-0 rounded-4 bg-white overflow-hidden">
              <Card.Header
                className="bg-gradient-to-r from-blue-100 to-blue-50 rounded-top-4 py-5 px-5 d-flex flex-wrap align-items-start gap-5">
                {/* Avatar block: full width at top */}
                <div
                  className="symbol symbol-circle symbol-80px overflow-hidden border-3 border-primary shadow-sm w-auto mb-3">
                  <a href="#" aria-label={order.user?.name} className="d-inline-block">
                    {order.user?.image ? (
                      <div className="symbol-label">
                        <img src={order.user?.image} alt={order.user?.name}
                             className="w-100 h-100 object-fit-cover rounded-circle"/>
                      </div>
                    ) : (
                      <div
                        className="symbol-label fs-2 bg-light-info text-info d-flex align-items-center justify-content-center rounded-circle"
                        style={{height: '80px'}}>
                        <i className="ki-duotone ki-user fs-1 me-2"></i>
                        {order.user?.name?.[0]?.toUpperCase() ?? ''}
                      </div>
                    )}
                  </a>
                </div>
                {/* Main user info */}
                <div className="d-flex flex-column flex-grow-1 min-w-0">
                  <span className="fw-bold fs-2 text-dark mb-2 text-ellipsis"
                        title={order.user?.name}>{order.user?.name}</span>
                  {/* City, Region, Skill elegant row */}
                  <div className="d-flex flex-wrap align-items-center gap-4 mt-1 mb-2 text-gray-600 fs-5">
                    {order.city && (
                      <span className="d-flex align-items-center gap-2">
                        <KTIcon iconName="map" className="fs-4 text-primary"/>
                        <span className="text-truncate" title={order.city.title}>
                          {order.city.title}
                        </span>
                      </span>
                    )}
                    {order.region && (
                      <span className="d-flex align-items-center gap-2">
                        <KTIcon iconName="geolocation" className="fs-4 text-primary"/>
                        <span className="text-truncate" title={order.region.title}>
                          {order.region.title}
                        </span>
                      </span>
                    )}
                    {order.skills && order.skills.length > 0 && (
                      <span className="d-flex align-items-center gap-2">
                        <KTIcon iconName="briefcase" className="fs-4 text-primary"/>
                        <span className="d-flex align-items-center gap-2 flex-wrap">
                          {order.skills.map((skill) => (
                            <span key={skill.id} className="badge bg-light text-gray-700 border">
                              {skill.title}
                            </span>
                          ))}
                        </span>
                      </span>
                    )}
                  </div>
                  <div className="text-gray-500 fs-5 d-flex gap-4 align-items-center flex-wrap">
                    <span className="d-flex align-items-center gap-2">
                      <KTIcon iconName={'calendar-2'} className="fs-4 text-primary"/>
                      <span>{new Date(order.created_at).toLocaleDateString()} {new Date(order.created_at).toLocaleTimeString()}</span>
                    </span>
                    <span
                      className={`badge bg-${order.status.color} text-white px-4 py-2 fs-5 shadow-sm`}>{order.status.label}</span>
                  </div>

                  {/* Budget & Expected Time Section with Icons */}
                  <div className="d-flex flex-wrap gap-5 align-items-center mt-4">
                    <OverlayTrigger
                      placement="top"
                      overlay={<Tooltip id={`order-${order.id}-budget_cont`}>{t('budget')}</Tooltip>}
                    >
                      <div className="d-flex align-items-center gap-2 bg-light rounded-3  shadow-sm">
                        <img src={url('/media/icons/wallet.svg')} alt="Budget" width={32} height={32} className=""/>
                        <span
                          className="fw-semibold text-gray-800 fs-5 ">{order.budget_start} - {order.budget_end}</span>
                      </div>
                    </OverlayTrigger>
                    <OverlayTrigger
                      placement="top"
                      overlay={<Tooltip id={`order-${order.id}-expected_time`}>{t('expected_time')}</Tooltip>}
                    >
                      <div className="d-flex align-items-center gap-2 bg-light rounded-3 shadow-sm">
                        <img src={url('/media/icons/clock.svg')} alt="Expected Time" width={32} height={32}
                             className=""/>
                        <span className="fw-semibold text-gray-800 fs-5 ">{order.expected_time}</span>
                      </div>
                    </OverlayTrigger>

                  </div>
                  {/* Buttons Group */}
                  {
                    !([OrderStatusEnum.New, OrderStatusEnum.OfferProvided] as unknown as OrderOfferStatus).includes(order.status.value) && (
                      <div className="alert alert-info d-flex align-items-center p-3 mt-4" role="alert">
                        <i className="ki-duotone ki-information fs-2hx text-info me-4">
                          <span className="path1"></span>
                          <span className="path2"></span>
                          <span className="path3"></span>
                        </i>
                        <div className="d-flex flex-column">
                          <span className="fs-5 text-gray-700">
                           {t('sorry this order has been ended')}
                          </span>
                        </div>
                      </div>
                    )}
                  <div className="d-flex gap-2 mt-3">
                    {(order.status.value == OrderStatusEnum.OfferProvided && order.provider?.socket_id === auth.socket_id) && (
                      <Button
                        variant="outline-secondary"
                        className="px-4 py-2 rounded-3 fw-semibold d-flex align-items-center gap-2"
                        style={{fontSize: '1.1rem'}}
                        onClick={StartChat}
                      >
                        <KTIcon iconName="message-text-2" className="fs-4"/>
                        {t('Start Conversation')}
                      </Button>
                    )}
                    {/* Add more buttons here if needed, e.g. view profile, send email, etc. */}
                  </div>
                </div>
                <div className="d-flex flex-column align-items-end gap-3">
                  <OverlayTrigger
                    placement="top"
                    overlay={<Tooltip id={`order-${order.id}-media_cont`}>{t('files count')}</Tooltip>}
                  >
                    <div className="d-flex align-items-center bg-light rounded-3  py-2 px-4 shadow-sm gap-2">
                      <i className="ki-duotone ki-paper-clip fs-3">
                        <span className="path1"></span>
                        <span className="path2"></span>
                        <span className="path3"></span>
                      </i>
                      <span className="fs-5 fw-bold text-gray-700">{order.media?.length}</span>
                    </div>
                  </OverlayTrigger>
                  <OverlayTrigger
                    placement="top"
                    overlay={<Tooltip id={`order-${order.id}-offers_cont`}>{t('offer count')}</Tooltip>}
                  >
                    <div className="d-flex align-items-center py-2 px-4 bg-light rounded-3 shadow-sm gap-2">
                      <i className="ki-duotone ki-message-text-2 fs-3">
                        <span className="path1"></span>
                        <span className="path2"></span>
                        <span className="path3"></span>
                      </i>
                      <span className="fs-5 fw-bold text-gray-700">{order.offers_count}</span>
                    </div>
                  </OverlayTrigger>

                </div>
              </Card.Header>
              <Card.Body className="px-5 py-5 fs-5 text-gray-800">
                <div className="mb-2 text-gray-700 fs-5"
                     style={{maxHeight: '180px', overflowY: 'auto'}}>{order.description}</div>
              </Card.Body>
            </Card>
            <div className="d-flex justify-content-end gap-2 mb-5">
              {order.status.value == OrderStatusEnum.InProgress &&
                <Button variant={'danger'} onClick={EndOrder}>End Order</Button>}
            </div>
            <Card className="mb-5 shadow-lg">
              <Card.Header>
                <Card.Title className='flex-column'>
                  <h3 className="fw-bold ">{t('my offers')}</h3>
                  <span className="text-muted fw-semibold fs-7">
                    {order.offers?.length}
                  </span>
                </Card.Title>
                {order.status.value === OrderStatusEnum.New && !order.offers?.find(o => o.status.value === OfferStatusEnum.Pending) && (
                  <div className="card-toolbar">
                    <Button
                      onClick={() => handleCreateClick()}
                      variant={'primary'}
                      className="btn btn-primary"
                    >
                      <KTIcon iconName={'plus'} className='fs-1'/>
                    </Button>
                  </div>
                )}

              </Card.Header>
              <Card.Body className="p-0">
                {order.offers && order.offers.length > 0 ? (
                  <div className="table-responsive w-100">
                    <table className="table table-bordered table-striped table-hover w-100" aria-label="Offers table">
                      <thead className="bg-gray-50 sticky-top">
                      <tr>
                        <th className="px-4 py-3 font-medium uppercase fs-5">#</th>
                        <th className="px-4 py-3 font-medium uppercase fs-5">{t('status')}</th>
                        <th className="px-4 py-3 font-medium uppercase fs-5">{t('price')}</th>
                        <th className="px-4 py-3 font-medium uppercase fs-5">{t('description')}</th>
                        <th className="px-4 py-3 font-medium uppercase fs-5">{t('created_at')}</th>
                        <th className="px-4 py-3 fs-5">{t('actions')}</th>
                      </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                      {order.offers.map((offer) => (
                        <tr key={offer.id} className="fs-5">
                          <td className="px-4 py-3">{offer.id}</td>
                          <td className="px-4 py-3">
                            <span
                              className={`text-white badge bg-${offer.status.color} fs-5`}>{offer.status.label}</span>
                          </td>
                          <td className="px-4 py-3">{offer.price}</td>
                          <td className="px-4 py-3">{offer.description}</td>
                          <td
                            className="px-4 py-3">{new Date(offer.created_at).toLocaleDateString()} {new Date(offer.created_at).toLocaleDateString()}</td>
                          <td className="px-4 py-3">
                            {([OfferStatusEnum.Pending, OfferStatusEnum.Accepted] as unknown as OrderOfferStatus).includes(offer.status.value) && ([OrderStatusEnum.New, OrderStatusEnum.OfferProvided] as unknown as OrderStatus).includes(order.status.value) && (
                              <>
                                <Button variant="warning" size="sm" aria-label={t('edit')} className="fs-5 me-2"
                                        onClick={() => handleEditClick(offer)}>
                                  <KTIcon iconName='pencil' className="me-1 fs-5"/>
                                  {t('edit')}
                                </Button>
                                <Button variant="danger" size="sm" aria-label={t('delete')} className="fs-5"
                                        onClick={() => handleDeleteClick(offer.id as string)}>
                                  <KTIcon iconName='trash' className="me-1 fs-5"/>
                                  {t('delete')}
                                </Button>
                              </>
                            )}
                          </td>
                        </tr>
                      ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="d-flex flex-column align-items-center justify-content-center py-5">
                    <span className="fs-1 text-muted mb-2"><i className="ki-duotone ki-message-text-2"></i></span>
                    <span className="text-muted fs-5">{t('No offers yet. Hover to view offers table.')}</span>
                  </div>
                )}
                <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)} centered>
                  <Modal.Header closeButton>
                    <Modal.Title>{t('Confirm Delete')}</Modal.Title>
                  </Modal.Header>
                  <Modal.Body>
                    {t('Are you sure you want to delete this offer? This action cannot be undone.')}
                  </Modal.Body>
                  <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>{t('Cancel')}</Button>
                    <Button variant="danger" onClick={confirmDeleteOffer}>{t('Delete')}</Button>
                  </Modal.Footer>
                </Modal>
              </Card.Body>
            </Card>
            {
              order.status.value === OrderStatusEnum.EndedByClient &&
              <Card className="mb-5 shadow-lg">
                <Card.Header>
                  <Card.Title>
                    <h3 className="fw-bold ">{t('review')}</h3>
                  </Card.Title>
                </Card.Header>
                <Card.Body>
                  <div className="d-flex gap-1 mb-2">
                    {[1, 2, 3, 4, 5].map((i) => (
                      <div className={`cursor-pointer ${i <= reviewRating ? "text-warning" : ""} fs-2`}>
                        <FontAwesomeIcon icon={faStar} onClick={() => setReviewRating(i)}/>
                      </div>
                    ))}
                  </div>
                  <textarea className="form-control" onChange={(e) => setReviewComment(e)}>{reviewComment}</textarea>
                  <InputError message={reviewForm.errors.rating}/>
                  <InputError message={reviewForm.errors.comment}/>
                </Card.Body>
                <Card.Footer className="text-end pt-0">
                  <Button variant={"primary"}
                          onClick={submitReview}
                          disabled={!(providerReview?.rating !== reviewRating || providerReview?.comment !== reviewComment)}
                  >
                    {
                      reviewForm.processing ?
                        <span className="indicator-progress" style={{display: 'block'}}>
                          {t('Please wait...')}
                          <span className="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                        :
                        <span className='indicator-label'>{t('Continue')}</span>
                    }
                  </Button>
                </Card.Footer>
              </Card>
            }

            {
              userReview &&
              <Card className="mb-5 shadow-lg">
                <Card.Header>
                  <Card.Title>
                    <h3 className="fw-bold ">{t('user review')}</h3>
                  </Card.Title>
                </Card.Header>
                <Card.Body>
                  <div className="d-flex gap-1 mb-2">
                    {[1, 2, 3, 4, 5].map((i) => (
                      <div className={`${i <= userReview.rating ? "text-warning" : ""} fs-2`}>
                        <FontAwesomeIcon icon={faStar}/>
                      </div>
                    ))}
                  </div>
                  <p>{userReview.comment}</p>
                </Card.Body>
              </Card>
            }

            <Modal
              show={createOfferModal as boolean}
              onHide={closeCreateOfferModal}
              size="lg"
              aria-labelledby="contained-modal-title-vcenter"
              centered
            >

              <Modal.Header closeButton>
                <Modal.Title>{t('create new offer')}</Modal.Title>
              </Modal.Header>
              <Modal.Body>
                <Form.Group className="mb-3">
                  <Form.Control
                    onChange={(e) => {
                      OfferForm.setData('price', parseFloat(e.currentTarget.value))
                    }}
                    type='number'
                    step={0.01}
                    placeholder={t('offered amount')}
                  />
                  <InputError message={OfferForm.errors.price}/>
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Control
                    onChange={(e) => {
                      OfferForm.setData('description', e.currentTarget.value)
                    }}
                    as='textarea'
                    placeholder={t('description')}
                    rows={5}
                  />
                  <InputError message={OfferForm.errors.description}/>
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

            <Modal
              show={editOfferModal}
              onHide={closeEditOfferModal}
              size="lg"
              aria-labelledby="contained-modal-title-vcenter"
              centered
            >

              <Modal.Header closeButton>
                <Modal.Title>{t('edit offer')}</Modal.Title>
              </Modal.Header>
              <Modal.Body>
                <Form.Group className="mb-3">
                  <Form.Control
                    onChange={(e) => {
                      OfferForm.setData('price', parseFloat(e.currentTarget.value))
                    }}
                    type='number'
                    step={0.01}
                    placeholder={t('offered amount')}
                    value={OfferForm.data.price}
                  />
                  <InputError message={OfferForm.errors.price}/>
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Control
                    onChange={(e) => {
                      OfferForm.setData('description', e.currentTarget.value)
                    }}
                    as='textarea'
                    placeholder={t('description')}
                    rows={5}
                    value={OfferForm.data.description}
                  />
                  <InputError message={OfferForm.errors.description}/>
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
          <Col sm={12} md={4} className="mx-auto">
            <Card>
              <Card.Header>
                <div className="card-title flex-column">
                  <h3 className="fw-bold mb-1">{t('attachments')}</h3>
                </div>
              </Card.Header>
              <Card.Body>
                <div className="d-flex flex-column mb-9">
                  {order.media?.map((media) => (
                    <div key={media.id} className="d-flex align-items-center mb-5">
                      {
                        media.type === 'image' ? (
                          <div className="symbol symbol-30px me-5">
                            <img alt="Icon" src={media.url}/>
                          </div>
                        ) : media.type === 'pdf' ? (
                          <div className="symbol symbol-30px me-5">
                            <img alt="Icon" src={url('/media/svg/files/pdf.svg')}/>
                          </div>
                        ) : (
                          <div className="symbol symbol-30px me-5">
                            <img alt="Icon" src={url('/media/svg/files/doc.svg')}/>
                          </div>
                        )
                      }
                      <div className="fw-semibold">
                        <a className="fs-6 fw-bold text-gray-900 text-hover-primary" href="#">{media.file_name}</a>
                        <div className="text-gray-500">{media.size}</div>
                      </div>
                      <a href={media.url} target="_blank"
                         className="btn btn-clean btn-sm btn-icon btn-icon-primary btn-active-light-primary ms-auto"
                         data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <KTIcon iconName={'arrow-down'} className='fs-1'/>
                      </a>
                    </div>
                  ))}
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Content>
    </>
  );
}

Show.layout = (page: React.ReactElement) => <ProviderLayout children={page}/>
export default Show;
