import type { FormEvent } from 'react';
import { toast } from 'sonner';
import AuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';
import {
  availableSteps,
  type Inputs,
} from '../providerSchema';
import {
  clearStoredRegistrationStep,
  REGISTRATION_OTP_STEP,
  writeStoredRegistrationStep,
} from '../registration-step-storage';
import {
  runPrecognitiveValidation,
  validateRegistrationStepClient,
} from '../register-step-advance';
import type { RegistrationForm } from './use-registration-form';
import type { RequiredFilesState } from '../types';

type StepsApi = {
  currentStep: number;
  nextStep: () => void;
  goToStep: (step: number) => void;
  stepIs: (step: number) => boolean;
};

type UseRegistrationAdvanceOptions = {
  form: RegistrationForm;
  steps: StepsApi;
  requiredFiles: RequiredFilesState;
  requestOtp: () => Promise<boolean>;
};

/**
 * Final submit + wizard Next (Zod client checks, OTP request, Precognition).
 */
export function useRegistrationAdvance({
  form,
  steps,
  requiredFiles,
  requestOtp,
}: UseRegistrationAdvanceOptions) {
  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    writeStoredRegistrationStep(steps.currentStep);

    form.post(AuthController.store().url, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (response) => {
        if (! response.props.flash?.error && Object.keys(response.props.errors || {}).length === 0) {
          steps.nextStep();
          clearStoredRegistrationStep();
        }
      },
      onError: (errors) => {
        steps.goToStep(REGISTRATION_OTP_STEP);
        writeStoredRegistrationStep(REGISTRATION_OTP_STEP);
        Object.values(errors).forEach((message) => toast.error(message));
      },
    });
  };

  const handleNext = async () => {
    const currentStep = availableSteps[steps.currentStep - 1];
    if (! currentStep) {
      console.error('Current step is not defined');
      return;
    }

    const previousServerErrors = {
      phone: form.errors.phone,
      email: form.errors.email,
      iban: form.errors.iban,
    };
    form.clearErrors();

    if (steps.stepIs(availableSteps.length - 2)) {
      const sent = await requestOtp();
      if (! sent) {
        return;
      }
    }

    const data = {
      ...form.data,
      requiredFiles,
    };
    const clientAdvance = validateRegistrationStepClient(currentStep, data);

    if (! clientAdvance.success) {
      Object.entries(clientAdvance.fieldErrors).forEach(([field, message]) => {
        if (message) {
          form.setError(field as keyof Inputs, message);
        }
      });

      return;
    }

    const precognitionFields = [...(currentStep.precognitionFields ?? [])];
    if (precognitionFields.length > 0) {
      const outcome = await runPrecognitiveValidation(
        (config) => {
          form.validate({
            only: config.only as Array<'phone' | 'email' | 'iban'>,
            onPrecognitionSuccess: config.onPrecognitionSuccess,
            onValidationError: config.onValidationError,
            onFinish: config.onFinish,
          });
        },
        precognitionFields,
      );

      if (outcome === 'validation_error') {
        return;
      }

      if (outcome === 'failed') {
        (['phone', 'email', 'iban'] as const).forEach((field) => {
          if (previousServerErrors[field] && ! form.errors[field]) {
            form.setError(field, previousServerErrors[field]);
          }
        });

        return;
      }
    }

    steps.nextStep();
  };

  return { handleSubmit, handleNext };
}
