
export type TranslatedAttributes = {
    name: string;
};

export type FormInput = {
    translations: Record<string, TranslatedAttributes>;
    is_active: boolean;
    image?: File | null;
    car_brand_id: number | null;
    _method?: string;
};

