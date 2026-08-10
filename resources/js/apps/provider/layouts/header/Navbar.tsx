import clsx from 'clsx'
import {KTIcon} from '@/vendor/metronic/helpers'
import {ThemeModeSwitcher} from '@/vendor/metronic/partials'
import {HeaderNotificationsMenu} from '@/apps/provider/layouts/header-menus/HeaderNotificationsMenu'
import {HeaderUserMenu} from '@/apps/provider/layouts/header-menus/HeaderUserMenu'
import {useLayout} from '@/vendor/metronic/layout/core'
import {usePage} from "@inertiajs/react";
import {Admin} from "@/shared/types/models";
import LangDropdown from "@/shared/layouts/Lang-dropdown";

const itemClass = 'ms-1 ms-md-4'
const userAvatarClass = 'symbol-35px'
const btnIconClass = 'fs-2'

const Navbar = () => {
  const {config} = useLayout()
  const currentUser = usePage().props.auth.user as unknown as Admin
  return (
    <div className='app-navbar flex-shrink-0'>
      <div className={clsx('app-navbar-item', itemClass)}>
        <LangDropdown />
      </div>

      <div className={clsx('app-navbar-item', itemClass)}>
        <HeaderNotificationsMenu/>
      </div>

      <div className={clsx('app-navbar-item', itemClass)}>
        <ThemeModeSwitcher toggleBtnClass={clsx('btn-active-light-primary btn-custom')}/>
      </div>

      <div className={clsx('app-navbar-item', itemClass)}>
        <div
          className={clsx('cursor-pointer symbol', userAvatarClass)}
          data-kt-menu-trigger="{default: 'click'}"
          data-kt-menu-attach='parent'
          data-kt-menu-placement='bottom-end'
        >
          <img src={currentUser.image} alt=''/>
        </div>

        <HeaderUserMenu/>

      </div>
      {config.app?.header?.default?.menu?.display && (
        <div className='app-navbar-item d-lg-none ms-2 me-n3' title='Show header menu'>
          <div
            className='btn btn-icon btn-active-color-primary w-35px h-35px'
            id='kt_app_header_menu_toggle'
          >
            <KTIcon iconName='text-align-left' className={btnIconClass}/>
          </div>
        </div>
      )}
    </div>
  )
}

export {Navbar}
