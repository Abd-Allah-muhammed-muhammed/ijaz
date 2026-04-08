import { Button, Col, Form, Nav, Row } from "react-bootstrap";
import { url, whenLocale } from "@/helpers/general";
import { City, ProviderType, Region } from "@/types/models";
import { Head, useForm } from "@inertiajs/react";
import React, { FormEvent, Fragment, ReactNode, useEffect, useState } from 'react';
import useSteps from "@/hooks/use-steps";
import { useTranslation } from 'react-i18next';
import ToastContainer from "@/components/toaster/toast-container";
import { toast } from 'sonner'
import { KTIcon } from "@/_metronic/helpers";
import './style.css'
// import {TreeSelect} from "antd";
import { availableSteps, CategoryOption, Inputs } from "./providerSchema";
// import SkillsSelect from "@/components/skills/skills-select";
// import {useGetCategory} from "@/hooks/use-CategoryQuery";
import ImageInput from "@/components/inputs/ImageInput";
import OTP from "@/components/inputs/OTP";
import InputError from "@/components/inputs/InputError";
import axios from '@/helpers/axios';
import AuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';
import ToastEffect from '@/components/toaster/toast-effect';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';
// import { CategoryFormData } from '@/pages/Dashboard/Providers/types';
import {
  Data as SelectCategoryModalData,
  SelectCategoryModal
} from '@/components/categories/category-selector/select-category-modal';
import { ProviderTypeFilesEnum } from "@/Enums/Enums";
import { AxiosError } from "axios";
import { faPlus } from "@fortawesome/free-solid-svg-icons/faPlus";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faArrowLeft,
  faArrowRight,
  faBell,
  faCircleCheck,
  faCommentDots,
  faRocket,
  faTrash
} from "@fortawesome/free-solid-svg-icons";
import ActionButton from "@/components/action-button";
import I18nextEffect from "@/lang/I18next-effect";

const MINUTES = 2;


const formatSeconds = (totalSeconds: number) => {

  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};
type Props = {
  types: ProviderType[]
  regions: Region[],
  cities: City[],
};

