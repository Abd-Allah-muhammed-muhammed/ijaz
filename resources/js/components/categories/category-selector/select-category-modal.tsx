import { useState } from 'react'
import { createPortal } from 'react-dom'
import { Modal } from 'react-bootstrap'
import { Step1 } from './steps/Step1'
import { Step5 } from './steps/Step5'
import { useTranslation } from "react-i18next";
import useSteps from "@/hooks/use-steps";
import { Category } from "@/types/models";
import { SelectOption } from "@/types";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome"
import { faXmark } from "@fortawesome/free-solid-svg-icons/faXmark";

type Props = {
  show: boolean
  handleClose: () => void
  submitCallback: (data: Data[]) => void,
  provider_type_id?: string,
}

export type Data = {
  category: Category | null
  skills: SelectOption[]
}

const modalsRoot = document.getElementById('root-modals') || document.body

export const SelectCategoryModal = (
  {
    show,
    handleClose,
    submitCallback,
    provider_type_id
  }: Props) => {
  const { nextStep, prevStep, stepIs, isBetweenStep, goToStep } = useSteps({
    totalSteps: 2,
  });




  const [data, setData] = useState<Data[]>([]);
  const { t } = useTranslation();
  return createPortal(
    <Modal
      tabIndex={-1}
      aria-hidden='false'
      dialogClassName='modal-dialog modal-dialog-centered mw-900px'
      show={show}
      onHide={() => {
        if (handleClose) {
          handleClose();
        }
        goToStep(1)
      }}
      onExit={() => {
        if (handleClose) {
          handleClose();
        }
        goToStep(1)
      }}
      backdrop={true}
    >
      <div className='modal-header'>
        <h2>{t('choose_selected_categories')}</h2>
        <button className='btn btn-sm btn-icon btn-active-color-danger' onClick={handleClose}>
          <FontAwesomeIcon icon={faXmark} size="2xl" />
        </button>
      </div>
      <div
        className={`modal-body py-lg-10 px-lg-10 ${stepIs(1) ? "first" : ""} ${stepIs(2) ? "last" : ""} ${isBetweenStep() ? "between" : ""} `}>
        <div
          // ref={stepperRef}
          className='stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid'
          id='kt_modal_create_app_stepper'
        >

          {/*begin::Content */}
          <div className='flex-row-fluid py-lg-5 px-lg-15'>
            {/*begin::Form */}
            <form noValidate id='kt_modal_create_app_form'>
              <Step1
                isCurrent={stepIs(1)}
                nextStep={nextStep}
                callback={(categories) => {
                  setData(prev => {
                    return [...categories.map(c => ({ category: c, skills: [] }))];
                  })
                }}
                provider_type_id={provider_type_id}
              />
              {/*<Step2*/}
              {/*  isCurrent={stepIs(2)}*/}
              {/*  state={data}*/}
              {/*  nextStep={nextStep}*/}
              {/*  prevStep={prevStep}*/}
              {/*  callback={($v) => {*/}
              {/*    setData((prev) => {*/}
              {/*      return {*/}
              {/*        ...prev,*/}
              {/*        skills: $v*/}
              {/*      }*/}
              {/*    })*/}
              {/*  }}*/}
              {/*/>*/}
              <Step5
                isCurrent={stepIs(2)}
                prevStep={prevStep}
                callback={submitCallback}
                data={data}
              />

            </form>
            {/*end::Form */}
          </div>
          {/*end::Content */}
        </div>
        {/* end::Stepper */}
      </div>
    </Modal>,
    modalsRoot
  )
}
