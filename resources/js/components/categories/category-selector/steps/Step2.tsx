import {Category} from "@/types/models";
import {useEffect, useState} from "react";
import {usePage} from "@inertiajs/react";
import {trans} from "@/hooks/use-translation";
import {KTIcon} from "@/_metronic/helpers";
import useGetSkills from "@/hooks/use-getSkills";
import {SelectOption} from "@/types";

type Props = {
  callback: (skills: SelectOption[]) => void
  state: {
    category: Category | null
  },
  isCurrent: boolean
  nextStep: () => void
  prevStep: () => void
}


const Step2 = ({callback, state, isCurrent, nextStep, prevStep}: Props) => {
  const [selected, setSelected] = useState<SelectOption[]>([])
  const skills = useGetSkills(state.category?.id as number)

  useEffect(function () {
    if (state.category) {
      skills.refetch()
    }

  }, [state.category])

  return (
    <div className={`pb-5 ${isCurrent ? 'current' : ''}`} data-kt-stepper-element='content'>
      <div className='w-100'>
        {/*begin::Form Group */}
        <div className='fv-row'>
          {/* begin::Label */}
          <label className='d-flex align-items-center fs-5 fw-semibold mb-4'>
            <span className='required'>{trans('skills')}</span>
            <i
              className='fas fa-exclamation-circle ms-2 fs-7'
              data-bs-toggle='tooltip'
              title='Specify your apps framework'
            ></i>
          </label>
          {skills.data?.map((skill) => (
            <label
              className='d-flex align-items-center justify-content-between cursor-pointer mb-6'
              key={`select-skill-${skill.value}`}
            >
            <span className='d-flex align-items-center me-2'>
              {/*<span className='symbol symbol-50px me-6'>*/}
              {/*<span className='symbol-label bg-light-warning'>*/}
              {/*<img*/}
              {/*  src={skill.icon}*/}
              {/*  className='h-50 align-self-center'*/}
              {/*  alt={skill.title}*/}
              {/*/>*/}
              {/*</span>*/}
              {/*</span>*/}

              <span className='d-flex flex-column'>
                <span className='fw-bolder fs-6'>{skill.label}</span>
                {/*<span className='fs-7 text-muted'>Base Web Projec</span>*/}
              </span>
            </span>

              <span className='form-check form-check-custom form-check-solid'>
              <input
                className='form-check-input'
                type='checkbox'
                // checked={data.appFramework === 'HTML5'}
                onChange={(event) => {
                  const newSelected = selected.filter((sk) => sk.value !== skill.value);
                  if (event.currentTarget.checked) {
                    newSelected.push(skill);
                  }
                  setSelected(newSelected);
                }}
              />
            </span>
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
              <KTIcon iconName='arrow-left' className='fs-3 me-1'/>
              {trans('previous')}
            </button>
          </div>


          <div>
            <button
              disabled={skills.isPending || selected.length === 0}
              type='button'
              className='btn btn-lg btn-primary'
              onClick={() => {
                if (selected.length === 0) {
                  return;
                }
                callback(selected);
                nextStep();
              }}
            >
              {trans('next')}
              <KTIcon iconName='arrow-right' className='fs-3 ms-1 me-0'/>
            </button>
          </div>
        </div>
        {/*end::Form Group */}
      </div>
    </div>
  )
}
export {Step2}
