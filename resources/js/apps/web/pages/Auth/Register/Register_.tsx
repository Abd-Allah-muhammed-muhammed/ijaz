import { useEffect, useState } from 'react';
import { Form } from 'react-bootstrap';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import ToastContainer from '@/shared/components/toaster/toast-container';
import ToastEffect from '@/shared/components/toaster/toast-effect';
import useSteps from '@/shared/hooks/use-steps';
import { REGISTRATION_STEP_CONTAINER_CLASS } from '@/shared/components/categories/category-picker';
import { ProviderTypeFilesEnum } from '@/Enums/Enums';
import type { City, ProviderType, Region } from '@/shared/types/models';
import { availableSteps, type CategoryOption } from './providerSchema';
import {
  clearStoredRegistrationStep,
  resolveInitialRegistrationStep,
  writeStoredRegistrationStep,
} from './registration-step-storage';
import { useRegistrationForm } from './hooks/use-registration-form';
import { useOtp } from './hooks/use-otp';
import { useRegistrationAdvance } from './hooks/use-registration-advance';
import type { RequiredFilesState } from './types';
import RegistrationStepper from './components/RegistrationStepper';
import RegistrationFooterControls from './components/RegistrationFooterControls';
import AccountTypeStep from './components/steps/AccountTypeStep';
import AccountInfoStep from './components/steps/AccountInfoStep';
import CategoriesStep from './components/steps/CategoriesStep';
import FilesStep from './components/steps/FilesStep';
import SummaryStep from './components/steps/SummaryStep';
import OtpStep from './components/steps/OtpStep';
import CompletedStep from './components/steps/CompletedStep';
import './style.css';

type RegisterProps = {
  types: ProviderType[];
  regions: Region[];
  cities: City[];
};

export default function Register_({ types, regions, cities }: RegisterProps) {
  const form = useRegistrationForm();
  const [categoriesOptions, setCategoriesOptions] = useState<Map<number, CategoryOption>>(new Map());
  const [requiredFiles, setRequiredFiles] = useState<RequiredFilesState>(
    Object.values(ProviderTypeFilesEnum).reduce((acc, file) => {
      acc[file] = false;
      return acc;
    }, {} as RequiredFilesState),
  );
  const [providerType, setProviderType] = useState<ProviderType | null>(null);
  const { t } = useTranslation();
  const page = usePage<{ errors?: Record<string, string> }>();
  const serverErrorCount = Object.keys(page.props.errors ?? {}).length;
  const steps = useSteps({
    totalSteps: availableSteps.length,
    initialStep: resolveInitialRegistrationStep(availableSteps.length, serverErrorCount > 0),
  });

  const otp = useOtp({
    phone: form.data.phone,
    processing: form.processing,
    onValidationErrors: (errors) => {
      form.setError(errors);
    },
  });

  const { handleSubmit, handleNext } = useRegistrationAdvance({
    form,
    steps,
    requiredFiles,
    requestOtp: otp.requestOtp,
  });

  useEffect(() => {
    writeStoredRegistrationStep(steps.currentStep);
  }, [steps.currentStep]);

  useEffect(() => {
    if (steps.stepIs(availableSteps.length)) {
      clearStoredRegistrationStep();
    }
  }, [steps.currentStep, steps]);

  return (
    <div className="d-flex flex-column flex-root h-100" id="kt_app_root" data-pan="register-page">
      <ToastContainer />
      <ToastEffect />
      <Head title={t('register')} />
      <div className="d-flex flex-column flex-lg-row flex-column-fluid stepper stepper-pills stepper-column stepper-multistep">
        <RegistrationStepper currentStep={steps.currentStep} stepIs={steps.stepIs} />
        <div className="d-flex flex-column flex-lg-row-fluid">
          <div className="d-flex flex-center flex-column flex-column-fluid">
            <Form
              className="w-100 my-auto pb-3 pb-lg-5"
              noValidate
              id="kt_create_account_form"
              onSubmit={handleSubmit}
            >
              <div className={`${REGISTRATION_STEP_CONTAINER_CLASS} p-3 p-md-4 p-lg-10 p-xl-15 mx-auto`}>
                <AccountTypeStep
                  isCurrent={steps.stepIs(1)}
                  types={types}
                  form={form}
                  onProviderTypeSelected={(type, files) => {
                    setRequiredFiles(files);
                    setProviderType(type);
                  }}
                />
                <AccountInfoStep
                  isCurrent={steps.stepIs(2)}
                  form={form}
                  regions={regions}
                  cities={cities}
                />
                <CategoriesStep
                  isCurrent={steps.stepIs(3)}
                  form={form}
                  providerTypeId={providerType?.id}
                  onCategoriesChange={setCategoriesOptions}
                />
                <FilesStep isCurrent={steps.stepIs(4)} form={form} requiredFiles={requiredFiles} />
                <SummaryStep
                  isCurrent={steps.stepIs(5)}
                  form={form}
                  providerType={providerType}
                  categoriesOptions={categoriesOptions}
                />
                <OtpStep
                  isCurrent={steps.stepIs(6)}
                  form={form}
                  seconds={otp.seconds}
                  formatSeconds={otp.formatSeconds}
                  onResend={otp.resendOtp}
                />
                <CompletedStep
                  isCurrent={steps.stepIs(7)}
                  form={form}
                  providerType={providerType}
                />
                <RegistrationFooterControls
                  currentStep={steps.currentStep}
                  processing={form.processing}
                  stepIs={steps.stepIs}
                  stepBetween={steps.stepBetween}
                  onPrevious={steps.prevStep}
                  onNext={handleNext}
                />
              </div>
            </Form>
          </div>
        </div>
      </div>
    </div>
  );
}
