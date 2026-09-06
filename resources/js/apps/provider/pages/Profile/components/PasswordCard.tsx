import { Col, FormControl, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import type { InertiaFormProps } from '@inertiajs/react';
import InputError from '@/shared/components/inputs/InputError';
import { SectionCard } from '@/shared/components/ui';
import type { ProfileFormData } from '@/apps/provider/pages/Profile/types';

export type PasswordCardProps = {
  form: InertiaFormProps<ProfileFormData>;
};

export default function PasswordCard({ form }: PasswordCardProps) {
  const { t } = useTranslation();

  return (
    <SectionCard title={t('change_password')} className="mb-5">
      <p className="text-muted fs-7 mb-4">{t('leave_blank_to_keep_current_password')}</p>
      <Row className="g-3">
        <Col xs={12} md={6}>
          <FormGroup>
            <FormLabel>{t('password')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('password')}
              type="password"
              autoComplete="new-password"
              value={form.data.password ?? ''}
              onChange={(event) =>
                form.setData('password', event.currentTarget.value || null)
              }
            />
            <InputError message={form.errors.password} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6}>
          <FormGroup>
            <FormLabel>{t('password_confirmation')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('password_confirmation')}
              type="password"
              autoComplete="new-password"
              value={form.data.password_confirmation ?? ''}
              onChange={(event) =>
                form.setData(
                  'password_confirmation',
                  event.currentTarget.value || null,
                )
              }
            />
            <InputError message={form.errors.password_confirmation} />
          </FormGroup>
        </Col>
      </Row>
    </SectionCard>
  );
}
