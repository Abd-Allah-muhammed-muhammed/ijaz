import { Form, Modal } from 'react-bootstrap';
import { router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import ActionButton from '@/shared/components/action-button';
import InputError from '@/shared/components/inputs/InputError';
import { SECONDARY_BUTTON_DEFAULT_CLASS } from '@/shared/components/ui';
import { formatCurrency } from '@/shared/lib/formatters';
import { walletWithdrawFormSchema } from '@/apps/provider/pages/Auth/Profile/wallet-forms-schems';
import WithdrawController from '@/actions/Modules/Wallet/Http/Controllers/Provider/WithdrawController';

type Props = {
  show: boolean;
  onHide: () => void;
  reloadOnly?: string[];
  /** Wallet funds available to withdraw (balance − holds), when known at the trigger site. */
  availableBalance?: number | string | null;
};

const FOOTER_BUTTON_CLASS = 'min-w-100px';

const WithdrawModal = ({
  show,
  onHide,
  reloadOnly,
  availableBalance,
}: Props) => {
  const WithdrawForm = useForm<walletWithdrawFormSchema>();
  const { t, i18n } = useTranslation();

  const formattedAvailable =
    availableBalance === null || availableBalance === undefined
      ? null
      : formatCurrency(availableBalance, {
          locale: i18n.language,
          currencyLabel: t('SAR'),
          maximumFractionDigits: 2,
          minimumFractionDigits: 0,
        });

  return (
    <Modal show={show} onHide={onHide} centered>
      <Modal.Header closeButton>
        <Modal.Title>{t('withdraw')}</Modal.Title>
      </Modal.Header>
      <Modal.Body className="d-flex flex-column gap-5">
        <Form.Group controlId="withdraw-amount">
          <Form.Label className="fw-semibold text-gray-800">
            {t('amount')}
          </Form.Label>
          <Form.Control
            type="number"
            placeholder={t('amount')}
            step={0.01}
            min={1}
            onChange={(e) => {
              WithdrawForm.setData('amount', parseFloat(e.target.value));
            }}
          />
          {formattedAvailable ? (
            <div className="text-muted fs-7 mt-2">
              {t('available_balance', { amount: formattedAvailable })}
            </div>
          ) : null}
          <InputError message={WithdrawForm.errors.amount} />
        </Form.Group>

        <Form.Group controlId="withdraw-notes">
          <Form.Label className="fw-semibold text-gray-800">
            {t('user_note')}
          </Form.Label>
          <Form.Control
            as="textarea"
            rows={3}
            placeholder={t('withdraw_notes_placeholder')}
            onChange={(e) =>
              WithdrawForm.setData('user_notes', e.currentTarget.value)
            }
          />
          <InputError message={WithdrawForm.errors.user_notes} />
        </Form.Group>
      </Modal.Body>
      <Modal.Footer className="d-flex flex-wrap gap-2 justify-content-end">
        <button
          type="button"
          className={`${SECONDARY_BUTTON_DEFAULT_CLASS} ${FOOTER_BUTTON_CLASS}`}
          onClick={onHide}
        >
          {t('close')}
        </button>
        <ActionButton
          type="submit"
          className={`btn btn-primary ${FOOTER_BUTTON_CLASS}`}
          isProcessing={WithdrawForm.processing}
          onClick={(e) => {
            e.preventDefault();
            WithdrawForm.submit(WithdrawController.store(), {
              onSuccess: () => {
                onHide();
                WithdrawForm.reset();
                if (reloadOnly && reloadOnly.length > 0) {
                  router.reload({ only: reloadOnly });
                }
              },
            });
          }}
          text={t('withdraw')}
        />
      </Modal.Footer>
    </Modal>
  );
};

export default WithdrawModal;
