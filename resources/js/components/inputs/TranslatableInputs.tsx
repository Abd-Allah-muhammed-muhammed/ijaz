import InputError from '@/components/inputs/InputError';
import { getSupportedLocales } from '@/hooks/use-locales';
import { Col, FormControl, FormGroup, FormLabel, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';

export type TranslatableBag = Record<string, Record<string, string | undefined> | undefined>;

export type TranslatableInputsProps = {
  field: string;
  values: TranslatableBag;
  errors: Record<string, string | undefined>;
  onChange: (locale: string, value: string) => void;
  locales?: string[];
  required?: boolean;
  as?: 'input' | 'textarea';
  rows?: number;
  labelForLocale?: (locale: string) => string;
  colProps?: { sm?: number; md?: number };
};

export default function TranslatableInputs({
  field,
  values,
  errors,
  onChange,
  locales: localesProp,
  required = true,
  as = 'input',
  rows = 3,
  labelForLocale,
  colProps = { sm: 12, md: 6 },
}: TranslatableInputsProps) {
  const { t } = useTranslation();
  const locales = localesProp ?? Object.keys(getSupportedLocales());
  const isTextarea = as === 'textarea';

  return (
    <Row>
      {locales.map((locale) => {
        const label = labelForLocale?.(locale) ?? t(`${field} in ${locale}`);

        return (
          <Col sm={colProps.sm} md={colProps.md} className="mb-3" key={locale}>
            <FormGroup>
              <FormLabel aria-required={required} className={required ? 'required' : undefined}>
                {label}
              </FormLabel>
              <FormControl
                placeholder={label}
                type={isTextarea ? 'textarea' : 'text'}
                as={isTextarea ? 'textarea' : undefined}
                rows={isTextarea ? rows : undefined}
                value={values[locale]?.[field] ?? ''}
                onChange={(e) => {
                  onChange(locale, e.currentTarget.value);
                }}
              />
              <InputError message={errors[`translations.${locale}.${field}`]} />
            </FormGroup>
          </Col>
        );
      })}
    </Row>
  );
}
