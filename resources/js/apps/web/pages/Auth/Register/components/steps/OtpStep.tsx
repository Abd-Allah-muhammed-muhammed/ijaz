import { useTranslation } from 'react-i18next';
import OTP from '@/shared/components/inputs/OTP';
import InputError from '@/shared/components/inputs/InputError';
import { url } from '@/shared/helpers/general';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import StepShell from '../StepShell';

export type OtpStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  seconds: number;
  formatSeconds: (totalSeconds: number) => string;
  onResend: () => void | Promise<void>;
};

export default function OtpStep({
  isCurrent,
  form,
  seconds,
  formatSeconds,
  onResend,
}: OtpStepProps) {
  const { t } = useTranslation();

  return (
    <StepShell
      isCurrent={isCurrent}
      contentClassName="d-flex flex-center flex-column flex-lg-row-fluid"
    >
      <div className="p-10">
        <div className="form w-100 mb-13">
          <div className="text-center mb-10">
            <img alt="Logo" className="mh-125px" src={url('media/svg/misc/smartphone-2.svg')} />
          </div>
          <div className="text-center mb-10">
            <div className="text-muted fw-semibold fs-5 mb-5">
              {t('enter_the_verification_code_we_sent_to')}
            </div>
            <div className="fw-bold text-gray-900 fs-3">{form.data.phone}</div>
          </div>
          <div className="mb-10">
            <OTP
              type="number"
              onChange={(value) => {
                form.setData('otp', value);
              }}
            />
          </div>
          <InputError message={form.errors.otp} />
        </div>
        <div className="text-center d-flex align-items-center gap-5 fw-semibold fs-5">
          <span className="text-muted me-1">{t('didnt_get_the_code_?')}</span>
          <a
            href="#"
            className="link-primary fs-5"
            onClick={(event) => {
              event.preventDefault();
              void onResend();
            }}
          >
            {seconds > 0 ? (
              <>
                <span className="text-muted me-1">{t('resend_code_in')} </span>
                <span className="fw-bold">{formatSeconds(seconds)}</span>
              </>
            ) : (
              t('resend')
            )}
          </a>
        </div>
      </div>
    </StepShell>
  );
}
