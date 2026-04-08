import { getSupportedLocales } from "@/hooks/use-locales";

const locales = getSupportedLocales();

export type TranslatedAttributes = {
    title: string;
};

export type FormInput = {
    parent_id: string;
    is_active: boolean;
    translations: Record<string, TranslatedAttributes>;
};
