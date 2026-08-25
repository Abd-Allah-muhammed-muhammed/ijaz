import {useEffect, useState} from 'react'
import {Button, Form, Modal} from 'react-bootstrap'
import {router, useForm} from '@inertiajs/react'
import {useTranslation} from 'react-i18next'
import {toast} from 'sonner'
import ActionButton from '@/shared/components/action-button'
import Portal from '@/shared/components/payment/portal'
import {PaymentMethodEnum} from '@/Enums/Payment'
import ImageInput from '@/shared/components/inputs/ImageInput'
import InputError from '@/shared/components/inputs/InputError'
import {walletDepositFormSchema} from '@/apps/provider/pages/Auth/Profile/wallet-forms-schems'
import {useAddBalance} from '@/shared/hooks/use-top-up-query'

type Props = {
  show: boolean
  onHide: () => void
  reloadOnly?: string[]
}

const RechargeModal = ({show, onHide, reloadOnly}: Props) => {
  const RechargeForm = useForm<walletDepositFormSchema>()
  const addBalanceMutator = useAddBalance()
  const [paymentWindow, setPaymentWindow] = useState<Window | null>(null)
  const {t} = useTranslation()

  useEffect(() => {
    if (!paymentWindow) {
      return;
    }
    const handleMessage = (event: MessageEvent) => {
      if (event.origin !== window.location.origin) return;

      if (event.data === 'payment-success') {
        paymentWindow?.close();
        onHide()
        setPaymentWindow(null);
        RechargeForm.reset()
        if (reloadOnly && reloadOnly.length > 0) {
          router.reload({only: reloadOnly});
        }
        toast.success(t('Payment Successful'))
      } else if (event.data === 'payment-failed') {
        setPaymentWindow(null);
        onHide()
        RechargeForm.reset()
        toast.error(t('Payment Failed, Please Try Again'))
        paymentWindow?.close();
      }
    }
    window.addEventListener('message', handleMessage);
    return () => {
      window.removeEventListener('message', handleMessage);
    };
  }, [paymentWindow]);

  return (
    <Modal show={show} onHide={onHide}>
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
        <Button variant="light" onClick={onHide}>
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
                if (res?.success && res?.data?.payable && res?.data?.url) {
                  setPaymentWindow(window.open(res.data.url, 'payment', 'width=800,height=600'));
                  return;
                }
                if (res?.success) {
                  onHide();
                  RechargeForm.reset();
                  if (reloadOnly && reloadOnly.length > 0) {
                    router.reload({only: reloadOnly});
                  }
                  toast.success(
                    res?.message || res?.data?.message || t('Payment Successful'),
                  );
                  return;
                }
                toast.error(res?.message || t('Payment Failed, Please Try Again'));
              },
              onError: (err) => {
                const payload = err.response?.data;
                if (payload && typeof payload === 'object') {
                  if ('errors' in payload && payload.errors && typeof payload.errors === 'object') {
                    const errors = payload.errors as Record<string, string[]>;
                    Object.keys(errors).forEach((key) => {
                      RechargeForm.setError(
                        key as keyof walletDepositFormSchema,
                        errors[key][0],
                      );
                    });
                    return;
                  }
                  if (
                    'message' in payload
                    && typeof (payload as { message?: unknown }).message === 'string'
                    && (payload as { message: string }).message !== ''
                  ) {
                    toast.error((payload as { message: string }).message);
                    return;
                  }
                }
                toast.error(t('Payment Failed, Please Try Again'));
              },
            })
          }}
        />
      </Modal.Footer>
    </Modal>
  )
}

export default RechargeModal
