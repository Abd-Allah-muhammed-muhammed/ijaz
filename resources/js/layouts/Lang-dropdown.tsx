import {getSupportedLocales} from "@/hooks/use-locales";
import {usePage} from "@inertiajs/react";
import {Dropdown} from "react-bootstrap";
import {replaceLocale, url} from "@/helpers/general";
import AuthController from '@/actions/App/Http/Controllers/Provider/AuthController';

const languages = getSupportedLocales();

const LangDropdown = () => {
  const pageData = usePage();
  const props = pageData.props;
  const currentLocale = props.app.locale
  const route = pageData.url
  const currentLanguage = languages[currentLocale]
  return (
    <Dropdown>
      <Dropdown.Toggle className='btn btn-flex btn-link rotate' variant={''}>
        <img data-kt-element="current-lang-flag" className="w-25px h-25px rounded-circle me-3"
             src={url(currentLanguage.flag)} alt=""/>
        <span data-kt-element="current-lang-name" className="me-2">{currentLanguage.native}</span>
        <i className="ki-duotone ki-down fs-2 text-muted rotate-180 m-0"></i>
      </Dropdown.Toggle>

      <Dropdown.Menu
        className="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4">
        {Object.entries(languages).map(([locale, options], index) => (
          <div className='px-3' key={'lang-switcher-wrapper-' + locale}>
            <Dropdown.Item
              key={`lang-switcher-${locale}-${index}`}
              className="d-flex align-items-center px-3"
              onClick={() => {
                // window.location.href = AuthController.switchLang(locale).url;
                window.location.href = url(replaceLocale(currentLocale, locale, route))
              }}
            >
                      <span className="symbol symbol-20px me-4">
                      <img data-kt-element="lang-flag" className="rounded-1" src={url(options.flag)} alt=""/>
                      </span>
              <span data-kt-element="lang-name"> {options.native}</span>
            </Dropdown.Item>
          </div>
        ))}
      </Dropdown.Menu>
    </Dropdown>
  )
}

export default LangDropdown
