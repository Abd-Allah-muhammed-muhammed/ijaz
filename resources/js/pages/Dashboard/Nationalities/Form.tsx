import { useTranslation } from 'react-i18next';
import {Nationality} from "@/types/models";
import {Col, Form as BTForm, FormControl, FormGroup, FormLabel, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {FormInput, TranslatedAttributes} from "./types";
import ActionButton from "@/components/action-button";
import {getSupportedLocales} from "@/hooks/use-locales";
import CategoryController from "@/actions/App/Http/Controllers/Dashboard/CategoryController";
import InputError from "@/components/inputs/InputError";
import NationalityController from "@/actions/App/Http/Controllers/Dashboard/NationalityController";

type Props = {
  /**
   * The role to be edited
   */
  row?: Nationality
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void

};

export default function Form({callback, row}: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();
  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>((previousValue: Record<string, TranslatedAttributes>, currentValue) => {
      const categoryTranslation = row?.translations?.[currentValue];
      previousValue[currentValue] = {
        name: categoryTranslation?.name || '',
      };
      return previousValue;
    }, {} as Record<string, TranslatedAttributes>),

  });
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <Row>
        <Col sm={12} md={12} className="mb-3">
          <Row>
            {Object.keys(locales).map((locale => (
              <Col sm={12} md={6} className="mb-3" key={locale}>
                <FormGroup>
                  <FormLabel aria-required={true} className="required">
                    {t(`name in ${locale}`)}
                  </FormLabel>
                  <FormControl
                    placeholder={t(`name in ${locale}`)}
                    type='text'
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
                    defaultValue={form.data.translations?.[locale as unknown as number]?.name}
                  />
                  <InputError message={form.errors[`translations.${locale}.name`]}/>
                </FormGroup>
              </Col>
            )))}
          </Row>
        </Col>
      </Row>
      <Row>
        <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
          <ActionButton
            type="submit"
            isProcessing={form.processing}
            text={t('save')}
          />
          <Link href={NationalityController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
