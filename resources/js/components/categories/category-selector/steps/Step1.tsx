import {useTranslation} from "react-i18next";
import React, {ReactNode, useEffect, useState} from "react";
import {Category} from "@/types/models";
import {useGetCategories} from "@/hooks/use-CategoryQuery";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faArrowLeft, faArrowRight, faHouse} from "@fortawesome/free-solid-svg-icons";
import {when, whenLocale} from "@/helpers/general";


type Props = {
  callback: (category: Category[]) => void
  isCurrent: boolean
  nextStep: () => void,
  provider_type_id?: string,
}


const Step1 = ({callback, isCurrent, nextStep, provider_type_id}: Props) => {
  const [selected, setSelected] = useState<Category | null>(null)
  const [parent, setParent] = useState<string>()
  const categories = useGetCategories(parent, undefined, provider_type_id)
  const [navbar, setNavbar] = useState<Category[]>([]);
  const [selectedCategories, setSelectedCategories] = useState<Map<string, Category>>(new Map());
  const {t} = useTranslation()

  useEffect(() => {
    if (parent) {
      categories.refetch().then(r => {
      })
    }
    setSelectedCategories(new Map())
  }, [parent]);


  return (
    <div className={`${isCurrent ? 'current' : ''}`} data-kt-stepper-element='content'>
      <div className='w-100'>
        {/*begin::Form Group */}
        {/*<div className='fv-row mb-10'>*/}
        {/*  <input*/}
        {/*    type='text'*/}
        {/*    className='form-control form-control-lg form-control-solid'*/}
        {/*    placeholder={t('search')}*/}
        {/*    value={searchTerm}*/}
        {/*    onChange={(e) => {*/}
        {/*      setSearchTerm(e.currentTarget.value)*/}
        {/*    }}*/}
        {/*  />*/}

        {/*</div>*/}
        <div className='fv-row'>
          <h3>{t('click_open_to_select_skills')}</h3>
          {/* begin::Label */}
          <label className='d-flex align-items-center fs-5 fw-semibold mb-4'>
            <button
              type='button'
              onClick={() => {
                setSelected(null)
                setNavbar([]);
                setParent(undefined)
              }}
              className='btn btn-sm btn-link'
            >
              <FontAwesomeIcon icon={faHouse} size={'xl'} className='text-primary'/>
            </button>
            <span className='mx-2'>/</span>
            {navbar.map(category => (
                <React.Fragment key={`navbar-category-${category.id}`}>
                  <button
                    type='button'
                    onClick={() => {
                      setSelected(null)
                      setNavbar(prev => {
                        const index = prev.findIndex(c => c.id === category.id);
                        if (index === -1) {
                          return [...prev, category];
                        }
                        return prev.slice(0, index + 1);
                      });
                      setParent(category.id.toString())

                    }}
                    className='btn btn-sm btn-link'
                  >
                    {category.title}
                  </button>
                  <span className='mx-2'>/</span>
                </React.Fragment>
              )
            )}
          </label>
          <div className="mt-10" style={{maxHeight: '400px', overflowY: 'auto'}}>
            {categories.data?.find(c => !c.has_children) && (
              <div className="d-flex align-items-center justify-content-between mb-6">
                <label htmlFor="select-all" className="d-flex align-items-center cursor-pointer">
                  <h3
                    className="fw-bolder m-0">{t('select-all')} ({categories.data?.filter(c => !c.has_children).length})</h3>
                </label>
                <div>
                  <span className='form-check form-check-custom form-check-solid'>
                  <input
                    id='select-all'
                    className='form-check-input'
                    type='checkbox'
                    checked={selectedCategories.size === categories.data?.filter(c => !c.has_children).length}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedCategories(new Map(categories.data?.filter(c => !c.has_children).map(c => [c.id.toString(), c])))
                      } else {
                        setSelectedCategories(new Map())
                      }
                    }}
                  />
                </span>

                </div>
              </div>
            )}
            <div>
              {categories.data?.map((category) => (
                <label
                  className='d-flex align-items-center justify-content-between mb-6 cursor-pointer'
                  key={`select-category-${category.id}-popup`}
                >
                  <span className='d-flex align-items-center me-2'>
                    <span className='symbol symbol-50px me-6'>
                      <span className='symbol-label bg-light-primary'>
                        {/*<KTIcon iconName='compass' className='fs-1 text-primary'/>*/}
                        <img src={category.icon} alt={category.title} className='w-100 h-100'/>
                      </span>
                    </span>

                    <span className='d-flex flex-column'>
                      <span className='fw-bolder fs-6'>{category.title}</span>
                      <span className='fs-7 text-muted'>
                        {/*Creating a clear text structure is just one SEO*/}
                      </span>
                    </span>
                  </span>
                  {
                    when<ReactNode>(
                      !category.has_children,
                      () => (
                        <span className='form-check form-check-custom form-check-solid'>
                          <span
                            className='badge badge-light-primary me-3 cursor-pointer px-5'
                          >
                            {t('select')}
                          </span>
                          <input
                            data-pan="select-category-step-1-checkbox"
                            className='form-check-input'
                            type='checkbox'
                            checked={selectedCategories.has(category.id.toString())}
                            onChange={() => {
                              setSelected(category)
                              setSelectedCategories(prev => {
                                if (!prev.has(category.id.toString())) {
                                  return new Map(prev).set(category.id.toString(), category);
                                }
                                return prev;
                              })
                            }}
                          />
                        </span>
                      ),
                      () => (
                        <button
                          data-pan="select-category-step-1-open-btn"
                          onClick={(e) => {
                            setParent(category.id.toString())
                            setNavbar((prev) => {
                              return [...prev, category];
                            });

                          }}
                          type='button'
                          className='btn btn-icon btn-primary btn-lg'
                          style={{
                            width: '80px',
                          }}
                        >
                          {/*<FontAwesomeIcon icon={faChevronRight} size={'lg'}/>*/}
                          {t('open')}
                        </button>
                      ))
                  }
                </label>
              ))}
            </div>
          </div>
        </div>
        <div className='d-flex flex-stack pt-10'>
          <div className='me-2'>
          </div>
          <div className='d-flex justify-content-between w-100'>
            <button
              disabled={!navbar.length || categories.isPending}
              type='button'
              className='btn btn-lg btn-light-primary me-3'
              onClick={() => {
                const lastIndex = navbar.length - 1;
                setNavbar((prev) => {
                  return [...prev.slice(0, lastIndex)];
                });
                setSelected(null)
                setParent(navbar[lastIndex - 1]?.id?.toString())
              }}
            >
              {/*<KTIcon iconName='arrow-left' className='fs-3 ms-1 me-0'/>*/}
              {
                whenLocale<ReactNode>(
                  'ar',
                  () => (
                    <FontAwesomeIcon icon={faArrowRight} size={'sm'} className='ms-0 me-2'/>
                  ),
                  () => (
                    <FontAwesomeIcon icon={faArrowLeft} size={'sm'} className='ms-0 me-2'/>

                  )
                )
              }
              {t('previous')}
            </button>
            <button
              data-pan="select-category-1-next"
              disabled={selectedCategories.size === 0 || categories.isPending}
              type='button'
              className='btn btn-lg btn-primary'
              onClick={() => {
                if (selectedCategories.size === 0) {
                  return;
                }
                callback(selectedCategories.values().toArray())
                nextStep()
              }}
            >
              {t('next')}
              {/*<KTIcon iconName='arrow-right' className='fs-3 ms-1 me-0'/>*/}
              {
                whenLocale<ReactNode>(
                  'ar',
                  () => (
                    <FontAwesomeIcon icon={faArrowLeft} size={'sm'} className='ms-2 me-0'/>
                  ),
                  () => (
                    <FontAwesomeIcon icon={faArrowRight} size={'sm'} className='ms-2 me-0'/>
                  )
                )
              }

            </button>
          </div>
        </div>
        {/*end::Form Group */}
      </div>
    </div>
  )
}

export {Step1}
