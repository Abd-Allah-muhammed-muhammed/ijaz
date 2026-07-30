// import the original type declarations
import 'react-i18next';

// import all namespaces (for the default language, only)
import translation from '@/lang/ar.json';

const resources = {
  translation,
};

declare module 'i18next' {
  // and extend them!
  interface CustomTypeOptions {
    // custom resources type
    // custom resources type
    resources: {
      translation: typeof translation
    };
  }
}
