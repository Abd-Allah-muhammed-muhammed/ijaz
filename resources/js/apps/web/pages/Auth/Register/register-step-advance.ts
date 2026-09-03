import { availableSteps, Inputs } from './providerSchema';

export type RegistrationStep = (typeof availableSteps)[number];

export type ClientStepAdvanceResult =
  | { success: true }
  | {
      success: false;
      fieldErrors: Partial<Record<keyof Inputs | string, string>>;
    };

export type PrecognitiveValidationOutcome = 'success' | 'validation_error' | 'failed';

/**
 * Minimal config shape for Inertia 3.0.2 `useForm().validate({ only, ... })`.
 * Callers wrap the form method because its generics reject a plain ValidationConfig.
 */
export type PrecognitiveValidateConfig = {
  only: string[];
  onPrecognitionSuccess?: () => void;
  onValidationError?: () => void;
  onFinish?: () => void;
};

export type PrecognitiveValidate = (config: PrecognitiveValidateConfig) => unknown;

/**
 * Instant client-side Zod checks for the current wizard step.
 * Server-confirmed uniqueness/format lives in Precognition (`runPrecognitiveValidation`).
 */
export function validateRegistrationStepClient(
  step: RegistrationStep,
  data: unknown,
): ClientStepAdvanceResult {
  if (! step.rules) {
    return { success: true };
  }

  const validation = step.rules.safeParse(data);

  if (! validation.success) {
    const fieldErrors: Partial<Record<string, string>> = {};

    for (const issue of validation.error.issues) {
      fieldErrors[issue.path.join('.')] = issue.message;
    }

    return { success: false, fieldErrors };
  }

  return { success: true };
}

/**
 * Wraps Inertia 3.0.2
 * `useForm().validate({ only, onPrecognitionSuccess, onValidationError, onFinish })`
 * in a Promise so wizard "Next" can await a fresh server check.
 */
export function runPrecognitiveValidation(
  validate: PrecognitiveValidate,
  fields: string[],
): Promise<PrecognitiveValidationOutcome> {
  if (fields.length === 0) {
    return Promise.resolve('success');
  }

  return new Promise((resolve) => {
    let outcome: PrecognitiveValidationOutcome | 'pending' = 'pending';

    validate({
      only: fields,
      onPrecognitionSuccess: () => {
        outcome = 'success';
      },
      onValidationError: () => {
        outcome = 'validation_error';
      },
      onFinish: () => {
        resolve(outcome === 'pending' ? 'failed' : outcome);
      },
    });
  });
}
