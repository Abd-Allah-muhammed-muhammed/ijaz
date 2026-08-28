import BankController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/BankController';
import ActionButton from '@/shared/components/action-button';
import ImageInput from '@/shared/components/inputs/ImageInput';
import InputError from '@/shared/components/inputs/InputError';
import { getSupportedLocales } from '@/shared/hooks/use-locales';
import { Bank } from '@/shared/types/models';
import { InertiaFormProps, Link, useForm } from '@inertiajs/react';
import { Form as BTForm, Col, FormCheck, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { FormInput, TranslatedAttributes } from './types';

type Props = {
  row?: Bank;
  logoUrl: string;
  callback?: (form: InertiaFormProps<FormInput>) => void;
};

export default function Form({ callback, row, logoUrl }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();

  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>(
      (previousValue, currentValue) => {
        const bankTranslation = row?.translations?.[currentValue];
        previousValue[currentValue] = {
          name: bankTranslation?.name || '',
        };
        return previousValue;
      },
      {},
    ),
    logo: undefined,
    is_active: row?.is_active ?? true,
  });

  return (
    <BTForm
      onSubmit={(e) => {
        e.preventDefault();
        if (callback) {
          callback(form);
        }
      }}
    >
      <Row>
        <Col sm={12} md={3} lg={2} className="mb-3">
          <FormLabel>{t('logo')}</FormLabel>
          <div style={{ maxWidth: 120 }}>
            <ImageInput
              url={logoUrl}
              callback={(data) => {
                form.setData('logo', data.currentTarget.files![0]);
              }}
            />
          </div>
          <InputError message={form.errors.logo} />
        </Col>
        <Col sm={12} md={9} lg={10} className="mb-3">
          <Row>
            {Object.keys(locales).map((locale) => (
              <Col sm={12} md={6} className="mb-3" key={locale}>
                <FormGroup>
                  <FormLabel aria-required className="required">
                    {t(`name in ${locale}`)}
                  </FormLabel>
                  <input
                    className="form-control"
                    placeholder={t(`name in ${locale}`)}
                    type="text"
                    value={form.data.translations?.[locale]?.name || ''}
                    onChange={(e) => {
                      const value = e.currentTarget.value;
                      form.setData((previousData) => ({
                        ...previousData,
                        translations: {
                          ...previousData.translations,
                          [locale]: {
                            ...previousData.translations[locale],
                            name: value,
                          },
                        },
                      }));
                    }}
                  />
                  <InputError message={form.errors[`translations.${locale}.name`]} />
                </FormGroup>
              </Col>
            ))}
          </Row>
          <Row>
            <Col sm={12} md={6} className="mb-3">
              <FormGroup>
                <FormLabel>{t('is_active')}</FormLabel>
                <FormCheck
                  type="switch"
                  id="bank_is_active_switch"
                  label={form.data.is_active ? t('active') : t('inactive')}
                  checked={form.data.is_active}
                  onChange={(e) => form.setData('is_active', e.target.checked)}
                />
                <InputError message={form.errors.is_active} />
              </FormGroup>
            </Col>
          </Row>
        </Col>
      </Row>
      <Row>
        <Col sm={12} className="d-flex justify-content-end mb-3 gap-3">
          <ActionButton type="submit" isProcessing={form.processing} text={t('save')} />
          <Link href={BankController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
