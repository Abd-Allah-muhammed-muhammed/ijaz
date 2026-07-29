import PropertyTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyTypeController';
import ActionButton from '@/components/action-button';
import InputError from '@/components/inputs/InputError';
import TranslatableInputs from '@/components/inputs/TranslatableInputs';
import { getSupportedLocales } from '@/hooks/use-locales';
import { PropertyType } from '@/types/models';
import { InertiaFormProps, Link, useForm } from '@inertiajs/react';
import { Form as BTForm, Col, FormCheck, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { FormInput, TranslatedAttributes } from './types';

type Props = {
  /**
   * The property type to be edited
   */
  propertyType?: PropertyType;
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void;
};

export default function Form({ callback, propertyType }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();

  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>(
      (previousValue: Record<string, TranslatedAttributes>, currentValue) => {
        const translation = propertyType?.translations?.[currentValue];
        previousValue[currentValue] = {
          name: translation?.name || '',
        };
        return previousValue;
      },
      {},
    ),
    is_active: propertyType?.is_active ?? true,
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
        <Col sm={12} className="mb-3">
          <TranslatableInputs
            field="name"
            values={form.data.translations}
            errors={form.errors}
            onChange={(locale, value) => {
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

          <br />
          <Row>
            <Col sm={12} md={6} className="mb-3">
              <FormGroup>
                <FormLabel>{t('is_active')}</FormLabel>
                <FormCheck
                  key={'is_active'}
                  type="switch"
                  id="custom-switch"
                  label={t('is_active')}
                  checked={form.data.is_active}
                  onChange={(e) => {
                    form.setData('is_active', e.target.checked);
                  }}
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
          <Link href={PropertyTypeController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
