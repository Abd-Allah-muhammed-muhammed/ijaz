import {getSupportedLocales} from "@/hooks/use-locales";

const locales = getSupportedLocales();

export type  TranslatedAttributes = {
  title: string;
  description: string;
};

export type FormInput = {
  icon: undefined | File;
  parent_id : string;
  translations: Record<string, TranslatedAttributes>;
  fees?: number;
  fees_type: string;
};
