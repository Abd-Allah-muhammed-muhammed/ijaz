import {Button, Form, Modal} from 'react-bootstrap'
import {router, useForm} from '@inertiajs/react'
import {useTranslation} from 'react-i18next'
import ActionButton from '@/shared/components/action-button'
import InputError from '@/shared/components/inputs/InputError'
import {walletWithdrawFormSchema} from '@/apps/provider/pages/Auth/Profile/wallet-forms-schems'
import WithdrawController from '@/actions/Modules/Wallet/Http/Controllers/Provider/WithdrawController'

type Props = {
  show: boolean
  onHide: () => void
  reloadOnly?: string[]
}

const WithdrawModal = ({show, onHide, reloadOnly}: Props) => {
  const WithdrawForm = useForm<walletWithdrawFormSchema>()
  const {t} = useTranslation()

  return (
    <Modal show={show} onHide={onHide}>
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
        <Button variant="light" onClick={onHide}>
          {t('close')}
        </Button>
        <ActionButton
          type="submit"
          isProcessing={WithdrawForm.processing}
          onClick={(e) => {
            e.preventDefault();
            WithdrawForm.submit(WithdrawController.store(), {
              onSuccess: ()=> {
                onHide();
                WithdrawForm.reset();
                if (reloadOnly && reloadOnly.length > 0) {
                  router.reload({only: reloadOnly});
                }
              }
            });
          }}
          text={t('withdraw')}
        />
      </Modal.Footer>
    </Modal>
  )
}

export default WithdrawModal
