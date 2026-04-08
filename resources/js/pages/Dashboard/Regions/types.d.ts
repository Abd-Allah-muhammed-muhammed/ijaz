import {getSupportedLocales} from "@/hooks/use-locales";

const locales = getSupportedLocales();

export type  TranslatedAttributes = {
  title: string;
};

export type FormInput = {
  translations: Record<string, TranslatedAttributes>;
};
