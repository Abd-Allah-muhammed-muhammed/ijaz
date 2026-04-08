import { useTranslation } from 'react-i18next';
import {City, Provider, ProviderType, ProviderTypeFileKeys, Region} from "@/types/models";
import {Button, Card, Col, Form as BTForm, FormControl, FormGroup, FormLabel, FormSelect, Row} from "react-bootstrap";
import {InertiaFormProps, Link, useForm} from "@inertiajs/react";
import {CategoryFormData} from "./types";
import ImageInput from "@/components/inputs/ImageInput";
import ActionButton from "@/components/action-button";
import {KTCard, KTIcon} from "@/_metronic/helpers";
import React, {useEffect, useState} from "react";
import {SelectCategoryModal} from "@/components/categories/category-selector/select-category-modal";
import InputError from "@/components/input-error";
import {FormInputs} from "@/pages/Dashboard/Providers/Validation";


type Props = {
  /**
   * The provider to be edited
   */
  row?: Provider
  /**
   * The provider types available
   */
  types: ProviderType[]
  /*
  * The cities available
  */
  cities: City[]
  /**
   * The regions available
   */
  regions: Region[]
  /** The URL to go back to the previous page */
  backUrl?: string
  /**
   * The callback function to be called when the form is submitted
   * @param form
   */
  callback?: (form: InertiaFormProps<FormInputs>, requiredFiles: Record<ProviderTypeFileKeys, boolean>) => void

};

