export type TranslatedAttributes = {
    name: string;
};

export type FormInput = {
    is_active: boolean;
    image?: File | null;
    translations: Record<string, TranslatedAttributes>;
    _method?: string;
};
