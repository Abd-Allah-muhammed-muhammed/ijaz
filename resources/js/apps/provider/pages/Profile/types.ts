import type {
  Category,
  City,
  Provider,
  ProviderType,
  ProviderTypeFileKeys,
  Region,
} from '@/shared/types/models';

export type ProfileSkillOption = {
  value: string;
  label: string;
};

export type ProfilePageProps = {
  types: ProviderType[];
  cities: City[];
  regions: Region[];
  provider: Provider;
};

export type ProfileCategorySelection = {
  category: Category;
  skills: ProfileSkillOption[];
};

export type ProfileFormCategory = {
  id: number;
  skills: number[];
};

export type ProfileFormData = {
  provider_type_id: number | null;
  name: string | null;
  email: string | null;
  phone: string | null;
  iban: string | null;
  address: string | null;
  region_id: number | null;
  city_id: number | null;
  password: string | null;
  password_confirmation: string | null;
  about: string | null;
  categories: ProfileFormCategory[];
  logo?: File;
  id_image?: File;
  commercial_record?: File;
  freelancer_certification?: File;
  iban_certification?: File;
  license_to_practice_law?: File;
};

export type ProfileRequiredFiles = Record<ProviderTypeFileKeys, boolean>;

export type ProfileFileFieldKey = Extract<
  keyof ProfileFormData,
  ProviderTypeFileKeys
>;