export default function Form({callback, row, types, cities, regions, backUrl,}: Props) {
  const { t } = useTranslation();
  const [citiesData, setCitiesData] = useState<City[]>([]);
  const [showCateModal, setShowCateModal] = useState<boolean>(false);
  const [requiredFiles, setRequiredFiles] = useState(row?.provider_type?.files || {
    id_image: false,
    commercial_record: false,
    freelancer_certification: false,
    iban_certification: false,
  });
  const [selectingCategory, setSelectingCategory] = useState<CategoryFormData[]>(row?.categories?.map(c => {
    return {
      category: c,
      skills: c.provider_skills?.map(s => {
        return {
          value: s.id as string,
          label: s.title as string,
        }
      }) || [],
    };
  }) || []);
  const form = useForm<FormInputs>({
    provider_type_id: row?.provider_type_id || null,
    name: row?.name || null,
    email: row?.email || null,
    phone: row?.phone || null,
    iban: row?.iban || null,
    address: row?.address || null,
    region_id: row?.region_id || null,
    city_id: row?.city_id || null,
    password: null,
    password_confirmation: null,
    about: row?.about,
    categories: row?.categories?.map((c) => {
      return {
        id: c.id as number,
        skills: c.provider_skills?.map(s => s.id as number) || [],
      }
    }) || [],
    logo: undefined,
    id_image: undefined,
    commercial_record: undefined,
    freelancer_certification: undefined,
    iban_certification: undefined,
  });

  useEffect(() => {
    if (!form.data.region_id) {
      setCitiesData([]);
    }
    setCitiesData(cities.filter(city => city.region_id === form.data.region_id));
  }, [form.data.region_id]);
  return (
    <BTForm onSubmit={(e) => {
      e.preventDefault();
      if (callback) {
        callback(form, requiredFiles);
      }
    }}>
      <KTCard className='mb-5'>
        <Card.Header>
          <Card.Title>
            {t('general.information')}
          </Card.Title>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col sm={12} md={4} className="mb-3">
              <FormGroup className='d-flex flex-column'>
                <ImageInput
                  style={{
                    maxHeight: '350px',
                    aspectRatio: '1 / 1',
                    width: '100%',
                    borderRadius: '8px',
                    objectFit: 'contain',
                  }}
                  url={row?.logo}
                  callback={(data) => {
                    form.setData('logo', data.currentTarget.files![0]);
                  }}/>
                <InputError message={form.errors.logo}/>
              </FormGroup>

            </Col>
            <Col sm={12} md={8} className="mb-3">
              <Row>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('name')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('name')}
                      type='text'
                      onChange={(e) => {
                        form.setData('name', e.currentTarget.value);
                      }}
                      defaultValue={form.data.name || ''}
                    />
                    <InputError message={form.errors.name}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('email')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('email')}
                      type='email'
                      onChange={(e) => {
                        form.setData('email', e.currentTarget.value);
                      }}
                      defaultValue={form.data.email || ''}
                    />
                    <InputError message={form.errors.email}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('phone')}
                    </FormLabel>
                    <FormControl
                      placeholder={t('phone')}
                      className="form-control-solid"
                      type="tel"
                      onChange={(e) => {
                        form.setData('phone', e.currentTarget.value);
                      }}
                      defaultValue={form.data.phone || ''}
                    />
                    <InputError message={form.errors.phone}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormLabel className="required">
                    {t('iban')}
                  </FormLabel>
                  <FormGroup>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('iban')}
                      type='text'
                      onChange={(e) => {
                        form.setData('iban', e.currentTarget.value);
                      }}
                      defaultValue={form.data.iban || ''}
                    />
                    <InputError message={form.errors.iban}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('address')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('address')}
                      type='text'
                      onChange={(e) => {
                        form.setData('address', e.currentTarget.value);
                      }}
                      defaultValue={form.data.address || ''}
                    />
                    <InputError message={form.errors.address}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('provider_types')}
                    </FormLabel>
                    <FormSelect
                      className='form-select-solid'
                      defaultValue={form.data.provider_type_id || ''}
                      onChange={(e) => {
                        const val = parseInt(e.currentTarget.value) || null;
                        form.setData('provider_type_id', val);
                        if (val) {
                          setRequiredFiles(
                            types.find(t => t.id === val)?.files || {
                              id_image: false,
                              commercial_record: false,
                              freelancer_certification: false,
                              iban_certification: false,
                            }
                          );
                        } else {
                          setRequiredFiles({
                            id_image: false,
                            commercial_record: false,
                            freelancer_certification: false,
                            iban_certification: false,
                          });
                        }
                        setSelectingCategory([]);
                        form.setData('categories', []);
                      }}
                    >
                      <option>{t('choose')}</option>
                      {types.map((type) => (
                        <option key={`types-${type.id}`} value={type.id}>
                          {type.name}
                        </option>
                      ))}
                    </FormSelect>
                    <InputError message={form.errors.provider_type_id}/>
                  </FormGroup>
                </Col>

                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('region')}
                    </FormLabel>
                    <FormSelect
                      className='form-select-solid'
                      defaultValue={form.data.region_id || ''}
                      onChange={(e) => {
                        const val = parseInt(e.currentTarget.value);
                        form.setData('region_id', val || null);
                        form.setData('city_id', null);
                      }}>
                      <option>{t('choose')}</option>
                      {regions.map((r) => (
                        <option key={`region-${r.id}`} value={r.id}>
                          {r.title}
                        </option>
                      ))}
                    </FormSelect>
                    <InputError message={form.errors.region_id}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('city')}
                    </FormLabel>
                    <FormSelect
                      className="form-select-solid"
                      value={form.data.city_id || ''}
                      onChange={(e) => {
                        const val = parseInt(e.currentTarget.value);
                        form.setData('city_id', val);
                      }}
                    >
                      <option>{t('choose')}</option>
                      {citiesData.map((c) => (
                        <option key={`region-${c.id}`} value={c.id}>
                          {c.title}
                        </option>
                      ))}
                    </FormSelect>
                    <InputError message={form.errors.city_id}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('password')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('password')}
                      type='password'
                      onChange={(e) => {
                        form.setData('password', e.currentTarget.value);
                      }}
                      defaultValue={form.data.password || ''}
                    />
                    <InputError message={form.errors.password}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={4} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('password_confirmation')}
                    </FormLabel>
                    <FormControl
                      className="form-control-solid"
                      placeholder={t('password_confirmation')}
                      type='password'
                      onChange={(e) => {
                        form.setData('password_confirmation', e.currentTarget.value);
                      }}
                      defaultValue={form.data.password_confirmation || ''}
                    />
                    <InputError message={form.errors.password_confirmation}/>
                  </FormGroup>
                </Col>
                <Col sm={12} md={8} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t('about')}
                    </FormLabel>
                    <textarea
                      defaultValue={form.data.about || ''}
                      rows={1}
                      className='form-control form-control-solid'
                      onChange={(e) => {
                        form.setData('about', e.currentTarget.value as string);
                      }}
                      placeholder={t('about')}/>
                  </FormGroup>

                </Col>
              </Row>
            </Col>
          </Row>
        </Card.Body>
      </KTCard>

      <KTCard className='mb-5'>
        <Card.Header>
          <Card.Title className="d-flex flex-column">
            {t('categories & skills')}
            <InputError message={form.errors.categories}/>
          </Card.Title>
          <div className='card-toolbar'>
            <Button variant='primary' size='sm' onClick={() => setShowCateModal(true)} disabled={!(form.data.provider_type_id as unknown as boolean)}>
              {/*<PlusIcon/>*/}
              {t('add category & skills')}
            </Button>
            <SelectCategoryModal
              show={showCateModal}
              handleClose={() => {
                setShowCateModal(false)
              }}
              submitCallback={(data) => {
                setSelectingCategory((previousData) => {
                  let categories = previousData.filter((x) => x.category.id !== data.category!.id);
                  return [data as CategoryFormData, ...categories]
                })
                setShowCateModal(false)
                form.setData((previousData) => {
                  let categories = previousData.categories?.filter((x) => x.id !== data.category!.id);
                  return {
                    ...previousData,
                    categories: [{
                      id: data.category!.id as number,
                      skills: data.skills.map(s => parseInt(s.value))
                    }, ...categories!]
                  }
                });
              }}
              provider_type_id={form.data.provider_type_id as unknown as string}
            />
          </div>
        </Card.Header>
        <Card.Body>
          <Row>
            {selectingCategory.map($c => {
              return (
                <Col sm={12} key={`category-${$c.category.id}`}>
                  <div className='d-flex align-items-center justify-content-between mb-6'>
                    <span className='d-flex align-items-center me-2'>
                      <span className='symbol symbol-50px me-6'>
                        <span className='symbol-label bg-light-primary'>
                          {/*<KTIcon iconName='compass' className='fs-1 text-primary'/>*/}
                          <img src={$c.category.icon} alt={$c.category.title} className='w-100 h-100'/>
                        </span>
                      </span>

                      <span className='d-flex flex-column align-items-start'>
                        <span className='fw-bolder fs-6'>{$c.category.title}</span>
                        <span className='fs-7 d-flex gap-3 flex-wrap'>
                          {$c.skills.map(function (skill) {
                            return (
                              <span className='badge badge-primary' key={`skill-${skill.value}`}>
                                {skill.label}
                              </span>
                            )
                          })}
                        </span>
                      </span>
                    </span>
                    <span className='form-check form-check-custom form-check-solid'>
                        <button
                          className='btn btn-sm btn-danger'
                          onClick={() => {
                            setSelectingCategory((previousData) => {
                              let categories = previousData.filter((x) => x.category.id !== $c.category.id);
                              return [...categories]
                            })
                          }}
                        >
                          <KTIcon className='fs-1' iconName='cross'/>
                        </button>
                    </span>
                  </div>
                </Col>
              );
            })}
          </Row>
        </Card.Body>
      </KTCard>

      <KTCard className='mb-5'>
        <Card.Header>
          <Card.Title>
            {t('required files')}
          </Card.Title>
        </Card.Header>
        <Card.Body>
          <Row>
            {Object.entries(requiredFiles).filter(([key, value]) => value).map(([key]) => {
              const file = row?.media?.find((m) => m.collection_name === key as ProviderTypeFileKeys);
              return (
                <Col sm={12} md={4} key={`files-${key}`} className="mb-3">
                  <FormGroup>
                    <FormLabel className="required">
                      {t(key as ProviderTypeFileKeys)}
                    </FormLabel>
                    <FormControl
                      accept="application/pdf"
                      className="form-control-solid"
                      type='file'
                      onChange={(e) => {
                        form.setData(key as ProviderTypeFileKeys, (e.currentTarget as HTMLInputElement).files?.[0]);
                      }}
                    />
                    {file && (
                      <a href={file.url} target="_blank" rel="noopener noreferrer" className="mt-2 d-block">
                        <KTIcon iconName="document" className="fs-2 me-2"/>
                        {t('download existing file')}
                      </a>
                    )}
                    <InputError message={form.errors[key as ProviderTypeFileKeys]}/>
                  </FormGroup>
                </Col>
              )
            })}
          </Row>
        </Card.Body>
      </KTCard>
      <Row>
        <Col sm={12} className="mb-3 d-flex gap-3 justify-content-end">
          {backUrl && (
            <Link href={backUrl} className="btn btn-light">
              {t('cancel')}
            </Link>
          )}
          <ActionButton
            type="submit"
            isProcessing={form.processing}
            text={t('save')}
          />
        </Col>
      </Row>
    </BTForm>
  );
}
