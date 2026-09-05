import type { FormEvent } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import AuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';
import {
  availableSteps,
  type Inputs,
} from '../providerSchema';
import {
  clearRegistrationSessionStorage,
  clearStoredRegistrationStep,
  REGISTRATION_FILES_STEP,
  REGISTRATION_OTP_STEP,
  writeStoredRegistrationStep,
} from '../registration-step-storage';
import {
  runPrecognitiveValidation,
  validateRegistrationStepClient,
} from '../register-step-advance';
import type { RegistrationForm } from './use-registration-form';
import { useRegistrationUploads } from './registration-uploads-context';
import type { RequiredFilesState } from '../types';
import type { RegistrationUploadField } from '../registration-upload-constants';
import { extractExpiredUploadFieldsForTest as extractExpiredUploadFields } from './upload-error-helpers';

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

const FILE_FIELD_KEYS: RegistrationUploadField[] = [
  'logo',
  'id_image',
  'commercial_record',
  'iban_certification',
  'freelancer_certification',
  'license_to_practice_law',
];

/**
 * Final submit + wizard Next (Zod client checks, OTP request, Precognition).
 */
export function useRegistrationAdvance({
  form,
  steps,
  requiredFiles,
  requestOtp,
}: UseRegistrationAdvanceOptions) {
  const { t } = useTranslation();
  const uploads = useRegistrationUploads();
  const [finishingUploads, setFinishingUploads] = useState(false);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    writeStoredRegistrationStep(steps.currentStep);

    if (uploads.hasFailedUploads) {
      toast.error(t('provider_registration.fix_upload_before_submit'));
      steps.goToStep(REGISTRATION_FILES_STEP);
      writeStoredRegistrationStep(REGISTRATION_FILES_STEP);

      return;
    }

    if (uploads.hasInFlightUploads) {
      setFinishingUploads(true);
      const settled = await uploads.awaitInFlightUploads();
      setFinishingUploads(false);

      if (settled.failed || settled.stillInFlight) {
        toast.error(t('provider_registration.fix_upload_before_submit'));
        steps.goToStep(REGISTRATION_FILES_STEP);
        writeStoredRegistrationStep(REGISTRATION_FILES_STEP);

        return;
      }
    }

    const uploadIds = uploads.getUploadIds();
    const requiredFieldList: RegistrationUploadField[] = [
      'logo',
      ...(Object.entries(requiredFiles) as Array<[RegistrationUploadField, boolean]>)
        .filter(([, required]) => required)
        .map(([key]) => key),
    ];

    for (const field of requiredFieldList) {
      if (! uploadIds[field]) {
        form.setError(field as keyof Inputs, t('validation.required', { attribute: t(field) }));
        toast.error(t('provider_registration.fix_upload_before_submit'));
        steps.goToStep(REGISTRATION_FILES_STEP);
        writeStoredRegistrationStep(REGISTRATION_FILES_STEP);

        return;
      }
    }

    form.transform((data) => {
      const next: Record<string, unknown> = {
        ...data,
        upload_token: uploads.token,
        uploads: uploadIds,
      };

      FILE_FIELD_KEYS.forEach((field) => {
        delete next[field];
      });

      return next as typeof data;
    });

    form.post(AuthController.store().url, {
      preserveState: true,
      preserveScroll: true,
      forceFormData: false,
      onSuccess: (response) => {
        if (! response.props.flash?.error && Object.keys(response.props.errors || {}).length === 0) {
          steps.nextStep();
          clearRegistrationSessionStorage();
          uploads.resetAll();
        }
      },
      onError: (errors) => {
        const expiredFields = extractExpiredUploadFields(errors);

        if (expiredFields.length > 0) {
          expiredFields.forEach((field) => {
            form.setError(field as keyof Inputs, errors[`uploads.${field}`] ?? errors[field] ?? t('provider_registration.reupload_required'));
            form.setData(field as keyof Inputs, undefined);
            void uploads.clearField(field);
          });
          toast.error(t('provider_registration.reupload_required'));
          steps.goToStep(REGISTRATION_FILES_STEP);
          writeStoredRegistrationStep(REGISTRATION_FILES_STEP);

          return;
        }

        steps.goToStep(REGISTRATION_OTP_STEP);
        writeStoredRegistrationStep(REGISTRATION_OTP_STEP);
        Object.values(errors).forEach((message) => toast.error(message));
      },
      onFinish: () => {
        form.transform((data) => data);
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

  return { handleSubmit, handleNext, finishingUploads };
}
