
export type TranslatedAttributes = {
    name: string;
};

export type FormInput = {
    is_active: boolean;
    translations: Record<string, TranslatedAttributes>;
};
