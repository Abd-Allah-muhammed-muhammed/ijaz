import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Page } from '@/shared/types/models';
import { Form as BTForm, FormControl, FormGroup, FormLabel } from 'react-bootstrap';
import { InertiaFormProps, Link, useForm } from '@inertiajs/react';
import { TranslatedAttributes } from './types';
import ActionButton from '@/shared/components/action-button';
import { getSupportedLocales } from '@/shared/hooks/use-locales';
import InputError from '@/shared/components/inputs/InputError';
import { Inputs } from '@/apps/admin/pages/Pages/validation';
import PageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController';
import PageContentEditor from './PageContentEditor';
import clsx from 'clsx';

type Props = {
  /**
   * The page to be edited
   */
  row?: Page;
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<Inputs>) => void;
};

export default function Form({ callback, row }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();
  const localeKeys = Object.keys(locales);
  const [activeLocale, setActiveLocale] = useState(localeKeys[0] ?? 'en');

  const form = useForm<Inputs>({
    slug: row?.slug || '',
    translations: localeKeys.reduce<Record<string, TranslatedAttributes>>(
      (previousValue, currentValue) => {
        const translation = row?.translations?.[currentValue];
        previousValue[currentValue] = {
          title: translation?.title || '',
          content: translation?.content || '',
        };
        return previousValue;
      },
      {} as Record<string, TranslatedAttributes>,
    ),
  });

  const updateTranslation = (locale: string, field: keyof TranslatedAttributes, value: string) => {
    form.setData((previousData) => ({
      ...previousData,
      translations: {
        ...previousData.translations,
        [locale]: {
          ...previousData.translations[locale],
          [field]: value,
        },
      },
    }));
  };

  return (
    <BTForm
      onSubmit={(e) => {
        e.preventDefault();
        if (callback) {
          callback(form);
        }
      }}
    >
      <div className="d-flex flex-column gap-6">
        <FormGroup>
          <FormLabel aria-required={true} className="required">
            {t('slug')}
          </FormLabel>
          <FormControl
            placeholder={t('slug')}
            type="text"
            onChange={(e) => {
              form.setData('slug', e.currentTarget.value);
            }}
            defaultValue={form.data.slug}
          />
          <InputError message={form.errors.slug} />
        </FormGroup>

        <div>
          <div className="d-flex flex-wrap gap-2 mb-5" role="tablist" aria-label={t('translations')}>
            {localeKeys.map((locale) => (
              <button
                key={locale}
                type="button"
                role="tab"
                aria-selected={activeLocale === locale}
                className={clsx(
                  'btn btn-sm rounded-pill d-inline-flex align-items-center gap-2 px-4 py-2 fw-bold',
                  activeLocale === locale
                    ? 'btn-primary'
                    : 'btn-light text-gray-600 btn-active-light-primary',
                )}
                onClick={() => setActiveLocale(locale)}
              >
                {t(locale) !== locale ? t(locale) : locale.toUpperCase()}
              </button>
            ))}
          </div>

          <div role="tabpanel" data-testid={`page-locale-panel-${activeLocale}`}>
            <FormGroup className="mb-5">
              <FormLabel aria-required={true} className="required">
                {t(`title in ${activeLocale}`)}
              </FormLabel>
              <FormControl
                key={`title-${activeLocale}`}
                placeholder={t(`title in ${activeLocale}`)}
                type="text"
                onChange={(e) => {
                  updateTranslation(activeLocale, 'title', e.currentTarget.value);
                }}
                defaultValue={form.data.translations?.[activeLocale]?.title as string}
              />
              <InputError message={form.errors[`translations.${activeLocale}.title`]} />
            </FormGroup>

            <FormGroup>
              <FormLabel aria-required={true} className="required">
                {t('content in', { locale: activeLocale })}
              </FormLabel>
              <PageContentEditor
                key={`editor-${activeLocale}`}
                locale={activeLocale}
                value={(form.data.translations?.[activeLocale]?.content as string) || ''}
                placeholder={t('content in', { locale: activeLocale })}
                onChange={(html) => {
                  updateTranslation(activeLocale, 'content', html);
                }}
              />
              <InputError message={form.errors[`translations.${activeLocale}.content`]} />
            </FormGroup>
          </div>
        </div>

        <div className="d-flex gap-3 justify-content-end pt-2">
          <ActionButton type="submit" isProcessing={form.processing} text={t('save')} />
          <Link href={PageController.index().url} className="btn btn-light rounded-pill px-4">
            {t('cancel')}
          </Link>
        </div>
      </div>
    </BTForm>
  );
}
