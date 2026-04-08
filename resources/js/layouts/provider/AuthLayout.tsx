import React, {ReactNode, useEffect} from 'react'
import {url} from "@/helpers/general";
import LangDropdown from "@/layouts/Lang-dropdown";

type Props = {
  children?: ReactNode
}

const AuthLayout = ({children}: Props) => {

  useEffect(() => {
    const className = document.body.className;
    document.body.className = 'app-blank';
    const root = document.getElementById('root')
    if (root) {
      root.style.height = '100vh'
    }

    return () => {
      document.body.className = className;
      if (root) {
        root.style.height = 'auto'
      }
    }
  }, []);

  return (
    <div className="d-flex flex-column flex-root h-100" id="kt_app_root">
      {/*begin::Authentication - Sign-in */}
      <div className="d-flex flex-column flex-lg-row flex-column-fluid">
        {/*begin::Logo*/}
        <a href="" className="d-block d-lg-none mx-auto py-20">
          <img alt="Logo" src={url("media/logos/default.svg")} className="theme-light-show h-25px"/>
          <img alt="Logo" src={url("media/logos/default-dark.svg")} className="theme-dark-show h-25px"/>
        </a>
        {/*end::Logo*/}
        {/*begin::Aside*/}
        <div className="d-flex flex-column flex-column-fluid flex-center w-lg-50 p-10">
          {/*begin::Wrapper*/}
          <div className="d-flex justify-content-between flex-column-fluid flex-column w-100 mw-450px">
            {children}
            {/*begin::Footer*/}
            <div className="m-0">
              <LangDropdown/>
            </div>
            {/*end::Footer*/}
          </div>
          {/*end::Wrapper*/}
        </div>
        {/*end::Aside*/}
        {/*begin::Body*/}
        <div
          className="d-none d-lg-flex flex-lg-row-fluid w-50 bgi-size-cover bgi-position-y-center bgi-position-x-start bgi-no-repeat"
          style={{backgroundImage: `url(${url('media/auth/login.png')})`}}></div>
        {/*begin::Body*/}
      </div>
      {/*end::Authentication - Sign-in*/}
    </div>
  );
};
export {AuthLayout}
