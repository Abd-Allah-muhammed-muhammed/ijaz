import {z} from "zod";
import i18n from "@/lang/i18next";

const fileSize = 8; // 8 MB

export const formSchema = z.object({
  provider_type_id: z.number(i18n.t('validation.required', {attribute: i18n.t('provider_type')})),

  requiredFiles: z.object({
    id_image: z.boolean(),
    commercial_record: z.boolean(),
    freelancer_certification: z.boolean(),
    iban_certification: z.boolean(),
  }),

  name: z.string(i18n.t('validation.required', {attribute: i18n.t('name')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('name')}))
    .min(3, i18n.t('validation.min.string', {attribute: i18n.t('name'), min: '3'})),

  about: z.string()
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('about')}))
    .optional(),

  email: z.email(i18n.t('validation.email', {attribute: i18n.t('email')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('email')})),

  phone: z.string(i18n.t('validation.required', {attribute: i18n.t('phone')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('phone')}))
    .regex(
      new RegExp('^(?<key>(\\+|00)?966|0)?(?<provider>5)(?<digits>\\d{8})$'),
      i18n.t('validation.regex', {attribute: i18n.t('phone')})
    ),

  address: z.string(i18n.t('validation.required', {attribute: i18n.t('address')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('phone')})),

  region_id: z.number(i18n.t('validation.required', {attribute: i18n.t('region')})),

  city_id: z.number(i18n.t('validation.required', {attribute: i18n.t('city')})),

  iban: z.string(i18n.t('validation.required', {attribute: i18n.t('iban')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('iban')})),

  password: z.string(i18n.t('validation.required', {attribute: i18n.t('password')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('password')}))
    .min(6, "Password must be at least 6 characters long"),

  password_confirmation: z.string(i18n.t('validation.required', {attribute: i18n.t('password_confirmation')}))
    .nonempty(i18n.t('validation.required', {attribute: i18n.t('password_confirmation')}))
    .min(6, "Password confirmation must be at least 6 characters long"),

  otp: z.string().max(4, i18n.t('validation.max.string', {attribute: i18n.t('otp'), max: '4'})),

  categories: z.array(z.object({
    id: z.number(i18n.t('validation.required', {attribute: i18n.t('category')})),
    skills: z.array(z.number()).nullish()
  }))
    .min(1, i18n.t('validation.required', {attribute: i18n.t('categories')})),

  id_image: z.file()
    .max(fileSize * 1024 * 1024, i18n.t('validation.max.file', {'attribute': i18n.t('id_image'), max: String(fileSize * 1024)}))
    .mime(['application/pdf'], i18n.t('validation.mimes', {'attribute': i18n.t('id_image'), 'values': 'pdf'}))
    .optional()
  ,

  commercial_record: z.file()
    .max(fileSize * 1024 * 1024, i18n.t('validation.max.file', {'attribute': i18n.t('commercial_record'), max: String(fileSize * 1024)}))
    .mime(['application/pdf'], i18n.t('validation.mimes', {'attribute': i18n.t('commercial_record'), 'values': 'pdf'}))
    .optional(),

  iban_certification: z.file()
    .max(fileSize * 1024 * 1024, i18n.t('validation.max.file', {
      'attribute': i18n.t('iban_certification'),
      'max': String(fileSize * 1024)
    }))
    .mime(['application/pdf'], i18n.t('validation.mimes', {'attribute': i18n.t('iban_certification'), 'values': 'pdf'}))
    .optional(),

  freelancer_certification: z.file()
    .max(fileSize * 1024 * 1024, i18n.t('validation.max.file', {
      'attribute': i18n.t('freelancer_certification'),
      'max': String(fileSize * 1024)
    }))
    .mime(['application/pdf'], i18n.t('validation.mimes', {
      'attribute': i18n.t('freelancer_certification'),
      'values': 'pdf'
    }))
    .optional(),

  logo: z.file(i18n.t('validation.required', {attribute: i18n.t('logo')}))
    .max(fileSize * 1024 * 1024, i18n.t('validation.max.file', {
      'attribute': i18n.t('logo'),
      'max':  String(fileSize * 1024)
    }))
    .mime(['image/jpeg', 'image/png'], i18n.t('validation.mimes', {
      'attribute': i18n.t('logo'),
      'values': 'png,jpeg'
    })),
})
  // .refine(data => {
  //   return data.password === data.password_confirmation
  // }, {
  //   message: i18n.t('validation.confirmed', {attribute: i18n.t('password_confirmation')}),
  //   path: ["password_confirmation"],
  //   params: {'code': 'passwords_match'}
  // })
  // .superRefine((data, ctx) => {
  //   console.log('refine')
  //   if (data.requiredFiles.id_image && !data.id_image) {
  //     ctx.addIssue({
  //       code: 'custom', // Use custom code for conditional validation
  //       message: i18n.t('validation.required', {attribute: i18n.t('id_image')}),
  //       path: ['id_image'],
  //     });
  //   }
  // });


