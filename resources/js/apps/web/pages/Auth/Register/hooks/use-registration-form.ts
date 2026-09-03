import { useForm } from '@inertiajs/react';
import type { InertiaPrecognitiveFormProps } from '@inertiajs/react';
import AuthController from '@/actions/App/Http/Controllers/Frontend/AuthController';
import type { Inputs } from '../providerSchema';

export type RegistrationForm = InertiaPrecognitiveFormProps<Inputs>;

const initialRegistrationData: Inputs = {
  provider_type_id: null,
  name: null,
  phone: null,
  email: null,
  iban: null,
  about: undefined,
  password: null,
  password_confirmation: null,
  address: null,
  region_id: null,
  city_id: null,
  otp: null,
  categories: [],
  id_image: undefined,
  commercial_record: undefined,
  iban_certification: undefined,
  freelancer_certification: undefined,
  logo: undefined,
};

/**
 * Precognition-enabled registration form bound to AuthController.store().
 */
export function useRegistrationForm(): RegistrationForm {
  return useForm(AuthController.store(), initialRegistrationData).setValidationTimeout(0);
}
