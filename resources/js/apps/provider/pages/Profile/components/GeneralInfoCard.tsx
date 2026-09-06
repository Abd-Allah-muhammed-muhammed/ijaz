import { Col, FormControl, FormGroup, FormLabel, FormSelect, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import type { InertiaFormProps } from '@inertiajs/react';
import InputError from '@/shared/components/inputs/InputError';
import { SectionCard } from '@/shared/components/ui';
import {
  PROFILE_ABOUT_MAX_LENGTH,
  PROFILE_ADDRESS_MAX_LENGTH,
  PROFILE_CARD_CLASS,
  PROFILE_EMAIL_MAX_LENGTH,
  PROFILE_FIELD_LABEL_REQUIRED_CLASS,
  PROFILE_IBAN_MAX_LENGTH,
  PROFILE_NAME_MAX_LENGTH,
  PROFILE_PHONE_MAX_LENGTH,
} from '@/apps/provider/pages/Profile/constants';
import { useRegionCityCascade } from '@/apps/provider/pages/Profile/hooks/use-region-city-cascade';
import type { ProfileFormData } from '@/apps/provider/pages/Profile/types';
import type { City, ProviderType, Region } from '@/shared/types/models';

export type GeneralInfoCardProps = {
  form: InertiaFormProps<ProfileFormData>;
  types: ProviderType[];
  regions: Region[];
  cities: City[];
  onProviderTypeChange: (providerTypeId: number | null) => void;
};

export default function GeneralInfoCard({
  form,
  types,
  regions,
  cities,
  onProviderTypeChange,
}: GeneralInfoCardProps) {
  const { t } = useTranslation();
  const T = t as (key: string) => string;
  const { citiesForRegion, applyRegionChange } = useRegionCityCascade(
    cities,
    form.data.region_id,
  );

  return (
    <SectionCard title={T('general.information')} className={PROFILE_CARD_CLASS}>
      <Row className="g-4">
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('name')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('name')}
              type="text"
              maxLength={PROFILE_NAME_MAX_LENGTH}
              value={form.data.name ?? ''}
              onChange={(event) => form.setData('name', event.currentTarget.value)}
            />
            <InputError message={form.errors.name} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('email')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('email')}
              type="email"
              maxLength={PROFILE_EMAIL_MAX_LENGTH}
              value={form.data.email ?? ''}
              onChange={(event) => form.setData('email', event.currentTarget.value)}
            />
            <InputError message={form.errors.email} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('phone')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('phone')}
              type="tel"
              maxLength={PROFILE_PHONE_MAX_LENGTH}
              value={form.data.phone ?? ''}
              onChange={(event) => form.setData('phone', event.currentTarget.value)}
            />
            <InputError message={form.errors.phone} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('iban')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('iban')}
              type="text"
              maxLength={PROFILE_IBAN_MAX_LENGTH}
              value={form.data.iban ?? ''}
              onChange={(event) => form.setData('iban', event.currentTarget.value)}
            />
            <InputError message={form.errors.iban} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('address')}</FormLabel>
            <FormControl
              className="form-control-solid"
              placeholder={t('address')}
              type="text"
              maxLength={PROFILE_ADDRESS_MAX_LENGTH}
              value={form.data.address ?? ''}
              onChange={(event) => form.setData('address', event.currentTarget.value)}
            />
            <InputError message={form.errors.address} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>
              {t('provider_types')}
            </FormLabel>
            <FormSelect
              className="form-select-solid"
              value={form.data.provider_type_id ?? ''}
              onChange={(event) => {
                const value = parseInt(event.currentTarget.value, 10) || null;
                onProviderTypeChange(value);
              }}
            >
              <option value="">{t('choose')}</option>
              {types.map((type) => (
                <option key={`type-${type.id}`} value={type.id}>
                  {type.name}
                </option>
              ))}
            </FormSelect>
            <InputError message={form.errors.provider_type_id} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('region')}</FormLabel>
            <FormSelect
              className="form-select-solid"
              value={form.data.region_id ?? ''}
              onChange={(event) => {
                const value = parseInt(event.currentTarget.value, 10) || null;
                const next = applyRegionChange(value);
                form.setData((previous) => ({
                  ...previous,
                  region_id: next.region_id,
                  city_id: next.city_id,
                }));
              }}
            >
              <option value="">{t('choose')}</option>
              {regions.map((region) => (
                <option key={`region-${region.id}`} value={region.id}>
                  {region.title}
                </option>
              ))}
            </FormSelect>
            <InputError message={form.errors.region_id} />
          </FormGroup>
        </Col>
        <Col xs={12} md={6} lg={4}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('city')}</FormLabel>
            <FormSelect
              className="form-select-solid"
              value={form.data.city_id ?? ''}
              onChange={(event) => {
                const value = parseInt(event.currentTarget.value, 10) || null;
                form.setData('city_id', value);
              }}
            >
              <option value="">{t('choose')}</option>
              {citiesForRegion.map((city) => (
                <option key={`city-${city.id}`} value={city.id}>
                  {city.title}
                </option>
              ))}
            </FormSelect>
            <InputError message={form.errors.city_id} />
          </FormGroup>
        </Col>
        <Col xs={12}>
          <FormGroup className="mb-0">
            <FormLabel className={PROFILE_FIELD_LABEL_REQUIRED_CLASS}>{t('about')}</FormLabel>
            <textarea
              rows={3}
              className="form-control form-control-solid"
              placeholder={t('about')}
              maxLength={PROFILE_ABOUT_MAX_LENGTH}
              value={form.data.about ?? ''}
              onChange={(event) => form.setData('about', event.currentTarget.value)}
            />
            <InputError message={form.errors.about} />
          </FormGroup>
        </Col>
      </Row>
    </SectionCard>
  );
}
