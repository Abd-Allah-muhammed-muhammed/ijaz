import { KTCard, KTCardBody, KTIcon } from '@/_metronic/helpers';
import CarBrandController from '@/actions/App/Http/Controllers/Dashboard/CarBrandController';
import ActionButton from '@/components/action-button';
import ImageInput from '@/components/inputs/ImageInput';
import InputError from '@/components/inputs/InputError';
import { getSupportedLocales } from '@/hooks/use-locales';
import { CarBrand, CarBrandTranslation } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { Col, Form as BTForm, FormCheck, FormControl, FormGroup, FormLabel, Nav, Row, Tab } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';

export interface CarBrandForm {
  translations: Record<string, Partial<CarBrandTranslation>>;
  is_active: boolean;
  image?: File | null;
}

type Props = {
  carBrand?: CarBrand;
  onSubmit: (form: InertiaFormProps<CarBrandForm>) => void;
};

export default function Form({ carBrand, onSubmit }: Props) {
  const { t } = useTranslation();
  const locales = getSupportedLocales();
  const [activeTab, setActiveTab] = useState(Object.keys(locales)[0]);

  const form = useForm<CarBrandForm>({
    translations: Object.keys(locales).reduce<Record<string, Partial<CarBrandTranslation>>>(
      (previousValue, currentValue) => {
        const transObj = carBrand?.translations as Record<string, Partial<CarBrandTranslation>> | CarBrandTranslation[] | undefined;
        let translation: Partial<CarBrandTranslation> | undefined;

        if (Array.isArray(transObj)) {
          translation = transObj.find((t) => t.locale === currentValue);
        } else if (transObj) {
          translation = transObj[currentValue];
        }

        previousValue[currentValue] = {
          name: translation?.name || '',
          locale: currentValue,
        };
        return previousValue;
      },
      {},
    ),
    is_active: carBrand?.is_active ?? true,
    image: null,
  });

  const isTranslated = (locale: string) => form.data.translations[locale]?.name?.trim() !== '';

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <BTForm onSubmit={handleSubmit} className="form">
      <Row className="g-7 g-lg-10">
        {/* Sidebar (4 Columns) */}
        <Col md={4} className="d-flex flex-column gap-7 gap-lg-10">
          {/* Thumbnail Card */}
          <KTCard className="card-flush py-4 shadow-sm border-dashed">
            <div className="card-header">
              <div className="card-title">
                <h2 className="fw-bold">{t('thumbnail')}</h2>
              </div>
            </div>
            <KTCardBody className="text-center pt-0">
              <ImageInput
                url={carBrand?.image_url}
                callback={(e: React.ChangeEvent<HTMLInputElement>) => {
                  const file = e.target.files?.[0];
                  if (file) {
                    form.setData('image', file);
                  }
                }}
              />
              <div className="text-muted fs-7 mt-3">
                {t('set_the_car_brand_thumbnail_image')}
              </div>
              <InputError message={form.errors.image} className="mt-2" />
            </KTCardBody>
          </KTCard>

          {/* Status Card */}
          <KTCard className="card-flush py-4 shadow-sm border-dashed">
            <div className="card-header">
              <div className="card-title">
                <h2 className="fw-bold">{t('status')}</h2>
              </div>
              <div className="card-toolbar">
                <div className={`rounded-circle h-15px w-15px ${form.data.is_active ? 'bg-success' : 'bg-danger'}`}></div>
              </div>
            </div>
            <KTCardBody className="pt-0">
              <FormCheck
                type="switch"
                id="is_active_switch"
                label={form.data.is_active ? t('active') : t('inactive')}
                checked={form.data.is_active}
                onChange={(e) => form.setData('is_active', e.target.checked)}
                className="h-30px w-50px"
              />
            </KTCardBody>
          </KTCard>
        </Col>

        {/* Main Column Section (8 Columns) */}
        <Col md={8} className="d-flex flex-column gap-7 gap-lg-10">
          <Tab.Container id="translations-tabs" activeKey={activeTab} onSelect={(k) => setActiveTab(k || '')}>
            <KTCard className="card-flush py-4 shadow-sm">
              <div className="card-header align-items-center border-0 gap-2 gap-md-5">
                <div className="card-title">
                  <h2>
                    <KTIcon iconName="language" className="fs-1 me-2 text-primary" />
                    {t('general_information')}
                  </h2>
                </div>
                <div className="card-toolbar overflow-auto">
                  <Nav className="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                    {Object.keys(locales).map((locale) => (
                      <Nav.Item key={locale}>
                        <Nav.Link
                          eventKey={locale}
                          className={`text-active-primary py-4 ${activeTab === locale ? 'active' : ''}`}
                        >
                          <div className="d-flex align-items-center">
                            {t(locale)}
                            {isTranslated(locale) && (
                              <KTIcon iconName="check-circle" className="ms-2 fs-4 text-success" />
                            )}
                          </div>
                        </Nav.Link>
                      </Nav.Item>
                    ))}
                  </Nav>
                </div>
              </div>

              <KTCardBody className="pt-5">
                <Tab.Content>
                  {Object.keys(locales).map((locale) => (
                    <Tab.Pane eventKey={locale} key={locale} className="fade show">
                      <FormGroup>
                        <FormLabel className="required fw-bold fs-5 mb-4">
                          {t('name')} ({t(locale)})
                        </FormLabel>
                        <FormControl
                          placeholder={`${t('name')} (${t(locale)})`}
                          type="text"
                          size="lg"
                          className="form-control-solid fs-4 py-4"
                          onChange={(e) => {
                            const value = e.currentTarget.value;
                            form.setData((previousData) => ({
                              ...previousData,
                              translations: {
                                ...previousData.translations,
                                [locale]: {
                                  ...previousData.translations[locale],
                                  name: value,
                                  locale,
                                },
                              },
                            }));
                          }}
                          defaultValue={form.data.translations[locale]?.name}
                        />
                        <InputError message={form.errors[`translations.${locale}.name`]} />
                      </FormGroup>
                    </Tab.Pane>
                  ))}
                </Tab.Content>
              </KTCardBody>
            </KTCard>
          </Tab.Container>

          {/* Persist Actions Card */}
          <div className="d-flex justify-content-end align-items-center gap-3">
            <Link href={CarBrandController.index().url} className="btn btn-light-primary fw-bold px-8">
              {t('cancel')}
            </Link>
            <ActionButton
              isProcessing={form.processing}
              text={t('save')}
            />
          </div>
        </Col>
      </Row>
    </BTForm>
  );
}
