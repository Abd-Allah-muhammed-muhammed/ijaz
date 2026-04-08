import {KTIcon} from "@/_metronic/helpers";
import {useTranslation} from "react-i18next";
import { ReactNode } from "react";

export default function ActionCell({children}:{children: ReactNode}) {
  const {t} = useTranslation()
  return <>
    <a
      href='#'
      className='btn btn-light btn-active-light-primary btn-sm'
      data-kt-menu-trigger='click'
      data-kt-menu-placement='bottom-end'
    >
      {t('actions')}
      <KTIcon iconName='down' className='fs-5 m-0'/>
    </a>
    {/* begin::Menu */}
    <div
      className='menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-125px py-4'
      data-kt-menu='true'
    >
      {t('actions')}

      {children}
    </div>
    {/* end::Menu */}
  </>;
}
