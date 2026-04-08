import {Locales, LocalesOptions} from "@/types";

export function getSupportedLocales(): Locales {
  return window._locales || {};
}

export function getLocaleOptions(locale: string): LocalesOptions {
  return window._locales[locale] || {};
}

export function hasLocale(locale: string): boolean {
  return Boolean(window._locales[locale]);
}

