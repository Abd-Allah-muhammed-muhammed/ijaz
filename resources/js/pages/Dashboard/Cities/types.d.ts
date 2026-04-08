import {getSupportedLocales} from "@/hooks/use-locales";

const locales = getSupportedLocales();

export type  TranslatedAttributes = {
  title: string;
};

export type FormInput = {
  region_id : string;
  translations: Record<string, TranslatedAttributes>;
};
