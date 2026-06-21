import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {KTIcon} from "@/_metronic/helpers";
import {ReactElement, useEffect, useState} from "react";
import {Provider} from "@/types/models";
import {useTranslation} from "react-i18next";
import RatingStars from '@/components/RatingStars';
import {Button, Form, Modal} from 'react-bootstrap';
import {router, useForm} from "@inertiajs/react";
import ActionButton from "@/components/action-button";
import Portal from "@/components/payment/portal";
import {PaymentMethodEnum} from "@/Enums/Payment";
import ImageInput from "@/components/inputs/ImageInput";
import { walletDepositFormSchema, walletWithdrawFormSchema } from '@/pages/Provider/Auth/Profile/wallet-forms-schems';
import InputError from "@/components/inputs/InputError";
import {useAddBalance} from "@/hooks/use-top-up-query";
import {toast} from "sonner";
import WithdrawController from '@/actions/Modules/Wallet/Http/Controllers/Provider/WithdrawController';


type Props = {
  children: ReactElement
  provider: Provider
}


const AccountLayout = ({children, provider}: Props) => {
  const [showWithdrawModal, setShowWithdrawModal] = useState(false);
  const [showRechargeModal, setShowRechargeModal] = useState(false);
  const RechargeForm = useForm<walletDepositFormSchema>();
  const WithdrawForm = useForm<walletWithdrawFormSchema>();
  const addBalanceMutator = useAddBalance();
  const [paymentWindow, setPaymentWindow] = useState<Window | null>(null);
  const {t} = useTranslation();


  useEffect(() => {
    if (!paymentWindow) {
      return;
    }
    const handleMessage = (event: MessageEvent) => {
      if (event.origin !== window.location.origin) return;

      if (event.data === 'payment-success') {
        paymentWindow?.close();
        setShowRechargeModal(false)
        setPaymentWindow(null);
        RechargeForm.reset()
        router.reload({only: ['provider', 'transactions']});
        toast.success(t('Payment Successful'))
        // إعادة توجيه أو تحديث UI
      } else if (event.data === 'payment-failed') {
        setPaymentWindow(null);
        setShowRechargeModal(false)
        RechargeForm.reset()
        toast.error(t('Payment Failed, Please Try Again'))
        paymentWindow?.close();
        // عرض رسالة خطأ
      }
    }
    window.addEventListener('message', handleMessage);
    return () => {
      window.removeEventListener('message', handleMessage);
    };
  }, [paymentWindow]);

  return (
    <>
      <ToolbarWrapper/>
      <Content>
        <div className='card mb-5 mb-xl-10'>
          <div className='card-body pt-9 pb-0'>
            <div className='d-flex flex-wrap flex-sm-nowrap mb-3'>
              <div className='me-7 mb-4'>
                <div className='symbol symbol-100px symbol-lg-160px symbol-fixed position-relative'>
                  <img src={provider.logo} alt='Metronic'/>
                  <div
                    className='position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border-4 border-white h-20px w-20px'></div>
                </div>
              </div>

              <div className='flex-grow-1'>
                <div className='d-flex justify-content-between align-items-start flex-wrap mb-2'>
                  <div className='d-flex flex-column'>
                    <div className='d-flex align-items-center mb-2'>
                      <a href='#' className='text-gray-800 text-hover-primary fs-2 fw-bolder me-1'>
                        {provider.name}
                      </a>
                      <a href='#'>
                        <KTIcon iconName='verify' className='fs-1 text-primary'/>
                      </a>
                      <span className='ms-3'>
                          <RatingStars rating={provider.average_rating || 0}/>
                      </span>
                    </div>

                    <div className='d-flex flex-wrap fw-bold fs-6 mb-4 pe-2'>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2'
                      >
                        <KTIcon iconName='profile-circle' className='fs-4 me-1'/>
                        Developer
                      </a>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2'
                      >
                        <KTIcon iconName='geolocation' className='fs-4 me-1'/>
                        {provider.address}
                      </a>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary mb-2'
                      >
                        <KTIcon iconName='sms' className='fs-4 me-1'/>
                        {provider.email}
                      </a>
                    </div>
                  </div>

                  <div className='d-flex my-4'>
                    <Button
                      variant="light"
                      size="sm"
                      className="me-2"
                      onClick={() => setShowWithdrawModal(true)}
                    >
                      <KTIcon iconName='check' className='fs-3 d-none'/>
                      <span className='indicator-label'>{t('withdraw')}</span>
                    </Button>
                    <Button
                      variant="primary"
                      size="sm"
                      className="me-3"
                      onClick={() => setShowRechargeModal(true)}
                    >
                      {t('recharge')}
                    </Button>
                  </div>
                </div>

                <div className='d-flex flex-wrap flex-stack'>
                  <div className='d-flex flex-column flex-grow-1 pe-8'>
                    <div className='d-flex flex-wrap'>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.balance}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('balance')}</div>
                      </div>

                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.total_earning}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('total_earning')}</div>
                      </div>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.total_spent}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('total_spent')}</div>
                      </div>

                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.credit}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('credit')}</div>
                      </div>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.pending_credit}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('pending_credit')}</div>
                      </div>

                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.debit}</div>
                        </div>
                        <div className='fw-bold fs-6 text-gray-500'>{t('debit')}</div>
                      </div>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{provider.wallet?.pending_debit}</div>
                        </div>
                        <div className='fw-bold fs-6 text-gray-500'>{t('pending_debit')}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Withdraw Modal */}
        <Modal show={showWithdrawModal} onHide={() => setShowWithdrawModal(false)}>
          <Modal.Header closeButton>
            <Modal.Title>{t('withdraw')}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group>
              <Form.Control type='number' placeholder={t('amount')} step={0.01} min={1} onChange={(e) => {
                WithdrawForm.setData('amount', parseFloat(e.target.value));
              }}/>
              <InputError message={WithdrawForm.errors.amount}/>
            </Form.Group>
            <Form.Group className="mt-2">
              <Form.Control
                as="textarea" rows={3}
                onChange={e => WithdrawForm.setData('user_notes', e.currentTarget.value)}
              />
              <InputError message={WithdrawForm.errors.user_notes}/>
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowWithdrawModal(false)}>
              {t('close')}
            </Button>
            <ActionButton
              type="submit"
              isProcessing={WithdrawForm.processing}
              onClick={(e) => {
                e.preventDefault();
                WithdrawForm.submit(WithdrawController.store(), {
                  onSuccess: ()=> {
                    setShowWithdrawModal(false);
                    WithdrawForm.reset();
                  }
                });
              }}
              text={t('withdraw')}
            />
          </Modal.Footer>
        </Modal>

        {/* Recharge Modal */}
        <Modal show={showRechargeModal} onHide={() => setShowRechargeModal(false)}>
          <Modal.Header closeButton>
            <Modal.Title>{t('recharge')}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <div>
              <Form.Control type='number' placeholder={t('amount')} step={0.01} min={1} onChange={(e) => {
                RechargeForm.setData('amount', parseFloat(e.target.value));
              }}/>
              <InputError message={RechargeForm.errors.amount}/>
            </div>
            <div className='mt-5'>
              <Portal
                paymentMethod={RechargeForm.data.payment_method || undefined}
                paymentDriver={RechargeForm.data.payment_driver as string || undefined}
                onPaymentMethodChange={(method: any) => RechargeForm.setData('payment_method', method)}
                onPaymentDriverChange={(driver: any) => RechargeForm.setData('payment_driver', driver)}
              />
              <InputError message={RechargeForm.errors.payment_method}/>
              <InputError message={RechargeForm.errors.payment_driver}/>
            </div>
            {RechargeForm.data.payment_method === PaymentMethodEnum.Offline && (
              <div className='mt-5'>
                <Form.Group>
                  <Form.Control
                    as="textarea" rows={3}
                    onChange={e => RechargeForm.setData('user_notes', e.currentTarget.value)}
                  />
                  <InputError message={RechargeForm.errors.user_notes}/>
                </Form.Group>
                <Form.Group className="mt-2">
                  <ImageInput
                    className='img-fluid w-100'
                    style={{
                      maxHeight: '200px', objectFit: 'cover'
                    }}
                    callback={(e) => {
                      RechargeForm.setData('transaction_image', e.currentTarget.files![0]);
                    }}
                  />
                  <InputError message={RechargeForm.errors.transaction_image}/>
                </Form.Group>
              </div>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowRechargeModal(false)}>
              {t('close')}
            </Button>
            <ActionButton
              type="submit"
              isProcessing={addBalanceMutator.isPending}
              text={t('recharge')}

              onClick={() => {
                RechargeForm.clearErrors()
                const validation = walletDepositFormSchema.safeParse(RechargeForm.data)
                if (!validation.success) {
                  validation.error.issues.forEach(issue => {
                    RechargeForm.setError(issue.path.join('.') as keyof walletDepositFormSchema, issue.message)
                  })
                  return;
                }
                addBalanceMutator.mutate(RechargeForm.data, {
                  onSuccess: (res) => {
                    if (res.data.payable) {
                      setPaymentWindow(window.open(res.data.url, 'payment', 'width=800,height=600'));
                      return;
                    }
                    setShowRechargeModal(false)
                    RechargeForm.reset()
                    toast.success(res.data.message)
                  },
                  onError: (err) => {
                    if (err.response?.data && typeof err.response.data === 'object' && 'errors' in err.response.data) {
                      const errors = (err.response.data as { errors: Record<string, string[]> }).errors;
                      Object.keys(errors).forEach((key) => {
                        RechargeForm.setError(key as keyof walletDepositFormSchema, errors[key][0]);
                      });
                    }
                  }
                })
              }}
            />
          </Modal.Footer>
        </Modal>
      </Content>
      {children}
    </>
  );
}

export default AccountLayout
