import ElectronicBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/ElectronicBrandController';
import ActionButton from '@/components/action-button';
import ImageInput from '@/components/inputs/ImageInput';
import InputError from '@/components/inputs/InputError';
import { getSupportedLocales } from '@/hooks/use-locales';
import { ElectronicBrand } from '@/types/models';
import { InertiaFormProps, Link, useForm } from '@inertiajs/react';
import { Form as BTForm, Col, FormCheck, FormControl, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { FormInput, TranslatedAttributes } from './types';

type Props = {
  electronicBrand?: ElectronicBrand;
  image: string;
  callback?: (form: InertiaFormProps<FormInput>) => void;
};

export default function Form({ callback, electronicBrand, image }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();

  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>(
      (previousValue: Record<string, TranslatedAttributes>, currentValue) => {
        const electronicBrandTranslation = electronicBrand?.translations?.[currentValue];
        previousValue[currentValue] = {
          name: electronicBrandTranslation?.name || '',
        };
        return previousValue;
      },
      {},
    ),
    image: undefined,
    is_active: electronicBrand?.is_active ?? true,
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
        <Col sm={12} md={2}>
          <ImageInput
            url={image}
            callback={(data) => {
              form.setData('image', data.currentTarget.files![0]);
            }}
          />
          <InputError message={form.errors.image} />
        </Col>
        <Col sm={12} md={10} className="mb-3">
          <Row>
            {Object.keys(locales).map((locale) => (
              <Col sm={12} md={6} className="mb-3" key={locale}>
                <FormGroup>
                  <FormLabel aria-required={true} className="required">
                    {t(`name in ${locale}`)}
                  </FormLabel>
                  <FormControl
                    placeholder={t(`name in ${locale}`)}
                    type="text"
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
                    defaultValue={form.data.translations?.[locale]?.name}
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
                  id="is_active_switch"
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
          <Link href={ElectronicBrandController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
