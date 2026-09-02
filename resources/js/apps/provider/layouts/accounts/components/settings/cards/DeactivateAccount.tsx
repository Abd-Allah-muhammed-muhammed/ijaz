import {useState, FC, FormEvent} from 'react'
import {useTranslation} from 'react-i18next'
import {router} from '@inertiajs/react'
import {KTIcon} from '@/vendor/metronic/helpers'
import withReactContent from 'sweetalert2-react-content'
import Swal from 'sweetalert2'
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController'

const DeactivateAccount: FC = () => {
  const {t} = useTranslation()
  const [confirmed, setConfirmed] = useState(false)
  const [processing, setProcessing] = useState(false)
  const swal = withReactContent(Swal)

  const submitDeactivation = (event: FormEvent) => {
    event.preventDefault()

    if (!confirmed || processing) {
      return
    }

    swal.fire({
      title: t('are_you_sure'),
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: t('cancel'),
      confirmButtonText: t('yes'),
    }).then((result) => {
      if (!result.isConfirmed) {
        return
      }

      setProcessing(true)
      router.post(
        AuthController.deactivate().url,
        {confirmed: true},
        {
          onFinish: () => setProcessing(false),
        },
      )
    })
  }

  return (
    <div className='card'>
      <div
        className='card-header border-0 cursor-pointer'
        role='button'
        data-bs-toggle='collapse'
        data-bs-target='#kt_account_deactivate'
        aria-expanded='true'
        aria-controls='kt_account_deactivate'
      >
        <div className='card-title m-0'>
          <h3 className='fw-bolder m-0'>{t('deactivate_account')}</h3>
        </div>
      </div>

      <div id='kt_account_deactivate' className='collapse show'>
        <form
          id='kt_account_deactivate_form'
          className='form'
          onSubmit={submitDeactivation}
        >
          <div className='card-body border-top p-9'>
            <div className='notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6'>
              <KTIcon iconName='information-5' className='fs-2tx text-warning me-4' />

              <div className='d-flex flex-stack flex-grow-1'>
                <div className='fw-bold'>
                  <h4 className='text-gray-800 fw-bolder'>{t('you_are_deactivating_your_account')}</h4>
                  <div className='fs-6 text-gray-600'>
                    {t('deactivate_account_disclaimer')}
                  </div>
                </div>
              </div>
            </div>

            <div className='form-check form-check-solid fv-row'>
              <input
                className='form-check-input'
                type='checkbox'
                id='deactivate'
                checked={confirmed}
                onChange={(event) => setConfirmed(event.target.checked)}
              />
              <label className='form-check-label fw-bold ps-2 fs-6' htmlFor='deactivate'>
                {t('i_confirm_my_account_deactivation')}
              </label>
            </div>

          </div>

          <div className='card-footer d-flex justify-content-end py-6 px-9'>
            <button
              id='kt_account_deactivate_account_submit'
              type='submit'
              className='btn btn-danger fw-bold'
              disabled={!confirmed || processing}
            >
              {!processing && t('deactivate_account')}
              {processing && (
                <span className='indicator-progress' style={{display: 'block'}}>
                  {t('Please wait...')}{' '}
                  <span className='spinner-border spinner-border-sm align-middle ms-2'></span>
                </span>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export {DeactivateAccount}
