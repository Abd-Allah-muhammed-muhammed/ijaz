import { useTranslation } from 'react-i18next';
import {Category, ProviderType} from '@/types/models';
import {Col, Form as BTForm, FormCheck, FormControl, FormGroup, FormLabel, Row} from 'react-bootstrap';
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {FormInput, ProviderTypeFilesEnumValues, TranslatedAttributes} from "./types";
import ActionButton from "@/components/action-button";
import {getSupportedLocales} from "@/hooks/use-locales";
import InputError from "@/components/inputs/InputError";
import ProviderTypeController from "@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController";
import {ProviderTypeFilesEnum} from "@/Enums/Enums";
import ImageInput from "@/components/inputs/ImageInput";
import Select from 'react-select';
import {useState} from 'react';
import {SelectOption} from '@/types';

type Props = {
  row?: ProviderType
  callback?: (form: InertiaFormProps<FormInput>) => void
  categories?: Category[];
};

export default function Form({callback, row, categories}: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();
  const [selectedCategories, setSelectedCategories] = useState<SelectOption[]>(row?.categories?.map(category => ({
    value: category.id,
    label: category.title,
  })) as SelectOption[]);
  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>((previousValue: Record<string, TranslatedAttributes>, currentValue) => {
      const Translation = row?.translations?.[currentValue];
      previousValue[currentValue] = {
        name: Translation?.name || '',
        description: Translation?.description || '',
      };
      return previousValue;
    }, {}),
    files: Object
      .values(ProviderTypeFilesEnum)
      .reduce<Record<ProviderTypeFilesEnumValues, boolean>>
      (
        (previousValue, currentValue) => ({
          ...previousValue,
          [currentValue]: row?.files?.[currentValue] || false,
        }),
        {} as Record<ProviderTypeFilesEnumValues, boolean>
      ),
    image: undefined,
    categories: row?.categories?.map(i => i.id as string) || [],
  });
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form);
      }
    }}>
      <Row className='mb-5'>
        <Col sm={12} md={2} className='mb-5'>
          <ImageInput callback={(e) => {
            form.setData('image', e.currentTarget.files![0]);
          }} url={row?.image}/>
          <InputError message={form.errors.image}/>
        </Col>
        <Col sm={12} md={10}>
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

          <Row>
            {Object.keys(locales).map((locale => (
              <Col sm={12} md={6} className="mb-3" key={locale}>
                <FormGroup>
                  <FormLabel aria-required={true} className="required">
                    {t(`description in ${locale}`)}
                  </FormLabel>
                  <FormControl
                    as="textarea"
                    placeholder={t(`description in ${locale}`)}
                    type='textarea'
                    onChange={(e) => {
                      const value = e.currentTarget.value;
                      form.setData((previousData) => ({
                        ...previousData,
                        translations: {
                          ...previousData.translations,
                          [locale]: {
                            ...previousData.translations[locale],
                            description: value,
                          },
                        },
                      }));
                    }}
                    defaultValue={form.data.translations?.[locale as unknown as number]?.description}
                  />
                  <InputError message={form.errors[`translations.${locale}.description`]}/>
                </FormGroup>
              </Col>
            )))}
          </Row>
        </Col>
      </Row>

      {Object.entries(ProviderTypeFilesEnum).map(([key, value]) => (
        <FormGroup
          key={key}
          as={'div'}
          className="d-flex justify-content-between align-content-center mb-5"
          style={{
            width: '100%',
          }}>
          <FormLabel htmlFor={`group-files-${value}`}>
            {t(value)}
          </FormLabel>
          <FormCheck
            id={`group-files-${value}`}
            className="form-check-solid"
            type="switch"
            checked={form.data.files[value]}
            onChange={(e) => {
              const checked = e.currentTarget.checked;
              form.setData((previousData) => ({
                ...previousData,
                files: {
                  ...previousData.files,
                  [value]: checked,
                },
              }));
            }}
          />
        </FormGroup>
      ))}

      <Row className='mb-5'>
        <Col md={6}>
          <FormGroup>
            <FormLabel aria-required={true} className="required">
              {t('categories')}
            </FormLabel>
            <Select
              isMulti
              options={categories?.map(category => ({
                value: category.id,
                label: category.title,
              })) as SelectOption[]}

              value={selectedCategories}
              onChange={(values) => {
                setSelectedCategories(values as { label: string; value: string }[]);
                form.setData('categories', values.map(i => i.value as string));
              }}
            />
            <InputError message={form.errors.categories}/>
          </FormGroup>
        </Col>
      </Row>

      <div className="my-3 d-flex gap-3 justify-content-end">
        <Link href={ProviderTypeController.index().url} className="btn btn-light">
          {t('cancel')}
        </Link>
        <ActionButton
          type="submit"
          isProcessing={form.processing}
          text={t('save')}
        />
      </div>
    </BTForm>
  );
}
