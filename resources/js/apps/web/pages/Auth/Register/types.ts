import { ProviderTypeFilesEnum } from '@/Enums/Enums';
import type { Inputs } from './providerSchema';

export type RequiredFilesState = Record<
  (typeof ProviderTypeFilesEnum)[keyof typeof ProviderTypeFilesEnum],
  boolean
>;

export type RegistrationFormData = Inputs;

/** Fields that use Precognition on blur + Next. */
export type PrecognitionField = 'phone' | 'email' | 'iban';
