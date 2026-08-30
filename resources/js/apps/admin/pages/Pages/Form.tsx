import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Page } from '@/shared/types/models';
import { Form as BTForm, FormControl, FormGroup, FormLabel, FormText } from 'react-bootstrap';
import { InertiaFormProps, Link, useForm } from '@inertiajs/react';
import { TranslatedAttributes } from './types';
import ActionButton from '@/shared/components/action-button';
import { getSupportedLocales } from '@/shared/hooks/use-locales';
import InputError from '@/shared/components/inputs/InputError';
import { Inputs } from '@/apps/admin/pages/Pages/validation';
import PageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController';
import PageContentEditor from './PageContentEditor';
import clsx from 'clsx';
import Select, { MultiValue } from 'react-select';

type PageOption = {
  value: string;
  label: string;
};

type Props = {
  row?: Page;
  pageOptions?: PageOption[];
  callback?: (form: InertiaFormProps<Inputs>) => void;
};

export default function Form({ callback, row, pageOptions = [] }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();
  const localeKeys = Object.keys(locales);
  const [activeLocale, setActiveLocale] = useState(localeKeys[0] ?? 'en');

  const form = useForm<Inputs>({
    slug: row?.slug || '',
    composed_of_slugs: row?.composed_of_slugs ?? [],
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

  const selectedComposed = useMemo(() => {
    const bySlug = new Map(pageOptions.map((option) => [option.value, option]));
    return (form.data.composed_of_slugs ?? [])
      .map((slug) => bySlug.get(slug))
      .filter((option): option is PageOption => option !== undefined);
  }, [form.data.composed_of_slugs, pageOptions]);

  const isComposed = (form.data.composed_of_slugs?.length ?? 0) > 0;

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

        <FormGroup data-testid="pages-composed-of-field">
          <FormLabel>{t('composed of')}</FormLabel>
          <Select
            isMulti
            options={pageOptions}
            value={selectedComposed}
            placeholder={t('composed of')}
            classNamePrefix="pages-composed-of"
            onChange={(values: MultiValue<PageOption>) => {
              // Selection order is preserved by react-select multi.
              form.setData(
                'composed_of_slugs',
                values.map((option) => option.value),
              );
            }}
          />
          <FormText className="text-muted" data-testid="pages-composed-of-help">
            When set, this page ignores its own content and instead renders each selected page as
            its own badge/card, stacked in the order selected. Leave content blank for composed
            pages.
          </FormText>
          <InputError message={form.errors.composed_of_slugs} />
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
              <FormLabel aria-required={!isComposed} className={clsx(!isComposed && 'required')}>
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
