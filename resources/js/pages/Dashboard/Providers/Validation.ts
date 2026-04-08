import z from 'zod';
import i18next from '@/lang/i18next';

const fileSize = 5; // 5 MB
export const Inputs = z.object({
  id: z.number().optional(),
  provider_type_id: z.number(i18next.t('validation.required', {attribute: i18next.t('provider_type')})),
  requiredFiles: z.object({
    id_image: z.boolean(),
    commercial_record: z.boolean(),
    freelancer_certification: z.boolean(),
    iban_certification: z.boolean(),
  }),

  name: z.string(i18next.t('validation.required', {attribute: i18next.t('name')}))
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('name')}))
    .min(3, i18next.t('validation.min.string', {attribute: i18next.t('name'), min: '3'})),

  about: z.string()
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('about')}))
    .optional(),

  email: z.email(i18next.t('validation.email', {attribute: i18next.t('email')}))
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('email')})),

  phone: z.string(i18next.t('validation.required', {attribute: i18next.t('phone')}))
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('phone')}))
    .regex(
      new RegExp('^(?<key>(\\+|00)?966|0)?(?<provider>5)(?<digits>\\d{8})$'),
      i18next.t('validation.regex', {attribute: i18next.t('phone')})
    ),

  address: z.string(i18next.t('validation.required', {attribute: i18next.t('address')}))
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('phone')})),

  region_id: z.number(i18next.t('validation.required', {attribute: i18next.t('region')})),

  city_id: z.number(i18next.t('validation.required', {attribute: i18next.t('city')})),

  iban: z.string(i18next.t('validation.required', {attribute: i18next.t('iban')}))
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('iban')})),

  password: z.string(i18next.t('validation.required', {attribute: i18next.t('password')}))
    // .min(6, "Password must be at least 6 characters long")
    .nullish(),

  password_confirmation: z.string(i18next.t('validation.required', {attribute: i18next.t('password_confirmation')}))
    // .min(6, "Password confirmation must be at least 6 characters long")
    .nullish(),

  categories: z.array(z.object({
    id: z.number(i18next.t('validation.required', {attribute: i18next.t('category')})),
    skills: z.array(z.number()).min(1, i18next.t('validation.required', {attribute: i18next.t('skills')}))
  }))
    .min(1, i18next.t('validation.required', {attribute: i18next.t('categories')})),

  id_image: z.file()
    .max(fileSize * 1024 * 1024, i18next.t('validation.max.file', {'attribute': i18next.t('id_image'), max: fileSize + 'MB'}))
    .mime(['application/pdf'], i18next.t('validation.mimes', {'attribute': i18next.t('id_image'), 'values': 'pdf'}))
    .optional()
  ,

  commercial_record: z.file()
    .max(fileSize * 1024 * 1024, i18next.t('validation.max.file', {
      'attribute': i18next.t('commercial_record'),
      'max': fileSize + 'MB'
    }))
    .mime(['application/pdf'], i18next.t('validation.mimes', {'attribute': i18next.t('commercial_record'), 'values': 'pdf'}))
    .optional(),

  iban_certification: z.file()
    .max(fileSize * 1024 * 1024, i18next.t('validation.max.file', {
      'attribute': i18next.t('iban_certification'),
      'max': fileSize + 'MB'
    }))
    .mime(['application/pdf'], i18next.t('validation.mimes', {'attribute': i18next.t('iban_certification'), 'values': 'pdf'}))
    .optional(),

  freelancer_certification: z.file()
    .max(fileSize * 1024 * 1024, i18next.t('validation.max.file', {
      'attribute': i18next.t('freelancer_certification'),
      'max': fileSize + 'MB'
    }))
    .mime(['application/pdf'], i18next.t('validation.mimes', {
      'attribute': i18next.t('freelancer_certification'),
      'values': 'pdf'
    }))
    .optional(),

  logo: z.file(i18next.t('validation.required', {attribute: i18next.t('logo')}))
    .max(fileSize * 1024 * 1024, i18next.t('validation.max.file', {
      'attribute': i18next.t('logo'),
      'max': String(fileSize * 1024 * 1024)
    }))
    .mime(['image/jpeg', 'image/png'], i18next.t('validation.mimes', {
      'attribute': i18next.t('commercial_record'),
      'values': 'png,jpeg'
    }))
    .optional(),
})
  .refine(data=>{
    if (data.id){
      return true;
    }
    return !!data.logo;
  },{message: i18next.t('validation.required', {attribute: i18next.t('logo')}),path: ['logo'],})
  .refine((data) => {
    if (data.id) {
      return true;
    }
    return Boolean(data.password);
  }, {
    message: i18next.t('validation.required', {attribute: i18next.t('password')}),
    path: ['password'],
  })
  .refine(data => {
    if (data.id) {
      return true;
    }
    return data.password === data.password_confirmation
  }, {
    message: i18next.t('validation.confirmed', {attribute: i18next.t('password_confirmation')}),
    path: ["password_confirmation"],
    params: {'code': 'passwords_match'}
  })
  .refine(data =>{
    if (data.id){
      return true;
    }
    return  data.requiredFiles.id_image ? Boolean(data.id_image) : true
  } ,
    {
      message: i18next.t('validation.required', {attribute: i18next.t('id_image')}),
      path: ['id_image'],
      params: {'code': 'id_image'}
    })
  .refine(data => {
    if (data.id){
      return true;
    }
    return data.requiredFiles.commercial_record ? Boolean(data.commercial_record) : true
  }, {
    message: i18next.t('validation.required', {attribute: i18next.t('commercial_record')}),
    path: ['commercial_record'],
    params: {'code': 'commercial_record_required'}
  })
  .refine(data => {
    if (data.id){
      return true;
    }
    return data.requiredFiles.iban_certification ? Boolean(data.iban_certification) : true
  }, {
    message: i18next.t('validation.required', {attribute: i18next.t('iban_certification')}),
    path: ['iban_certification'],
    params: {'code': 'iban_certification_required'}
  })
  .refine(data => {
    if (data.id){
      return true;
    }
    return data.requiredFiles.freelancer_certification ? Boolean(data.freelancer_certification) : true
  }, {
    message: i18next.t('validation.required', {attribute: i18next.t('freelancer_certification')}),
    path: ['freelancer_certification'],
    params: {'code': 'freelancer_certification_required'}
  })
;

export type Inputs = z.infer<typeof Inputs>;

export type FormInputs = {
  provider_type_id: number | null;
  name: string | null;
  about?: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  region_id: number | null;
  city_id: number | null;
  iban: string | null;
  password: string | null;
  password_confirmation: string | null;
  categories: {
    id: number;
    skills: number[];
  }[];
  id_image?: File;
  commercial_record?: File;
  iban_certification?: File;
  freelancer_certification?: File;
  logo?: File;
}
