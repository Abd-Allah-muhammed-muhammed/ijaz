import {Category} from "@/types/models";
import {SelectOption} from "@/types";

export type CategoryFormData = {
  category: Category
  skills: SelectOption[]
}

export type FormInput = {
  name: string;
  email: string;
  iban: string;
  provider_type_id: number | null;
  phone: string;
  region_id: number | null;
  city_id: number | null;
  address: string;
  about: string;
  commercial_record: File | undefined
  logo: File | undefined
  categories: CategoryFormData[]
  password: string;
  password_confirmation: string;
};
