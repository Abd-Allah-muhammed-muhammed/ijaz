import { checkProviderRegistrationPhone } from './check-provider-phone';
import { availableSteps, Inputs } from './providerSchema';

export type RegistrationStep = (typeof availableSteps)[number];

export type StepAdvanceResult =
  | { success: true }
  | {
      success: false;
      fieldErrors: Partial<Record<keyof Inputs | string, string>>;
      blockedOnPhoneCheck?: boolean;
    };

export async function validateRegistrationStepAdvance(
  step: RegistrationStep,
  data: unknown,
  locale: string,
  phone: string | null,
): Promise<StepAdvanceResult> {
  if (!step.rules) {
    return { success: true };
  }

  const validation = step.rules.safeParse(data);

  if (!validation.success) {
    const fieldErrors: Partial<Record<string, string>> = {};

    for (const issue of validation.error.issues) {
      fieldErrors[issue.path.join('.')] = issue.message;
    }

    return { success: false, fieldErrors };
  }

  if (step.requiresPhoneAvailabilityCheck) {
    const result = await checkProviderRegistrationPhone(locale, phone ?? '');

    if (result.status === 'invalid') {
      return {
        success: false,
        fieldErrors: { phone: result.message },
        blockedOnPhoneCheck: true,
      };
    }

    if (result.status === 'failed') {
      return {
        success: false,
        fieldErrors: {},
        blockedOnPhoneCheck: true,
      };
    }
  }

  return { success: true };
}
