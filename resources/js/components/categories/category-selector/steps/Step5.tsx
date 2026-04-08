import {KTIcon} from '@/_metronic/helpers'
import {Category, Skill} from "@/types/models";
import React, {ReactNode} from "react";
import {useTranslation} from "react-i18next";
import {SelectOption} from "@/types";
import {whenLocale} from "@/helpers/general";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faArrowLeft, faArrowRight} from "@fortawesome/free-solid-svg-icons";

type  Data = {
  parents?: Category[]
  category: Category | null
  skills: SelectOption[]
}

type Props = {
  callback: (data :Data[]) => void
  prevStep?: () => void
  data: Data[];
  isCurrent: boolean
}


const Step5 = (
  {
    callback,
    prevStep,
    isCurrent,
    data
  }
  : Props) => {
    const {t} = useTranslation()
  return (
    <div data-kt-stepper-element='content' className={`pb-5 ${isCurrent ? 'current' : ''}`}>
      <div className='w-100 text-center'>
        <div className="fv-row">
          {data.map(c=>(
            <label className='d-flex align-items-center justify-content-between mb-6 cursor-pointer' key={'step5-category-'+c.category?.id}>
            <span className='d-flex align-items-center me-2'>
              <span className='symbol symbol-50px me-6'>
                <span className='symbol-label bg-light-primary'>
                  {/*<KTIcon iconName='compass' className='fs-1 text-primary'/>*/}
                  <img src={c.category?.icon} alt={c.category?.title} className='w-100 h-100'/>
                </span>
              </span>

              <span className='d-flex flex-column align-items-start'>
                <span className='fw-bolder fs-6'>{c.category?.title}</span>
                <span className='fs-7 text-muted d-flex gap-1'>
                  {c.skills.map(function (skill, index) {
                    return (
                      <span className='badge badge-primary' key={`skill-${skill.value}`}>
                        {skill.label}
                      </span>
                    )
                  })}
                </span>
              </span>
            </span>

              {/*<span className='form-check form-check-custom form-check-solid'>*/}
              {/*      <input*/}
              {/*        className='form-check-input'*/}
              {/*        type='radio'*/}
              {/*        checked={selected?.id === category.id}*/}
              {/*        onChange={() => {*/}
              {/*          setSelected(category)*/}
              {/*          if (category.has_children) {*/}
              {/*            setNavbar((prev) => {*/}
              {/*              return [...prev, category];*/}
              {/*            });*/}
              {/*          }*/}
              {/*        }}*/}
              {/*      />*/}
              {/*    </span>*/}
            </label>
          ))}
        </div>
        <div className='d-flex flex-stack pt-10'>
          <div className='me-2'>
            <button
              type='button'
              className='btn btn-lg btn-light-primary me-3'
              onClick={prevStep}
            >
              {/*<KTIcon iconName='arrow-left' className='fs-3 me-1'/>*/}

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
          </div>
          <div>
            <button
              data-pan="select-category-step-1-submit-btn"
              type='button'
              className='btn btn-lg btn-primary'
              onClick={() => {
                if (callback){
                  callback(data)
                }
              }}
            >
              {t('submit')}
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
      </div>
    </div>
  )
}

export {Step5}