export type Schema = z.infer<typeof formSchema>;

export type CategoryOption = {
  id: number;
  title: string;
  icon: string;
  skills: {
    value: string;
    label: string;
  }[];
}


export type Inputs = {
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
  otp: string | null;
  categories: {
    id: number;
    skills: number[];
  }[];
  id_image?: File;
  commercial_record?: File;
  iban_certification?: File;
  freelancer_certification?: File;
  logo: File | undefined;
}



export const availableSteps = [
  {
    title: i18n.t('account_type'),
    description: i18n.t('select_account_type'),
    rules: formSchema.pick({
      provider_type_id: true,
    }),
  },
  {
    title: i18n.t('account_information'),
    description: i18n.t('setup_your_account_information'),
    rules: formSchema.pick({
      name: true,
      about: true,
      email: true,
      phone: true,
      address: true,
      region_id: true,
      city_id: true,
      iban: true,
      password: true,
      password_confirmation: true,
    }),

  },

  {
    title: i18n.t('categories & skills'),
    description: i18n.t('select_your_categories & skills'),
    rules: formSchema.pick({
      categories: true,
    }),
  },
  {
    title: i18n.t('files'),
    description: i18n.t('provide_your_files'),
    rules: formSchema.pick({
      requiredFiles: true,
      id_image: true,
      commercial_record: true,
      iban_certification: true,
      freelancer_certification: true,
      logo: true,
    })
      .refine(data => data.requiredFiles.id_image ? Boolean(data.id_image) : true,
      {
        message: i18n.t('validation.required', {attribute: i18n.t('id_image')}),
        path: ['id_image'],
        params: {'code': 'iban_certification_required'}
      })
      .refine(data => {
        return data.requiredFiles.commercial_record ? Boolean(data.commercial_record) : true
      }, {
        message: i18n.t('validation.required', {attribute: i18n.t('commercial_record')}),
        path: ['commercial_record'],
        params: {'code': 'commercial_record_required'}
      })
      .refine(data => {
        return data.requiredFiles.iban_certification ? Boolean(data.iban_certification) : true
      }, {
        message: i18n.t('validation.required', {attribute: i18n.t('iban_certification')}),
        path: ['iban_certification'],
        params: {'code': 'iban_certification_required'}
      })
      .refine(data => {
        return data.requiredFiles.freelancer_certification ? Boolean(data.freelancer_certification) : true
      }, {
        message: i18n.t('validation.required', {attribute: i18n.t('freelancer_certification')}),
        path: ['freelancer_certification'],
        params: {'code': 'freelancer_certification_required'}
      })
    ,
  },
  {
    title: i18n.t('summary'),
    description: i18n.t('review_your_information'),

  },
  {
    title: i18n.t('phone_verification'),
    description: i18n.t('setup_your_phone_verification'),
    rules: formSchema.pick({
      otp: true,
    }),
  },

  {
    title: i18n.t('completed'),
    description: i18n.t('your_account_is_created'),
  },
];
