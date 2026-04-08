import clsx from 'clsx'
import {FC} from 'react'
import {url} from "@/helpers/general";
import {getSupportedLocales} from "@/hooks/use-locales";
import {usePage} from "@inertiajs/react";
import {useTranslation} from "react-i18next";

const lang = getSupportedLocales()
const replaceLocale = (currentLocale: string, locale: string, url: string) => {
  if (url === `/${currentLocale}`) {
    return `/${locale}`
  }
  return url.replace(`/${currentLocale}/`, `/${locale}/`)
};

const Languages: FC = () => {
  const pageData = usePage();
  const props = pageData.props;
  const currentLocale = props.app.locale
  const route = pageData.url
  const currentLanguage = lang[currentLocale]
  const {t} = useTranslation()
  return (
    <>
      <a href='#' className='menu-link px-5'
      >
        <span className='menu-title position-relative'>
          {t('language')}
          <span className='fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0'>
            {currentLanguage?.name}{' '}
            <img
              className='w-15px h-15px rounded-1 ms-2'
              src={url(currentLanguage.flag)}
              alt='metronic'
            />
          </span>
        </span>
      </a>

      <div className='menu-sub menu-sub-dropdown w-175px py-4'>
        {Object.entries(lang).map(([locale, l]) => (
          <div
            className='menu-item px-3'
            key={locale}
            onClick={() => {
              window.location.href = url(replaceLocale(currentLocale, locale, route))
            }}
          >
            <a
              href='#'
              className={clsx('menu-link d-flex px-5', {active: locale === currentLocale})}
            >
              <span className='symbol symbol-20px me-4'>
                <img className='rounded-1' src={url(l.flag)} alt={l.native}/>
              </span>
              {l.native}
            </a>
          </div>
        ))}
      </div>
    </>

  )
}

export {Languages}
