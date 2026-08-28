import { Bank } from '@/shared/types/models';

export type TranslatedAttributes = {
  name: string;
};

export type FormInput = {
  translations: Record<string, TranslatedAttributes>;
  logo?: File;
  is_active: boolean;
};

export type BankFormProps = {
  row?: Bank;
  logoUrl: string;
};
