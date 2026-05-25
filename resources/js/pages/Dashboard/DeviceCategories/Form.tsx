import DeviceCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/DeviceCategoryController';
import ActionButton from '@/components/action-button';
import ImageInput from '@/components/inputs/ImageInput';
import InputError from '@/components/inputs/InputError';
import { getSupportedLocales } from '@/hooks/use-locales';
import { Category } from '@/types/models';
import { InertiaFormProps, Link, useForm } from '@inertiajs/react';
import { Form as BTForm, Col, FormControl, FormGroup, FormLabel, FormSelect, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { FormInput, TranslatedAttributes } from './types';

type Props = {
  category?: Category;
  categories: Category[];
  image: string;
  callback?: (form: InertiaFormProps<FormInput>) => void;
};

export default function Form({ callback, category, categories, image }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();

  const form = useForm<FormInput>({
    translations: Object.keys(locales).reduce<Record<string, TranslatedAttributes>>(
      (previousValue: Record<string, TranslatedAttributes>, currentValue) => {
        const categoryTranslation = category?.translations?.[currentValue];
        previousValue[currentValue] = {
          title: categoryTranslation?.title || '',
        };
        return previousValue;
      },
      {},
    ),
    icon: undefined,
    parent_id: (category?.parent_id as unknown as string) || '',
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
              form.setData('icon', data.currentTarget.files![0]);
            }}
          />
          <InputError message={form.errors.icon} />
        </Col>
        <Col sm={12} md={10} className="mb-3">
          <Row>
            {Object.keys(locales).map((locale) => (
              <Col sm={12} md={6} className="mb-3" key={locale}>
                <FormGroup>
                  <FormLabel aria-required={true} className="required">
                    {t(`title in ${locale}`)}
                  </FormLabel>
                  <FormControl
                    placeholder={t(`title in ${locale}`)}
                    type="text"
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
                  <InputError message={form.errors[`translations.${locale}.title`]} />
                </FormGroup>
              </Col>
            ))}
          </Row>

          <Row>
            <Col sm={12} md={6} className="mb-3">
              <FormGroup>
                <FormLabel> {t('parent_category')} </FormLabel>
                <FormSelect
                  defaultValue={form.data.parent_id}
                  onChange={(e) => {
                    const value = e.currentTarget.value;
                    form.setData('parent_id', value);
                  }}
                >
                  <option value={''}>{t('choose')}</option>
                  {categories.map((cat) => (
                    <option key={`category-${cat.id}`} value={cat.id}>
                      {cat.title}
                    </option>
                  ))}
                </FormSelect>
                <InputError message={form.errors.parent_id} />
              </FormGroup>
            </Col>
          </Row>
        </Col>
      </Row>
      <Row>
        <Col sm={12} className="d-flex justify-content-end mb-3 gap-3">
          <ActionButton type="submit" isProcessing={form.processing} text={t('save')} />
          <Link href={DeviceCategoryController.index().url} className="btn btn-light">
            {t('cancel')}
          </Link>
        </Col>
      </Row>
    </BTForm>
  );
}
