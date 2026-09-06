import { useState } from 'react';
import { Col, FormControl, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import type { InertiaFormProps } from '@inertiajs/react';
import { KTIcon } from '@/vendor/metronic/helpers';
import InputError from '@/shared/components/inputs/InputError';
import { SectionCard } from '@/shared/components/ui';
import {
  PROFILE_CARD_CLASS,
  PROFILE_FIELD_LABEL_CLASS,
  PROFILE_PASSWORD_MAX_LENGTH,
  PROFILE_PASSWORD_MIN_LENGTH,
} from '@/apps/provider/pages/Profile/constants';
import type { ProfileFormData } from '@/apps/provider/pages/Profile/types';

export type PasswordCardProps = {
  form: InertiaFormProps<ProfileFormData>;
};

export default function PasswordCard({ form }: PasswordCardProps) {
  const { t } = useTranslation();
  const passwordValue = form.data.password ?? '';
  const enforceMinLength = passwordValue.length > 0;
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

  return (
    <SectionCard title={t('change_password')} className={PROFILE_CARD_CLASS}>
      <p className="text-muted fs-7 mb-3">{t('leave_blank_to_keep_current_password')}</p>
      <Row className="g-4">
        <Col xs={12} md={6}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_CLASS}>{t('password')}</FormLabel>
            <div className="input-group">
              <FormControl
                className="form-control-solid"
                placeholder={t('password')}
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                minLength={enforceMinLength ? PROFILE_PASSWORD_MIN_LENGTH : undefined}
                maxLength={PROFILE_PASSWORD_MAX_LENGTH}
                value={passwordValue}
                onChange={(event) =>
                  form.setData('password', event.currentTarget.value || null)
                }
                data-pan="profile-password-input"
              />
              <button
                type="button"
                className="input-group-text border-0 bg-light"
                onClick={() => setShowPassword((value) => !value)}
                aria-label={showPassword ? t('hide_password') : t('show_password')}
                data-pan="profile-password-visibility"
              >
                <KTIcon
                  iconName={showPassword ? 'eye-slash' : 'eye'}
                  className="fs-3"
                />
              </button>
            </div>
            <InputError message={form.errors.password} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_CLASS}>
              {t('password_confirmation')}
            </FormLabel>
            <div className="input-group">
              <FormControl
                className="form-control-solid"
                placeholder={t('password_confirmation')}
                type={showPasswordConfirmation ? 'text' : 'password'}
                autoComplete="new-password"
                minLength={enforceMinLength ? PROFILE_PASSWORD_MIN_LENGTH : undefined}
                maxLength={PROFILE_PASSWORD_MAX_LENGTH}
                value={form.data.password_confirmation ?? ''}
                onChange={(event) =>
                  form.setData(
                    'password_confirmation',
                    event.currentTarget.value || null,
                  )
                }
                data-pan="profile-password-confirmation-input"
              />
              <button
                type="button"
                className="input-group-text border-0 bg-light"
                onClick={() => setShowPasswordConfirmation((value) => !value)}
                aria-label={
                  showPasswordConfirmation ? t('hide_password') : t('show_password')
                }
                data-pan="profile-password-confirmation-visibility"
              >
                <KTIcon
                  iconName={showPasswordConfirmation ? 'eye-slash' : 'eye'}
                  className="fs-3"
                />
              </button>
            </div>
            <InputError message={form.errors.password_confirmation} />
          </FormGroup>
        </Col>
      </Row>
    </SectionCard>
  );
}
