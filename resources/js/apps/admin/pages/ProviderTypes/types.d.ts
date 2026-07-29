import {ProviderTypeFilesEnum} from '@/Enums/Enums';

export type  TranslatedAttributes = {
  name: string;
  description?: string;
};

export type ProviderTypeFilesEnumValues = (typeof ProviderTypeFilesEnum)[keyof typeof ProviderTypeFilesEnum];

export type FormInput = {
  translations: Record<string, TranslatedAttributes>;
  files: Record<ProviderTypeFilesEnumValues, boolean>
  image: undefined | File,
  categories: string[]
};