type RequiredFilesState = Record<typeof ProviderTypeFilesEnum[keyof typeof ProviderTypeFilesEnum], boolean>;
const Register_ = (
  {
    types,
    regions,
    cities,
  }: Props
) => {
  const form = useForm<Inputs>({
    provider_type_id: null,
    name: null,
    phone: null,
    email: null,
    iban: null,
    about: undefined,
    password: null,
    password_confirmation: null,
    address: null,
    region_id: null,
    city_id: null,
    otp: null,
    categories: [],
    id_image: undefined,
    commercial_record: undefined,
    iban_certification: undefined,
    freelancer_certification: undefined,
    logo: undefined,
  })
  const [seconds, setSeconds] = useState(0)
  const [citiesData, setCitiesData] = useState<City[]>([]);
  // const [category, setCategory] = useState<number>(0)
  // const [skills, setSkills] = useState<{ label: string, value: string }[]>([])
  const [categoriesOptions, setCategoriesOptions] = useState<Map<number, CategoryOption>>(new Map)
  // const categoryQuery = useGetCategory(category)
  const [requiredFiles, setRequiredFiles] = useState<RequiredFilesState>(Object.values(ProviderTypeFilesEnum).reduce((acc, file) => {
    acc[file] = false;
    return acc;
  }, {} as RequiredFilesState));
  const [showCateModal, setShowCateModal] = useState<boolean>(false);

  const [providerType, setProviderType] = useState<ProviderType | null>(null)
  const steps = useSteps({
    totalSteps: availableSteps.length,
  })
  const { t } = useTranslation();
  // const categoriesSelect = useMemo(() => {
  //   return <TreeSelect
  //     treeData={categories}
  //     onChange={(id) => {
  //       setCategory(id);
  //     }}
  //   />
  // }, [categories])

  // const addCategory = async () => {
  //   const {data} = await categoryQuery.refetch()
  //   if (!data) {
  //     toast.error('Category not found');
  //     return;
  //   }
  //   const newCategory = {
  //     id: data.id as number,
  //     title: data.title,
  //     icon: data.icon,
  //     skills
  //   };
  //   const $new = new Map(categoriesOptions).set(newCategory.id, newCategory);
  //   setCategoriesOptions($new);
  //   setSkills([]);
  //
  //   form.setData('categories', Array.from(
  //     $new.values().map(category => {
  //       return {
  //         id: category.id,
  //         skills: category.skills.map(s => parseInt(s.value as string))
  //       }
  //     })
  //   ))
  //
  // }


  const addCategory = (data: SelectCategoryModalData[]) => {
    const $new = new Map(categoriesOptions);
    data.forEach(c => {
      $new.set(c.category!.id as number, {
        id: c.category!.id as number,
        title: c.category!.title,
        icon: c.category!.icon,
        skills: c.skills
      })
    })
    setCategoriesOptions($new);
    // setSkills([]);

    form.setData('categories', Array.from(
      $new.values().map(category => {
        return {
          id: category.id,
          skills: category.skills.map(s => parseInt(s.value as string))
        }
      })
    ))

  }

  const sendOtp = async (): Promise<[any, Record<string, string[]> | null]> => {
    try {
      const response = await axios.post(AuthController.otp().url, {
        "phone": form.data.phone,
      })
      return [response.data, null];
    } catch (e: unknown) {
      if (e instanceof AxiosError) {
        if (e.status === 422) {
          return [null, e.response?.data.errors];
        }
      }

      return [null, {
        'error': [e as unknown as string]
      }];
    }

  }

  const handleSubmit = function (e: FormEvent<HTMLFormElement>) {
    e.preventDefault();

    form.post(AuthController.store().url, {
      onSuccess: (e) => {
        if (!e.props.flash?.error && Object.keys(e.props.errors || {}).length === 0) {
          steps.nextStep();
        }
      },
      onError: (res) => {
        Object.values(res).forEach(i => toast.error(i));
      }
    })
  }

  useEffect(() => {
    const interval = setInterval(() => {
      if (seconds <= 0) {
        clearInterval(interval);
      }
      setSeconds(prev => prev > 0 ? prev - 1 : 0);
    }, 1000);
    return () => {
      clearInterval(interval);
    }
  }, [seconds])

  return (
    <I18nextEffect>
    <div className="d-flex flex-column flex-root h-100" id="kt_app_root" data-pan="register-page">
      
      <ToastContainer />
      <ToastEffect />
      <Head title={t('register')} />
      <div
        className="d-flex flex-column flex-lg-row flex-column-fluid stepper stepper-pills stepper-column stepper-multistep"
      >
        <div className="d-flex flex-column flex-lg-row-auto w-lg-350px w-xl-500px">
          <div
            className="d-flex flex-column position-lg-fixed top-0 bottom-0 w-lg-350px w-xl-500px scroll-y bgi-size-cover bgi-position-center"
            style={{ backgroundImage: `url(${url('/media/misc/auth-bg.png')})` }}>
            <div className="d-flex flex-center py-10 py-lg-20 mt-lg-20 d-none d-lg-flex">
              <a href="/">
                <img alt="Logo" src={url("/media/logos/default.svg")} className="h-70px" />
              </a>
            </div>
            <div className="d-flex flex-row-fluid justify-content-center">
              {/* Desktop Navigation */}
              <Nav className="stepper-nav justify-content-center flex-column m-5 d-none d-lg-flex">
                {availableSteps.map((step, index) => {
                  const stepNumber = index + 1;
                  return (
                    <Nav.Item
                      key={'step-nav' + index}
                      className={`stepper-item ${steps.stepIs(stepNumber) ? 'current' : ''} ${steps.currentStep > stepNumber ? "completed" : ""}`}
                      data-kt-stepper-element="nav"
                    >
                      <div className="stepper-wrapper">
                        <div className="stepper-icon rounded-3">
                          <KTIcon iconName='check' className='ki-duotone ki-check fs-2 stepper-check' />
                          <span className="stepper-number">{stepNumber}</span>
                        </div>
                        <div className="stepper-label d-flex">
                          <h3 className="stepper-title fs-2">{step.title}</h3>
                          <div className="stepper-desc fw-normal">{step.description}</div>
                        </div>
                      </div>
                      {stepNumber < availableSteps.length && (<div className="stepper-line h-40px"></div>)}
                    </Nav.Item>
                  )
                })}
              </Nav>

              {/* Mobile Navigation */}
              <div className="d-flex d-lg-none flex-column w-100 px-2 py-3">
                {/* Mobile Steps Progress - Horizontal Scrollable */}
                <div className="mb-4">
                  <style>{`
                    .mobile-stepper-scroll::-webkit-scrollbar {
                      display: none;
                    }
                    .mobile-stepper-scroll {
                      -ms-overflow-style: none;
                      scrollbar-width: none;
                    }
                  `}</style>
                  <div className="d-flex align-items-center overflow-auto pb-2 mobile-stepper-scroll">
                    <div className="d-flex align-items-center" style={{ minWidth: 'max-content' }}>
                      {availableSteps.map((step, index) => {
                        const stepNumber = index + 1;
                        const isActive = steps.stepIs(stepNumber);
                        const isCompleted = steps.currentStep > stepNumber;
                        return (
                          <div key={'mobile-step-' + index} className="d-flex align-items-center flex-shrink-0">
                            <div className="d-flex flex-column align-items-center">
                              <div
                                className={`d-flex align-items-center justify-content-center rounded-circle position-relative ${isActive ? 'bg-primary text-white shadow-sm' :
                                  isCompleted ? 'bg-success text-white shadow-sm' :
                                    'bg-white bg-opacity-20 text-white border border-white border-opacity-50'
                                  }`}
                                style={{
                                  width: '30px',
                                  height: '30px',
                                  fontSize: '12px',
                                  fontWeight: 'bold',
                                  minWidth: '30px',
                                  transition: 'all 0.3s ease',
                                  transform: isActive ? 'scale(1.15)' : 'scale(1)'
                                }}
                              >
                                {isCompleted ? (
                                  <KTIcon iconName='check' className='ki-duotone ki-check fs-6 text-white' />
                                ) : (
                                  stepNumber
                                )}
                              </div>
                              <div className={`mt-2 px-2 text-center ${isActive ? 'text-white' : 'text-white-50'}`}
                                style={{ fontSize: '9px', lineHeight: '1.2', maxWidth: '60px' }}>
                                <div className="fw-bold">{step.title}</div>
                              </div>
                            </div>
                            {stepNumber < availableSteps.length && (
                              <div
                                className={`mx-3 rounded flex-shrink-0 ${isCompleted ? 'bg-success' : 'bg-white bg-opacity-20'}`}
                                style={{
                                  height: '2px',
                                  width: '20px',
                                  minWidth: '20px',
                                  transition: 'all 0.5s ease',
                                  marginTop: '-20px'
                                }}
                              ></div>
                            )}
                          </div>
                        )
                      })}
                    </div>
                  </div>
                </div>

                {/* Mobile Current Step Info - Compact */}
                <div className="text-center mb-3">
                  <div className="bg-white bg-opacity-10 rounded-3 px-3 py-2" style={{ backdropFilter: 'blur(10px)' }}>
                    <div className="text-white fw-semibold" style={{ fontSize: '14px' }}>
                      <span
                        className="opacity-75">خطوة {steps.currentStep} من {availableSteps.length}:</span> {availableSteps[steps.currentStep - 1]?.title}
                    </div>
                    <div className="text-white opacity-90 mt-1" style={{ fontSize: '12px' }}>
                      {availableSteps[steps.currentStep - 1]?.description}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="d-flex flex-column flex-lg-row-fluid">
          <div className="d-flex flex-center flex-column flex-column-fluid">
            <div className="w-lg-650px w-xl-700px p-3 p-md-4 p-lg-10 p-xl-15 mx-auto">
              <Form className="my-auto pb-3 pb-lg-5" noValidate id="kt_create_account_form" onSubmit={handleSubmit}>
                <div className={`${steps.stepIs(1) ? "current" : ''}`} data-kt-stepper-element="content">
                  <div className="w-100">
                    <div className="pb-10 pb-lg-15">
                      <h2 className="fw-bold d-flex align-items-center text-gray-900">
                        {t('select_account_type')}
                        <span
                          className="ms-1"
                          data-bs-toggle="tooltip"
                          title="Billing is issued based on your selected account typ">
                          <i className="ki-duotone ki-information-5 text-gray-500 fs-6">
                            <span className="path1"></span>
                            <span className="path2"></span>
                            <span className="path3"></span>
                          </i>
                        </span>
                      </h2>
                      <div className="text-muted fw-semibold fs-6">
                        {t('if_you_need_more_info,_please_check_out')}
                        <a href={GeneralController.index().url}
                          className="link-primary fw-bold mx-2">{t('help_page')}</a>.
                      </div>
                      <div>
                        <InputError message={form.errors.provider_type_id} />
                      </div>
                    </div>
                    <div className="fv-row" style={{}}>
                      {types.map(type => (
                        <Col lg={12} key={`type-${type.id}`} className="mb-6">
                          <input
                            type="radio"
                            className="btn-check"
                            checked={form.data.provider_type_id == type.id}
                            onChange={() => {
                              form.setData('provider_type_id', type.id as number);
                              const files = {} as RequiredFilesState;
                              Object.values(ProviderTypeFilesEnum).forEach((value) => {
                                files[value] = type.files[value] || false;
                              })
                              setRequiredFiles(files);
                              setProviderType(type)
                            }}
                            id={`type-${type.id}`}
                          />
                          <label
                            className="btn btn-outline btn-outline-dashed btn-active-light-primary p-7 d-flex align-items-start gap-3"
                            htmlFor={`type-${type.id}`}
                            style={{ height: '100%', cursor: 'pointer' }}
                          >
                            <img src={type.image} height={80} width={80} alt={type.name} />
                            <span className="d-block fw-semibold text-start">
                              <span className="text-gray-900 fw-bold d-block fs-4 mb-2">{type.name}</span>
                              <span className="fs-6">
                                <p style={{ lineBreak: "loose", maxHeight: '150px' }}
                                  className="m-0 overflow-y-auto">
                                  {type.description}
                                </p>
                              </span>
                            </span>
                          </label>
                        </Col>
                      ))}
                    </div>
                  </div>
                </div>
                <div className={steps.stepIs(2) ? "current" : ''} data-kt-stepper-element="content">
                  <div className="w-100">
                    <div className="pb-5">
                      <h2 className="fw-bold text-gray-900">{t('account_information')}</h2>
                      {/*<div className="text-muted fw-semibold fs-6">If you need more info, please check out*/}
                      {/*  <a href="#" className="link-primary fw-bold">Help Page</a>.*/}
                      {/*</div>*/}
                    </div>
                    <Row>
                      <Col sm={12} md={6}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="required form-label mb-3">{t('name')}</Form.Label>
                          <Form.Control
                            type="text"
                            placeholder={t('name')}
                            onChange={(event) => {
                              form.setData('name', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.name} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="required form-label mb-3">{t('phone')}</Form.Label>
                          <Form.Control
                            type="tel"
                            placeholder={t('phone')}
                            onChange={(event) => {
                              form.setData('phone', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.phone} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="form-label required mb-3">{t('email')}</Form.Label>
                          <Form.Control
                            type="email"
                            placeholder={t('email')}
                            onChange={(event) => {
                              form.setData('email', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.email} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="required form-label mb-3">{t('iban')}</Form.Label>
                          <Form.Control
                            type="text"
                            placeholder={t('iban')}
                            onChange={(event) => {
                              form.setData('iban', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.iban} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="required form-label mb-3">{t('password')}</Form.Label>
                          <Form.Control
                            type="password"
                            placeholder={t('password')}
                            onChange={(event) => {
                              form.setData('password', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.password} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="required form-label mb-3">{t('password_confirmation')}</Form.Label>
                          <Form.Control
                            type="password"
                            placeholder={t('password_confirmation')}
                            onChange={(event) => {
                              form.setData('password_confirmation', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.password_confirmation} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="fv-row mb-10">
                          <Form.Label className="form-label required">{t('region')}</Form.Label>
                          <Form.Select
                            defaultValue={form.data.region_id as unknown as string}
                            onChange={(e) => {
                              const val = parseInt(e.currentTarget.value);
                              setCitiesData(cities.filter(city => parseInt(city.region_id as unknown as string) === parseInt(e.currentTarget.value)));
                              form.setData('region_id', val || null);
                            }}>
                            <option>{t('choose')}</option>
                            {regions.map((r) => (
                              <option key={`region-${r.id}`} value={r.id}>
                                {r.title}
                              </option>
                            ))}
                          </Form.Select>
                          <InputError message={form.errors.region_id} />
                        </Form.Group>
                      </Col>
                      <Col sm={12} md={6}>
                        <Form.Group className="fv-row mb-10">
                          <Form.Label className="required">
                            {t('city')}
                          </Form.Label>
                          <Form.Select
                            defaultValue={form.data.city_id as unknown as string}
                            onChange={(e) => {
                              form.setData('city_id', e.currentTarget.value ? parseInt(e.currentTarget.value) : null);
                            }}
                          >
                            <option>{t('choose')}</option>
                            {citiesData.map((c) => (
                              <option key={`city-${c.id}`} value={c.id}>
                                {c.title}
                              </option>
                            ))}
                          </Form.Select>
                          <InputError message={form.errors.city_id} />
                        </Form.Group>
                      </Col>
                      <Col sm={12}>
                        <Form.Group className="fv-row mb-10">
                          <Form.Label className='required'>{t('address')}</Form.Label>
                          <Form.Control
                            type="text"
                            onChange={(event) => {
                              form.setData('address', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.address} />
                        </Form.Group>

                      </Col>
                      <Col sm={12}>
                        <Form.Group className="mb-10 fv-row">
                          <Form.Label className="required form-label mb-3">{t('about_you')}</Form.Label>
                          <Form.Control
                            as='textarea'
                            rows={3}
                            placeholder="مثال: أقدّم خدمات العامة ، إدخال بيانات، وإنجاز مهام متعددة"
                            onChange={(event) => {
                              form.setData('about', event.currentTarget.value);
                            }}
                          />
                          <InputError message={form.errors.about} />
                        </Form.Group>
                      </Col>
                    </Row>
                  </div>
                </div>
                <div className={steps.stepIs(3) ? "current" : ''} data-kt-stepper-element="content">
                  <div className="w-100">
                    {/*<div className="pb-5">*/}
                    {/*  <h2 className="fw-bold text-gray-900">{t('categories & skills')}</h2>*/}
                    {/*  /!*<div className="text-muted fw-semibold fs-6">If you need more info, please check out*!/*/}
                    {/*  /!*  <a href="#" className="text-primary fw-bold">Help Page</a>.*!/*/}
                    {/*  /!*</div>*!/*/}
                    {/*</div>*/}
                    {/*<Form.Group className="d-flex flex-column mb-7 fv-row">*/}
                    {/*  <Form.Label className="d-flex align-items-center fs-6 fw-semibold form-label mb-2">*/}
                    {/*    <span className="required">{t('category')}</span>*/}
                    {/*  </Form.Label>*/}
                    {/*  {categoriesSelect}*/}
                    {/*</Form.Group>*/}
                    {/*<Form.Group className="d-flex flex-column mb-7 fv-row">*/}
                    {/*  <Form.Label className="required fs-6 fw-semibold form-label mb-2">{t('skills')}</Form.Label>*/}
                    {/*  <div className="position-relative">*/}
                    {/*    <SkillsSelect categoryId={category} setValues={setSkills} values={skills}/>*/}
                    {/*  </div>*/}
                    {/*</Form.Group>*/}
                    <Row className='mb-7 fv-row'>
                      <Col sm={6}>
                        <h4 className='fw-bold text-gray-900'>{t('categories')}</h4>
                      </Col>
                      <Col sm={6} className='d-flex justify-content-end'>
                        <Button
                          variant='primary'
                          size='sm'
                          onClick={() => setShowCateModal(true)}
                          className="d-flex gap-5 align-items-center"
                          enterKeyHint="enter">
                          <FontAwesomeIcon icon={faPlus} className="fs-4" size={'sm'} />
                        </Button>
                        <SelectCategoryModal
                          show={showCateModal}
                          handleClose={() => {
                            setShowCateModal(false)
                          }}
                          submitCallback={(data) => {
                            addCategory(data);
                            setShowCateModal(false);
                          }}
                          provider_type_id={providerType?.id as string}
                        />
                        {/*<Button type='button' variant='secondary' className="text-primary" onClick={addCategory}>*/}
                        {/*  {t('add')}*/}
                        {/*  <i className="ki-duotone ki-plus fs-4 ">*/}
                        {/*    <span className="path1"></span>*/}
                        {/*    <span className="path2"></span>*/}
                        {/*  </i>*/}
                        {/*</Button>*/}
                      </Col>
                      <Col sm={12}>
                        <div className='mb-0 fv-row'>
                          <label className='d-flex align-items-center form-label mb-5'>
                            {/*
                              your account plane
                            */}
                            <i
                              className='fas fa-exclamation-circle ms-2 fs-7'
                              data-bs-toggle='tooltip'
                              title='Monthly billing will be based on your account plan'
                            ></i>
                          </label>
                          <InputError message={form.errors.categories} />

                          <div className='mb-0'>
                            {Array.from(categoriesOptions.values()).map((ca, index) => (
                              <div key={'category-container-' + ca.id} className='mb-5'>
                                <div className='d-flex flex-stack mb-5 cursor-pointer'>
                                  <span className='d-flex align-items-center me-2'>
                                    <span className='symbol symbol-50px me-6'>
                                      <span className='symbol-label'>
                                        <img src={ca.icon} className='h-50px align-self-center' alt={ca.title} />
                                      </span>
                                    </span>

                                    <span className='d-flex flex-column'>
                                      <span className='fw-bolder text-gray-800 text-hover-primary fs-5'>
                                        {ca.title}
                                      </span>
                                      <span className='fs-6 fw-bold text-gray-500'>
                                        {/*{ca.skills.map(skill => skill.label).join(', ')}*/}
                                      </span>
                                    </span>
                                  </span>
                                  <span className='form-check form-check-custom form-check-solid'>
                                    <Button
                                      variant={'danger'}
                                      size={'sm'}
                                      onClick={() => {
                                        form.setData('categories', form.data.categories.filter(c => c.id !== ca.id));
                                        setCategoriesOptions((prev) => {
                                          const newMap = new Map(prev);
                                          newMap.delete(ca.id);
                                          return newMap;
                                        });
                                      }}
                                    >
                                      <FontAwesomeIcon icon={faTrash} />
                                    </Button>
                                  </span>
                                </div>

                                {/*
                                  @ts-expect-error
                                  eslint-disable-next-line @typescript-eslint/ban-ts-comment
                                */}
                                <InputError message={form.errors?.[`categories.${index}`]} />
                                {/*
                                  @ts-expect-error
                                  eslint-disable-next-line @typescript-eslint/ban-ts-comment
                                */}
                                <InputError message={form.errors?.[`categories.${index}.skills`]} />
                              </div>

                            ))}
                          </div>
                        </div>
                      </Col>
                    </Row>

                  </div>
                </div>
                <div className={steps.stepIs(4) ? "current" : ''} data-kt-stepper-element="content">
                  <div className="w-100">
                    <div className="pb-8 pb-lg-10">
                      <h2 className="fw-bold text-gray-900">{t('files')}</h2>
                      {/*<div className="text-muted fw-semibold fs-6">If you need more info, please*/}
                      {/*  <a href="authentication/layouts/corporate/sign-in.html" className="link-primary fw-bold">Sign*/}
                      {/*    In</a>.*/}
                      {/*</div>*/}
                    </div>

                    <Form.Group className="mb-4">
                      <Form.Label className='required' htmlFor={'logo'}>
                        {t('logo')}
                      </Form.Label>
                      <div>
                        <ImageInput
                          id={`logo`}
                          style={{
                            maxHeight: '200px',
                          }}
                          callback={(e) => {
                            if (!e.currentTarget.files || e.currentTarget.files.length === 0) {
                              return;
                            }
                            form.setData('logo', e.currentTarget.files[0]);
                          }}
                        />
                      </div>
                      <InputError message={form.errors.logo} />
                    </Form.Group>
                    {

                      // @ts-expect-error ts(2322)
                      Object.keys(requiredFiles).filter(key => requiredFiles[key]).map((fileName, index) => (
                        <Fragment key={`file-${index}`}>
                          <Form.Group className="mb-10">
                            <Form.Label className="form-label required mb-3" htmlFor={`file-${fileName}`}>
                              {/*@ts-expect-error ts(2322) */}
                              {t(fileName)}
                            </Form.Label>
                            <input
                              className="form-control "
                              id={`file-${fileName}`}
                              type="file"
                              accept="application/pdf"
                              onChange={(event) => {
                                if (!event.currentTarget.files || event.currentTarget.files.length === 0) {
                                  return;
                                }
                                // @ts-expect-error ts(2322)
                                form.setData(fileName, event.currentTarget.files[0]);
                              }}
                            />
                            <InputError message={form.errors[fileName as keyof Inputs]} />
                          </Form.Group>
                        </Fragment>
                      ))
                    }
                  </div>
                </div>
                <div className={steps.stepIs(5) ? "current" : ''} data-kt-stepper-element="content">
                  <div className="w-100">
                    <div className="pb-8 pb-lg-10">
                      <h2 className="fw-bold text-gray-900">{t('summary')}</h2>
                      {/*<div className="text-muted fw-semibold fs-6">If you need more info, please*/}
                      {/*  <a href="authentication/layouts/corporate/sign-in.html" className="link-primary fw-bold">Sign*/}
                      {/*    In</a>.*/}
                      {/*</div>*/}
                    </div>
                    <div className="mb-0">
                      <Row>
                        {/*<Col sm={12} md={2}>*/}
                        {/*  {form.data.logo &&*/}
                        {/*    <img className='img-fluid' src={URL.createObjectURL(form.data.logo as File)} alt='logo'/>}*/}
                        {/*</Col>*/}
                        <Col sm={12} md={12}>
                          <div className='d-flex flex-column flex-md-row'>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('name')}</span>
                              <span className="text-gray-600">{form.data.name}</span>
                            </div>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('phone')}</span>
                              <span className="text-gray-600">{form.data.phone}</span>
                            </div>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('email')}</span>
                              <span className="text-gray-600">{form.data.email}</span>
                            </div>
                          </div>
                          <div className='d-flex flex-column flex-md-row'>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('provider_type')}</span>
                              <span className="text-gray-600">{providerType?.name}</span>
                            </div>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('iban')}</span>
                              <span className="text-gray-600">{form.data.iban}</span>
                            </div>

                          </div>
                          <div className='d-flex'>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('about')}</span>
                              <p
                                className="text-gray-600 form-control bg-transparent h-auto min-h-100px">{form.data.about}</p>
                            </div>
                          </div>
                        </Col>
                        <Col>
                          {/*<div className='d-flex flex-column flex-md-row'>*/}
                          {/*  <div className="col d-flex flex-column mb-4 mb-md-5">*/}
                          {/*    <span className="fw-bold text-gray-800">{t('region')}</span>*/}
                          {/*    <span*/}
                          {/*      className="text-gray-600">{regions.find(r => parseInt(r.id as string) === form.data.region_id)?.title}</span>*/}
                          {/*  </div>*/}
                          {/*  <div className="col d-flex flex-column mb-4 mb-md-5">*/}
                          {/*    <span className="fw-bold text-gray-800">{t('city')}</span>*/}
                          {/*    <span*/}
                          {/*      className="text-gray-600">{cities.find(c => parseInt(c.id as string) === form.data.city_id)?.title}</span>*/}
                          {/*  </div>*/}

                          {/*</div>*/}
                          <div className='d-flex'>
                            <div className="col d-flex flex-column mb-4 mb-md-5">
                              <span className="fw-bold text-gray-800">{t('address')}</span>
                              <p className="text-gray-600 form-control bg-transparent h-auto">{form.data.address}</p>
                            </div>
                          </div>
                          <Row>
                            <Col sm={12} className='mb-10'>
                              <span className="fw-bold text-gray-800">{t('categories')}</span>
                            </Col>
                            {Array.from(categoriesOptions.values()).map((ca) => (
                              <Col sm={12} md={6} className='d-flex flex-stack mb-5 cursor-pointer'
                                key={`category-${ca.id}`}>
                                <span className='d-flex align-items-center me-2'>
                                  <span className='symbol symbol-50px me-6'>
                                    <span className='symbol-label'>
                                      <img src={ca.icon} className='h-50px align-self-center' alt={ca.title} />
                                    </span>
                                  </span>

                                  <span className='d-flex flex-column'>
                                    <span className='fw-bolder text-gray-800 text-hover-primary fs-5'>
                                      {ca.title}
                                    </span>
                                    <span className='fs-6 fw-bold text-gray-500'>
                                      {/*{ca.skills.map(skill => skill.label).join(', ')}*/}
                                    </span>
                                  </span>
                                </span>
                              </Col>
                            ))}
                          </Row>
                        </Col>
                      </Row>

                    </div>
                  </div>
                </div>
                <div className={steps.stepIs(6) ? "current" : ''} data-kt-stepper-element="content">
                  <div className="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div className="p-10">
                      <div className="form w-100 mb-13">
                        <div className="text-center mb-10">
                          <img alt="Logo" className="mh-125px" src={url('media/svg/misc/smartphone-2.svg')} />
                        </div>
                        <div className="text-center mb-10">
                          <div
                            className="text-muted fw-semibold fs-5 mb-5">{t('enter_the_verification_code_we_sent_to')}</div>
                          <div className="fw-bold text-gray-900 fs-3">{form.data.phone}</div>
                        </div>
                        <div className="mb-10">
                          <OTP type={'number'} onChange={(value) => {
                            form.setData('otp', value);
                          }} />
                        </div>
                        <InputError message={form.errors.otp} />
                      </div>
                      <div className="text-center d-flex align-items-center gap-5 fw-semibold fs-5">
                        <span className="text-muted me-1">{t('didnt_get_the_code_?')}</span>
                        <a
                          href="#"
                          //disabled={form.processing || seconds > 0}
                          className="link-primary fs-5"
                          onClick={async (e) => {
                            e.preventDefault();
                            if (form.processing || seconds > 0) {
                              return;
                            }
                            const [_, errors] = await sendOtp();
                            if (errors) {
                              // @ts-expect-error ts(2345)
                              form.setError(errors)
                              Object.values(errors).forEach((r) => {
                                toast.error(r[0])
                              });
                              return
                            }
                            setSeconds(60 * MINUTES);
                          }}>
                          {seconds > 0 ?
                            (<>
                              <span className="text-muted me-1">{t('resend_code_in')} </span>
                              <span className="fw-bold">{formatSeconds(seconds)}</span>
                            </>)
                            : t('resend')
                          }

                        </a>
                      </div>
                    </div>
                  </div>
                </div>
                <div className={steps.stepIs(7) ? "current" : ''} data-kt-stepper-element="content">
                  <div className="w-100">
                    {/*<div className="">*/}
                    {/*  <h2 className="fw-bold text-gray-900">{t('you_are_done!')}</h2>*/}
                    {/*</div>*/}
                    <div className="mb-0">
                      {/* Success Registration Card */}
                      <div className="bg-white rounded-3 shadow-sm border border-light-subtle p-8 mb-6">
                        {/* Header Section with Icon */}
                        <div className="text-center mb-8">
                          <div
                            className="bg-gradient-to-br from-emerald-100 to-teal-100 rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                          // style={{width: '80px', height: '80px'}}
                          >
                            <FontAwesomeIcon icon={faCircleCheck} size="2xl" className='text-success' style={{
                              fontSize: '10rem',
                            }} />
                          </div>
                        </div>

                        {/* Welcome Message Section */}
                        <div className="welcome-message mb-8">
                          <div className="text-center mb-6">
                            <h2 className="fs-2 fw-bold text-gray-900 mb-4">🎉 {t('completed')}</h2>
                          </div>

                          <div
                            className="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2 p-6 mb-6 border border-emerald-200">
                            <div className="registration-content text-center">
                              <div className="fs-5 text-gray-800 lh-lg fw-medium"
                                style={{
                                  textAlign: 'center',
                                }}
                                dangerouslySetInnerHTML={{
                                  __html: t('end_register')
                                    .replace(new RegExp('\r?\n', 'g'), '<br/>')
                                    .replace(/💚/g, '<span class="text-success fs-4">💚</span>')
                                    .replace(/🔔/g, '<span class="text-primary fs-4">🔔</span>')
                                    .replace(/🎯/g, '<span class="text-warning fs-4">🎯</span>')
                                    .replace(/🚀/g, '<span class="text-info fs-4">🚀</span>')
                                    .replace(/🎊/g, '<span class="text-success fs-4">🎊</span>')
                                    .replace(/💪/g, '<span class="text-primary fs-4">💪</span>')
                                    .replace(/🇸🇦/g, '<span class="text-success fs-4">🇸🇦</span>')
                                }}>
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Registration Summary Section */}
                        <div className="registration-summary">
                          <div className="bg-light-primary rounded-2 p-6 border border-primary-subtle">
                            <div className="summary-content">
                              <div className="fs-6 text-gray-800 lh-lg"
                                style={{
                                  textAlign: 'center',
                                }}
                                dangerouslySetInnerHTML={{
                                  __html: t('registration_summary', {
                                    created_at: new Date().toLocaleDateString(),
                                    phone: form.data.phone as string,
                                    account_type: providerType?.name || '',
                                    order_id: ""
                                  })
                                    .replace(new RegExp('\r?\n', 'g'), '<br/>')
                                    .replace(/🧾/g, '<span class="text-primary fs-4">🧾</span>')
                                    .replace(/✅/g, '<span class="text-success fs-4">✅</span>')
                                    .replace(/✨/g, '<span class="text-warning fs-4">✨</span>')
                                }}>
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Next Steps Visual Guide */}
                        <div className="next-steps mt-8">
                          <div
                            className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2 p-6 border border-blue-200">
                            <div className="row g-4">
                              <div className="col-12 col-md-4">
                                <div className="text-center">
                                  <div
                                    className="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style={{ width: '50px', height: '50px' }}
                                  >

                                    {/*<i className="ki-duotone ki-notification fs-2 text-primary">*/}
                                    {/*  <span className="path1"></span>*/}
                                    {/*  <span className="path2"></span>*/}
                                    {/*  <span className="path3"></span>*/}
                                    {/*</i>*/}
                                    <FontAwesomeIcon icon={faBell} size="2x" className='text-primary' />
                                  </div>
                                  <div className="fs-7 fw-semibold text-gray-700">انتظار الإشعار</div>
                                </div>
                              </div>
                              <div className="col-12 col-md-4">
                                <div className="text-center">
                                  <div
                                    className="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style={{ width: '50px', height: '50px' }}>
                                    <FontAwesomeIcon size="2x" icon={faCommentDots} className="text-success" />
                                  </div>
                                  <div className="fs-7 fw-semibold text-gray-700">تابع الإشعارات</div>
                                </div>
                              </div>
                              <div className="col-12 col-md-4">
                                <div className="text-center">
                                  <div
                                    className="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style={{ width: '50px', height: '50px' }}>


                                    <FontAwesomeIcon size="2x" icon={faRocket} className="text-warning" />
                                  </div>
                                  <div className="fs-7 fw-semibold text-gray-700">جهز خدماتك</div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="d-flex flex-column flex-md-row justify-content-between pt-8 pt-md-15 gap-3">
                  <div className="order-2 order-md-1">
                    {steps.stepBetween(2, availableSteps.length - 1) && (
                      <Button
                        data-pan={`register-step-${steps.currentStep}-previous-button`}
                        onClick={steps.prevStep}
                        type="button"
                        variant="light-primary"
                        className="w-100 w-md-auto"
                      >
                        {
                          whenLocale<ReactNode>(
                            'ar',
                            () => (
                              <FontAwesomeIcon icon={faArrowRight} size={'sm'} className='ms-0 me-2' />
                            ),
                            () => (
                              <FontAwesomeIcon icon={faArrowLeft} size={'sm'} className='ms-0 me-2' />

                            )
                          )
                        }
                        {t('previous')}
                      </Button>
                    )}
                  </div>
                  <div className="order-1 order-md-2">
                    {steps.stepIs(availableSteps.length - 1) && (
                      <ActionButton
                        isProcessing={form.processing}
                        type="submit"
                        // className="w-100 w-md-auto"
                        data-pan={`register-step-${steps.currentStep}-submit-button`}
                        text={
                          <>
                            {t('submit')}
                            {
                              whenLocale<ReactNode>(
                                'ar',
                                () => (
                                  <FontAwesomeIcon icon={faArrowLeft} size={'sm'} className='ms-2 me-0' />
                                ),
                                () => (
                                  <FontAwesomeIcon icon={faArrowRight} size={'sm'} className='ms-2 me-0' />
                                )
                              )
                            }
                          </>
                        }
                      />
                      // <Button
                      //   type="submit"
                      //   variant="primary"
                      //   disabled={form.processing}
                      //   className="w-100 w-md-auto"
                      // >
                      //   {form.processing ?
                      //     <span className='indicator-progress' style={{display: 'block'}}>
                      //     {t('please_wait...')}
                      //       <span className='spinner-border spinner-border-sm align-middle ms-2'></span>
                      //   </span>
                      //     :
                      //     <span className="indicator-label">
                      //     {t('submit')}
                      //       <i className="ki-duotone ki-arrow-right fs-4 ms-2">
                      //       <span className="path1"></span>
                      //       <span className="path2"></span>
                      //     </i>
                      //   </span>
                      //   }
                      // </Button>
                    )}
                    {steps.stepBetween(1, availableSteps.length - 2) && (
                      <Button
                        type="button"
                        variant="primary"
                        className="w-100 w-md-auto"
                        data-pan={`register-step-${steps.currentStep}-next-button`}
                        onClick={async () => {
                          form.clearErrors()
                          const currentStep = availableSteps[steps.currentStep - 1];
                          if (!currentStep) {
                            console.error('Current step is not defined');
                            return;
                          }
                          if (steps.stepIs(availableSteps.length - 2)) {
                            const [_, errors] = await sendOtp();
                            if (errors) {
                              // @ts-expect-error ts(2345)
                              form.setError(errors)
                              Object.values(errors).forEach((e) => {
                                toast.error(e[0])
                              })
                              return;
                            }
                            setSeconds(60 * MINUTES);
                          }
                          if (!currentStep.rules) {
                            steps.nextStep()
                            return;
                          }
                          const data = {
                            ...form.data,
                            requiredFiles
                          };
                          const validation = currentStep.rules.safeParse(data)
                          if (validation.success) {
                            steps.nextStep()
                          } else {
                            validation.error.issues.forEach(issue => {
                              form.setError(issue.path.join('.') as keyof Inputs, issue.message)
                            })
                          }

                        }}>
                        {t('next')}
                        {
                          whenLocale<ReactNode>(
                            'ar',
                            () => (
                              <FontAwesomeIcon icon={faArrowLeft} size={'sm'} className='ms-2 me-0' />
                            ),
                            () => (
                              <FontAwesomeIcon icon={faArrowRight} size={'sm'} className='ms-2 me-0' />
                            )
                          )
                        }
                      </Button>
                    )}

                  </div>
                </div>
                <div className={`d-flex justify-content-center mt-15 ${steps.isLastStep() ? "" : "d-none"}`}>
                  <a href={GeneralController.index().url} className="btn btn-primary btn-lg">
                    {t('return_to_home_page')}
                  </a>
                </div>
              </Form>
            </div>
          </div>
        </div>
      </div>
    </div>
    </I18nextEffect>
  );  
}

export default Register_
