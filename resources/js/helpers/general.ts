import {APP_URL} from '@/constants';
import {ZodObject} from "zod";
import {InertiaFormProps, usePage} from "@inertiajs/react";
import {FormDataConvertible} from "@inertiajs/core";

export const url = (path: string): string => {
  path = path || '';
  path = path.trimStart().replace(/\/+/g, '/')
  if (path[0] === '/') {
    path = path.substring(1);
  }
  return `${APP_URL}/${path}`
    .replace(/^\/|\/$/g, '')
    .trim();
}

export const replaceLocale = (currentLocale: string, locale: string, url: string) => {
  if (url === `/${currentLocale}`) {
    return `/${locale}`
  }
  return url.replace(`/${currentLocale}/`, `/${locale}/`)
};
export const makeOnline = (user: { socket_id: string }) => {
  Array.from(document.getElementsByClassName(user.socket_id)).forEach(function (element) {
    element.classList.remove('d-none')
  })
}

export const makeOffline = (user: { socket_id: string }) => {
  Array.from(document.getElementsByClassName(user.socket_id)).forEach(function (element) {
    element.classList.add('d-none')
  })
}


export const scrollToDiv = (id: string) => {
  const element = document.querySelector(id);
  window.scrollTo({
    top: (element?.getBoundingClientRect().top || 0) + window.pageYOffset - 150,
  });
}
export const zodValidate = <T extends Record<string, FormDataConvertible>>(schema: ZodObject, form: InertiaFormProps<T>, extra: Record<string, unknown> = {}): boolean => {
  const data = {...form.data, ...extra};
  const result = schema.safeParse(data);
  form.clearErrors();
  if (!result.success) {
    result.error.issues.forEach(issue => {
      //@ts-ignore
      form.setError(issue.path.join('.'), issue.message);
    })
  }
  return result.success;
}

export const build_date = (date: string | Date) => {
  const d = new Date(date);
  return d.toLocaleDateString() + ' : ' + d.toLocaleTimeString();
}
export const when = <T>(condition: Boolean, callback: T | (() => T), fallback: unknown):T =>{

  if (condition) {
    return typeof callback === 'function' ? (callback as () => T)() : callback;
  }
   return typeof fallback === 'function' ? (fallback as () => T)() : fallback as T;
}

export const unless = <T>(condition: Boolean, callback: T | (() => T), fallback: unknown):T =>{
  if (!condition) {
    return typeof callback === 'function' ? (callback as () => T)() : callback;
  }
  return fallback  as T;
}
export const whenLocale = <T>(locale:string , callback: T | (() => T), fallback: unknown):T => {
  const domLocale = usePage().props.app.locale
  return when(domLocale === locale, callback, fallback);
}

