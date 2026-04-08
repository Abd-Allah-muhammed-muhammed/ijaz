import {z, ZodObject} from "zod";

import {getSupportedLocales} from "@/hooks/use-locales";

const locales = getSupportedLocales();


export const Inputs = z.object({
  translations: z.object(
    Object.keys(locales).reduce((acc, locale) =>  {
      acc[locale] = z.object({
        title: z.string().min(2, {message: 'Title is required'}).max(191, {message: 'Title must be less than 191 characters'}),
        content: z.string().min(2, {message: 'content is required'}).max(65535, {message: 'answer must be less than 65535 characters'}),
      });
      return acc;
    }, {} as Record<string, ZodObject>)
  )
})


export type Inputs = z.infer<typeof Inputs>;
