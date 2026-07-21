import { useTranslation } from 'react-i18next';
import {Category} from "@/types/models";
import {Col, Form as BTForm, FormControl, FormGroup, FormLabel, FormSelect, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {FormInput, TranslatedAttributes} from "./types";
import ActionButton from "@/components/action-button";
import {getSupportedLocales} from "@/hooks/use-locales";
import CategoryController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/CategoryController";
import ImageInput from "@/components/inputs/ImageInput";
import InputError from "@/components/inputs/InputError";

type Props = {
  /**
   * The role to be edited
   */
  category?: Category
  categories: Category[]
  image: string
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInput>) => void

};

export default function Form({callback, category, categories, image}: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();

  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>((previousValue: Record<string, TranslatedAttributes>, currentValue) => {
      const categoryTranslation = category?.translations?.[currentValue];
      previousValue[currentValue] = {
        title: categoryTranslation?.title || '',
      };
      return previousValue;
    }, {}),
    icon: undefined,
    parent_id: category?.parent_id as unknown as string || ''

  });
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <Row>
        <Col sm={12} md={2}>
          <ImageInput
            url={image}
            callback={(data) => {
              form.setData('icon', data.currentTarget.files![0]);
            }}/>
          <InputError message={form.errors.icon}/>
        </Col>
        <Col sm={12} md={10} className="mb-3">
          <Row>
            {Object.keys(locales).map((locale => (
              <Col sm={12} md={6} className="mb-3" key={locale}>
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
                    defaultValue={form.data.translations?.[locale as unknown as number]?.title}
                  />
                  <InputError message={form.errors[`translations.${locale}.title`]}/>
                </FormGroup>
              </Col>
            )))}
          </Row>
          <br/>
          <Row>
            <Col sm={12} md={6} className="mb-3">
              <FormGroup>
                <FormLabel aria-required={true} className='required'> {t('category')} </FormLabel>
                <FormSelect
                  defaultValue={form.data.parent_id}
                  onChange={(e) => {
                    const value = e.currentTarget.value;
                    form.setData('parent_id', value);
                  }}
                >
                  <option value={''}>{t('choose')}</option>
                  {categories.map((category) => (
                    <option key={`category-${category.id}`} value={category.id}>
                      {category.title}
                    </option>
                  ))}
                </FormSelect>
                {/*<InputError message={form.errors.translations ?. [locale as unknown as number]}/>*/}
              </FormGroup>
            </Col>
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
          <Link href={CategoryController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
