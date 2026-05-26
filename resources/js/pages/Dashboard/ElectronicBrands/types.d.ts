export type TranslatedAttributes = {
  name: string;
};

export type FormInput = {
  image: undefined | File;
  is_active: boolean;
  translations: Record<string, TranslatedAttributes>;
  _method?: string;
};
