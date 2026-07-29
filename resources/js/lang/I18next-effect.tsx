import { ReactNode, useEffect } from 'react';
import i18n from './i18next';

export default function I18nextEffect({ children, locale }: { children?: ReactNode, locale: string }): ReactNode | undefined {
  useEffect(() => {
    i18n.changeLanguage(locale);
    // Font stack is owned by app.css (--font-sans: IBM Plex Sans Arabic + Inter).
    // Do not override body font-family per locale — Arabic glyphs come from IBM Plex.
  }, [locale]);

  return children;
}
