import {AuthLayout} from "@/layouts/provider/AuthLayout";
import {Login} from "@/app/modules/auth/components/Login";
import {Head, Link, useForm} from "@inertiajs/react";
import React, {ReactElement} from "react";
import { useTranslation } from 'react-i18next';
import AuthController from "@/actions/App/Http/Controllers/Frontend/AuthController";
import ProviderAutController from "@/actions/App/Http/Controllers/Provider/AuthController";
import ActionButton from "@/components/action-button";
import InputError from "@/components/inputs/InputError";
import { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEye, faEyeSlash } from '@fortawesome/free-solid-svg-icons';

const LoginPage = () => {
  const { t } = useTranslation();
  const form = useForm({
    email: '',
    password: '',
    remember: false,
  });
  const [showPassword, setShowPassword] = useState(false);

  const togglePassword = () => {
    setShowPassword(!showPassword);
  };

  return (
    <>
      <Head title={'Login'}/>
      <div className="d-flex flex-stack py-2">
        <div className="me-2"></div>
        <div className="m-0">
          <span className="text-gray-500 fw-bold fs-5 me-2">{t('not a member yet?')}</span>
          <Link href={AuthController.create().url} className="link-primary fw-bold fs-5">{t('register')}</Link>
        </div>
      </div>
      <div className="py-20">
        <form className="form w-100" noValidate onSubmit={(e) => {
          e.preventDefault();
          form.post(ProviderAutController.login().url);
        }}>
          <div className="card-body">
            <div className="text-start mb-10">
              <h1 className="text-gray-900 mb-3 fs-3x" data-kt-translate="sign-in-title">{t('login')}</h1>
              <div className="text-gray-500 fw-semibold fs-6" data-kt-translate="general-desc">
              </div>
            </div>
            <div className="fv-row mb-8">
              <input
                type="email"
                required
                placeholder={t('email')}
                autoComplete="off"
                onChange={(e) => form.setData('email', e.target.value)}
                className="form-control form-control-solid"/>
              <InputError message={form.errors.email} className="mt-2"/>
            </div>
            <div className="fv-row mb-7">
              <div className="input-group">
                <input
                  required
                  type={showPassword ? 'text' : 'password'}
                  placeholder={t('password')}
                  autoComplete="off"
                  className="form-control form-control-solid"
                  onChange={(e) => form.setData('password', e.target.value)}
                />
                <span className="input-group-text border-0" onClick={togglePassword} style={{ cursor: 'pointer' }}>
                  <FontAwesomeIcon icon={showPassword ? faEyeSlash : faEye} />
                </span>
              </div>
              <InputError message={form.errors.password} className="mt-2"/>
            </div>
            <div className="d-flex flex-stack">
              <ActionButton isProcessing={form.processing} text={t('login')}/>
            </div>
          </div>
        </form>
      </div>
    </>
  )
};

LoginPage.layout = (page: ReactElement) => {
  return (
    <AuthLayout>
      {page}
    </AuthLayout>
  )
};

export default LoginPage;
