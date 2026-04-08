import { useEffect, ReactNode } from 'react';
import i18n from './i18next';


export default function I18nextEffect({ children, locale }: { children?: ReactNode, locale: string }): ReactNode | undefined {
  useEffect(() => {
    console.log(locale);
    
    i18n.changeLanguage(locale);
    if (['ar', 'ur'].includes(locale)) {
      document.body.setAttribute('style', 'font-family: "Cairo", sans-serif !important');
    } else {
      document.body.setAttribute('style', 'font-family: Inter, Helvetica, "sans-serif" !important');
    }
  }, [locale]);

  return children;
}

