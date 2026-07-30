import { ReactNode, useEffect } from 'react';
import i18n from './i18next';

export default function I18nextEffect({ children, locale }: { children?: ReactNode, locale: string }): ReactNode | undefined {
  useEffect(() => {
    i18n.changeLanguage(locale);
  }, [locale]);

  return children;
}
