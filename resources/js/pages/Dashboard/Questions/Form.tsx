import { useTranslation } from 'react-i18next';
import {Question} from "@/types/models";
import {Col, Form as BTForm, FormControl, FormGroup, FormLabel, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {TranslatedAttributes} from "./types";
import ActionButton from "@/components/action-button";
import {getSupportedLocales} from "@/hooks/use-locales";
import InputError from "@/components/inputs/InputError";
import {Inputs} from "@/pages/Dashboard/Pages/validation";
import QuestionController from "@/actions/Modules/Cms/Http/Controllers/Dashboard/QuestionController";

type Props = {
  /**
   * The role to be edited
   */
  row?: Question
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<Inputs>) => void

};

export default function Form({callback, row}: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();
  const form = useForm<Inputs>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>((previousValue: Record<string, TranslatedAttributes>, currentValue) => {
      const translation = row?.translations?.[currentValue];
      previousValue[currentValue] = {
        title: translation?.title || '',
        answer: translation?.answer || '',
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
              <Col sm={12} className="mb-3" key={locale}>
                <FormGroup>
                  <FormLabel aria-required={true} className="required">
                    {t(`title in ${locale}`)}
                  </FormLabel>
                  <FormControl
                    placeholder={t(`title in ${locale}`)}
                    type='text'
                    onChange={(e) => {
                      const value = e.currentTarget.value;
                      form.setData((previousData) => ({
                        ...previousData,
                        translations: {
                          ...previousData.translations,
                          [locale]: {
                            ...previousData.translations[locale],
                            title: value,
                          },
                        },
                      }));
                    }}
                    defaultValue={form.data.translations?.[locale]?.title as string}
                  />
                  <InputError message={form.errors[`translations.${locale}.title`]}/>
                </FormGroup>
                <FormGroup>
                  <FormLabel aria-required={true} className="required">
                    {t('answer in', {locale})}
                  </FormLabel>
                  <FormControl
                    as={'textarea'}
                    rows={10}
                    placeholder={t('answer in', {locale})}
                    type='text'
                    onChange={(e) => {
                      const value = e.currentTarget.value;
                      form.setData((previousData) => ({
                        ...previousData,
                        translations: {
                          ...previousData.translations,
                          [locale]: {
                            ...previousData.translations[locale],
                            answer: value,
                          },
                        },
                      }));
                    }}
                    defaultValue={form.data.translations?.[locale]?.answer as string}
                  />
                  <InputError message={form.errors[`translations.${locale}.answer`]}/>
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
          <Link href={QuestionController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
