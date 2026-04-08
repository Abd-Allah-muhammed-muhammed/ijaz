import * as Yup from 'yup'
import clsx from 'clsx'
import {Link, useForm} from '@inertiajs/react'
import AuthController from "@/actions/App/Http/Controllers/Dashboard/AuthController";
import ActionButton from "@/components/action-button";
import { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEye, faEyeSlash } from '@fortawesome/free-solid-svg-icons';
import { useTranslation } from "react-i18next";

const loginSchema = Yup.object().shape({
  email: Yup.string()
    .email('Wrong email format')
    .min(3, 'Minimum 3 symbols')
    .max(50, 'Maximum 50 symbols')
    .required('Email is required'),
  password: Yup.string()
    .min(3, 'Minimum 3 symbols')
    .max(50, 'Maximum 50 symbols')
    .required('Password is required'),
})

const initialValues = {
  email: '',
  password: '',
}
type LoginFormInputs = {
  email: string
  password: string
}

export function Login() {
  const form = useForm<LoginFormInputs>(initialValues)
  const [showPassword, setShowPassword] = useState(false);

  const togglePassword = () => {
    setShowPassword(!showPassword);
  };
  const { t } = useTranslation();
  return (
    <form
      className='form w-100 h-100'
      onSubmit={(e) => {
        e.preventDefault()
        form.submit(AuthController.login())
      }}
      id='kt_login_signin_form'
    >
      {/* begin::Heading */}
      <div className='text-center mb-11'>
        <h1 className='text-gray-900 fw-bolder mb-3'>Sign In</h1>
      </div>
      {form.hasErrors && (
        <div className='mb-lg-15 alert alert-danger'>
          {Object.values(form.errors).map(error => (
            <div key={error} className='alert-text font-weight-bold'>{error}</div>
          ))}
        </div>
      )}

      {/* begin::Form group */}
      <div className='fv-row mb-8'>
        <label className='form-label fs-6 fw-bolder text-gray-900'>Email</label>
        <input
          placeholder='Email'
          className='form-control bg-transparent'
          type='email'
          name='email'
          autoComplete='off'
          onChange={(e)=> form.setData('email', e.currentTarget.value)}
        />
        {form.errors.email && (
          <div className='fv-plugins-message-container'>
            <span role='alert'>{form.errors.email}</span>
          </div>
        )}
      </div>
      {/* end::Form group */}

      {/* begin::Form group */}
      <div className='fv-row mb-3'>
        <label className='form-label fw-bolder text-gray-900 fs-6 mb-0'>Password</label>
        <div className="input-group">
          <input
            type={showPassword ? 'text' : 'password'}
            autoComplete='off'
            className='form-control bg-transparent'
            placeholder={t('password')}
            onChange={(e)=> form.setData('password', e.currentTarget.value)}
          />
          <span className="input-group-text" onClick={togglePassword} style={{ cursor: 'pointer' }}>
            <FontAwesomeIcon icon={showPassword ? faEyeSlash : faEye} />
          </span>
        </div>
        {form.errors.password && (
          <div className='fv-plugins-message-container'>
            <div className='fv-help-block'>
              <span role='alert'>{form.errors.password}</span>
            </div>
          </div>
        )}
      </div>
      {/* end::Form group */}

      {/* begin::Action */}
      <div className='d-grid mb-10'>
        <ActionButton
          isProcessing={form.processing}
        />
      </div>
      {/* end::Action */}
    </form>
  )
}
