import { useState } from 'react';
import { Form, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import type { City, Region } from '@/shared/types/models';
import { SAUDI_IBAN_MAX_LENGTH, SAUDI_PHONE_MAX_LENGTH } from '../../providerSchema';
import { usePrecognitionBlur } from '../../hooks/use-precognition-blur';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import RegistrationFormGroup from '../RegistrationFormGroup';
import StepShell from '../StepShell';

export type AccountInfoStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  regions: Region[];
  cities: City[];
};

export default function AccountInfoStep({
  isCurrent,
  form,
  regions,
  cities,
}: AccountInfoStepProps) {
  const { t } = useTranslation();
  const [citiesData, setCitiesData] = useState<City[]>([]);

  const onPhoneBlur = usePrecognitionBlur('phone', form.data.phone, (field) => {
    form.validate(field);
  });
  const onEmailBlur = usePrecognitionBlur('email', form.data.email, (field) => {
    form.validate(field);
  });
  const onIbanBlur = usePrecognitionBlur('iban', form.data.iban, (field) => {
    form.validate(field);
  });

  return (
    <StepShell
      isCurrent={isCurrent}
      heading={<h2 className="fw-bold text-gray-900">{t('account_information')}</h2>}
    >
      <Row>
        <RegistrationFormGroup label={t('name')} required error={form.errors.name} md={6}>
          <Form.Control
            type="text"
            placeholder={t('name')}
            onChange={(event) => {
              form.setData('name', event.currentTarget.value);
            }}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup label={t('phone')} required error={form.errors.phone} md={6}>
          <Form.Control
            type="tel"
            placeholder={t('phone')}
            maxLength={SAUDI_PHONE_MAX_LENGTH}
            value={form.data.phone ?? ''}
            onChange={(event) => {
              form.setData('phone', event.currentTarget.value);
            }}
            onBlur={onPhoneBlur}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup label={t('email')} required error={form.errors.email} md={6}>
          <Form.Control
            type="email"
            placeholder={t('email')}
            value={form.data.email ?? ''}
            onChange={(event) => {
              form.setData('email', event.currentTarget.value);
            }}
            onBlur={onEmailBlur}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup label={t('iban')} required error={form.errors.iban} md={6}>
          <Form.Control
            type="text"
            placeholder={t('iban')}
            maxLength={SAUDI_IBAN_MAX_LENGTH}
            value={form.data.iban ?? ''}
            onChange={(event) => {
              form.setData('iban', event.currentTarget.value);
            }}
            onBlur={onIbanBlur}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup label={t('password')} required error={form.errors.password} md={6}>
          <Form.Control
            type="password"
            placeholder={t('password')}
            onChange={(event) => {
              form.setData('password', event.currentTarget.value);
            }}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup
          label={t('password_confirmation')}
          required
          error={form.errors.password_confirmation}
          md={6}
        >
          <Form.Control
            type="password"
            placeholder={t('password_confirmation')}
            onChange={(event) => {
              form.setData('password_confirmation', event.currentTarget.value);
            }}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup
          label={t('region')}
          required
          error={form.errors.region_id}
          md={6}
          groupClassName="fv-row mb-10"
        >
          <Form.Select
            defaultValue={form.data.region_id as unknown as string}
            onChange={(event) => {
              const val = Number.parseInt(event.currentTarget.value, 10);
              setCitiesData(
                cities.filter(
                  (city) =>
                    Number.parseInt(city.region_id as unknown as string, 10)
                    === Number.parseInt(event.currentTarget.value, 10),
                ),
              );
              form.setData('region_id', val || null);
            }}
          >
            <option>{t('choose')}</option>
            {regions.map((region) => (
              <option key={`region-${region.id}`} value={region.id}>
                {region.title}
              </option>
            ))}
          </Form.Select>
        </RegistrationFormGroup>

        <RegistrationFormGroup
          label={t('city')}
          required
          error={form.errors.city_id}
          md={6}
          groupClassName="fv-row mb-10"
        >
          <Form.Select
            defaultValue={form.data.city_id as unknown as string}
            onChange={(event) => {
              form.setData(
                'city_id',
                event.currentTarget.value ? Number.parseInt(event.currentTarget.value, 10) : null,
              );
            }}
          >
            <option>{t('choose')}</option>
            {citiesData.map((city) => (
              <option key={`city-${city.id}`} value={city.id}>
                {city.title}
              </option>
            ))}
          </Form.Select>
        </RegistrationFormGroup>

        <RegistrationFormGroup
          label={t('address')}
          required
          error={form.errors.address}
          groupClassName="fv-row mb-10"
        >
          <Form.Control
            type="text"
            onChange={(event) => {
              form.setData('address', event.currentTarget.value);
            }}
          />
        </RegistrationFormGroup>

        <RegistrationFormGroup label={t('about_you')} required error={form.errors.about}>
          <Form.Control
            as="textarea"
            rows={3}
            placeholder="مثال: أقدّم خدمات العامة ، إدخال بيانات، وإنجاز مهام متعددة"
            onChange={(event) => {
              form.setData('about', event.currentTarget.value);
            }}
          />
        </RegistrationFormGroup>
      </Row>
    </StepShell>
  );
}
