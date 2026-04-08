import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import ar from './ar.json';
import en from './en.json';
import hi from './hi.json';
import ur from './ur.json';

const resources = {
  en: {
    translation: en,
  },
  ar: {
    translation: ar,
  },
  hi: {
    translation: hi,
  },
  ur: {
    translation: ur,
  },
};

// Get the initial language from localStorage or default to 'ar'
const I18N_CONFIG_KEY = import.meta.env.VITE_APP_I18N_CONFIG_KEY || 'i18nConfig';
const I18N_DEFAULT_LOCALE = import.meta.env.VITE_APP_I18N_DEFAULT_LOCALE || 'ar';
const getStoredLanguage = () => {
  try {
    const ls = localStorage.getItem(I18N_CONFIG_KEY);
    if (ls) {
      const config = JSON.parse(ls);
      return config.selectedLang || I18N_DEFAULT_LOCALE;
    }
  } catch (error) {
    console.error('Error reading language from localStorage:', error);
  }
  
  return I18N_DEFAULT_LOCALE;
};


i18n
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: undefined,
    lng: undefined,
    interpolation: {
      escapeValue: false, // react already safes from xss
    },
  });

export default i18n;
