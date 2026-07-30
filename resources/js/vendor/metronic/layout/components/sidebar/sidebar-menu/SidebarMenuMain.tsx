import AdminController from '@/actions/App/Http/Controllers/Dashboard/AdminController';
import BannerController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/BannerController';
import CarBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarBrandController';
import CarCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarCategoryController';
import CarTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/CarTypeController';
import CategoryController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/CategoryController';
import CityController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/CityController';
import HomeController from '@/actions/App/Http/Controllers/Dashboard/HomeController';
import MessageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/MessageController';
import NationalityController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/NationalityController';
import OrderController from '@/actions/Modules/Orders/Http/Controllers/Dashboard/OrderController';
import PageController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController';
import PanAnalyticsController from '@/actions/App/Http/Controllers/Dashboard/PanAnalyticsController';
import PropertyCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyCategoryController';
import PropertyTypeController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/PropertyTypeController';
import ProviderController from '@/actions/App/Http/Controllers/Dashboard/ProviderController';
import ProviderTypeController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/ProviderTypeController';
import QuestionController from '@/actions/Modules/Cms/Http/Controllers/Dashboard/QuestionController';
import RegionController from '@/actions/Modules/Geo/Http/Controllers/Dashboard/RegionController';
import RoleController from '@/actions/App/Http/Controllers/Dashboard/RoleController';
import SkillController from '@/actions/Modules/Marketplace/Http/Controllers/Dashboard/SkillController';
import SupportController from '@/actions/Modules/Support/Http/Controllers/Dashboard/SupportController';
import TopUpRequestController from '@/actions/Modules/Wallet/Http/Controllers/Dashboard/TopUpRequestController';
import UserController from '@/actions/App/Http/Controllers/Dashboard/UserController';
import WithdrawRequestController from '@/actions/Modules/Wallet/Http/Controllers/Dashboard/WithdrawRequestController';
import useActiveRoute from '@/shared/hooks/use-active-route';
import usePermissions from '@/shared/hooks/use-permissions';
import { useTranslation } from 'react-i18next';
import { SidebarMenuItem } from './SidebarMenuItem';
import DeviceCategoryController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/DeviceCategoryController';
import ElectronicBrandController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/ElectronicBrandController';
import SpecializationController from '@/actions/Modules/Catalog/Http/Controllers/Dashboard/SpecializationController';
import InstituteAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/InstituteAdvisementController';
import OpportunityController from '@/actions/Modules/Opportunity/Http/Controllers/Dashboard/OpportunityController';
import GuarantorDashboardController from '@/actions/Modules/Guarantor/Http/Controllers/Dashboard/GuarantorController';
import PropertyAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/PropertyAdvisementController';
import CarAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/CarAdvisementController';
import ElectronicAdvisementController from '@/actions/Modules/Classifieds/Http/Controllers/Dashboard/ElectronicAdvisementController';
import SettingController from '@/actions/Modules/Settings/Http/Controllers/Dashboard/SettingController';
import ReviewController from '@/actions/Modules/Reviews/Http/Controllers/Dashboard/ReviewController';

const SidebarMenuMain = () => {
  const { matchUrl, matchComponents } = useActiveRoute();
  const { hasPermission, hasAnyPermission } = usePermissions();
  const { t } = useTranslation();
  return (
    <>
      {/* 1. Dashboard (standalone) */}
      <SidebarMenuItem
        isActive={matchUrl(HomeController.url())}
        to={HomeController.url()}
        icon="element-11"
        title={t('dashboard')}
        fontIcon="bi-app-indicator"
      />

      {/* 2. Orders */}
      {hasPermission('show orders') && (
        <>
          <div className="menu-item">
            <div className="menu-content pt-8 pb-2">
              <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('orders')}</span>
            </div>
          </div>
          <SidebarMenuItem
            isActive={matchComponents('dashboard.orders.*')}
            to={OrderController.index().url}
            icon="basket"
            title={t('orders')}
            fontIcon="bi-cart"
            show={hasPermission('show orders')}
          />
        </>
      )}

      {/* 3. Administration → Roles, Admins, Users, Providers */}
      {hasAnyPermission(['show roles', 'show admins', 'show users', 'show providers']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('administration')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={RoleController.index().url}
        title={t('roles')}
        icon="lock-2"
        fontIcon="bi-lock"
        isActive={matchComponents('dashboard.roles.*')}
        show={hasPermission('show roles')}
      />
      <SidebarMenuItem
        to={AdminController.index().url}
        title={t('admins')}
        icon="profile-circle"
        fontIcon="bi-person"
        isActive={matchComponents('dashboard.admins.*')}
        show={hasPermission('show admins')}
      />
      <SidebarMenuItem
        to={UserController.index().url}
        title={t('users')}
        icon="profile-user"
        fontIcon="bi-people"
        isActive={matchComponents('dashboard.users.*')}
        show={hasPermission('show users')}
      />
      <SidebarMenuItem
        to={ProviderController.index().url}
        title={t('providers')}
        icon="briefcase"
        fontIcon="bi-briefcase"
        isActive={matchComponents('dashboard.providers.*')}
        show={hasPermission('show providers')}
      />

      {/* 4. Marketplace → Categories, Skills, Provider Types */}
      {hasAnyPermission(['show categories', 'show skills', 'show providerTypes']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('marketplace')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={CategoryController.index().url}
        title={t('categories')}
        icon="category"
        fontIcon="bi-grid"
        isActive={matchComponents('dashboard.categories.*')}
        show={hasPermission('show categories')}
      />
      <SidebarMenuItem
        to={SkillController.index().url}
        title={t('skills')}
        icon="award"
        fontIcon="bi-award"
        isActive={matchComponents('dashboard.skills.*')}
        show={hasPermission('show skills')}
      />
      <SidebarMenuItem
        to={ProviderTypeController.index().url}
        title={t('provider_types')}
        icon="tag"
        fontIcon="bi-tags"
        isActive={matchComponents('dashboard.providerTypes.*')}
        show={hasPermission('show providerTypes')}
      />

      {/* 5. Geo → Regions, Cities, Nationalities */}
      {hasAnyPermission(['show regions', 'show cities', 'show nationalities']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('geo')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={RegionController.index().url}
        title={t('regions')}
        icon="geolocation"
        fontIcon="bi-globe"
        isActive={matchComponents('dashboard.regions.*')}
        show={hasPermission('show regions')}
      />
      <SidebarMenuItem
        to={CityController.index().url}
        title={t('cities')}
        icon="map"
        fontIcon="bi-pin-map"
        isActive={matchComponents('dashboard.cities.*')}
        show={hasPermission('show cities')}
      />
      <SidebarMenuItem
        to={NationalityController.index().url}
        title={t('nationalities')}
        icon="flag"
        fontIcon="bi-flag"
        isActive={matchComponents('dashboard.nationalities.*')}
        show={hasPermission('show nationalities')}
      />

      {/* 6. Catalog — Properties → Property Categories, Property Types */}
      {hasAnyPermission(['show propertyCategories', 'show propertyTypes']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('catalog_properties')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={PropertyCategoryController.index().url}
        title={t('property_categories')}
        icon="category"
        fontIcon="bi-grid"
        isActive={matchComponents('dashboard.propertyCategories.*')}
        show={hasPermission('show propertyCategories')}
      />
      <SidebarMenuItem
        to={PropertyTypeController.index().url}
        title={t('property_types')}
        icon="home-2"
        fontIcon="bi-building"
        isActive={matchComponents('dashboard.property-types.*')}
        show={hasPermission('show propertyTypes')}
      />

      {/* 7. Catalog — Cars → Car Categories, Car Types, Car Brands */}
      {hasAnyPermission(['show carBrands', 'show carTypes', 'show carCategories']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('catalog_cars')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={CarCategoryController.index().url}
        title={t('car_categories')}
        icon="category"
        fontIcon="bi-grid"
        isActive={matchComponents('dashboard.car-categories.*')}
        show={hasPermission('show carCategories')}
      />
      <SidebarMenuItem
        to={CarTypeController.index().url}
        title={t('car_types')}
        icon="car"
        fontIcon="bi-car-front"
        isActive={matchComponents('dashboard.car-types.*')}
        show={hasPermission('show carTypes')}
      />
      <SidebarMenuItem
        to={CarBrandController.index().url}
        title={t('car_brands')}
        icon="star"
        fontIcon="bi-star"
        isActive={matchComponents('dashboard.car-brands.*')}
        show={hasPermission('show carBrands')}
      />

      {/* 8. Catalog — Devices → Device Categories, Electronic Brands */}
      {hasAnyPermission(['show deviceCategories', 'show electronicBrands']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('catalog_devices')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={DeviceCategoryController.index().url}
        title={t('device_categories')}
        icon="devices"
        fontIcon="bi-laptop"
        isActive={matchComponents('dashboard.device-categories.*')}
        show={hasPermission('show deviceCategories')}
      />
      <SidebarMenuItem
        to={ElectronicBrandController.index().url}
        title={t('electronic_brands')}
        icon="star"
        fontIcon="bi-star"
        isActive={matchComponents('dashboard.electronic-brands.*')}
        show={hasPermission('show electronicBrands')}
      />

      {/* 9. Catalog — Institutes → Specializations */}
      {hasPermission('show specializations') && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('catalog_institutes')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={SpecializationController.index().url}
        title={t('specializations')}
        icon="book"
        fontIcon="bi-book"
        isActive={matchComponents('dashboard.specializations.*')}
        show={hasPermission('show specializations')}
      />

      {/* 10. Classifieds (Listings) */}
      {hasAnyPermission([
        'show propertyAdvisements',
        'show carAdvisements',
        'show electronicAdvisements',
        'show instituteAdvisements',
      ]) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('classifieds')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={PropertyAdvisementController.index().url}
        title={t('property_advisements')}
        icon="notepad-bookmark"
        fontIcon="bi-file-earmark-text"
        isActive={matchComponents('dashboard.property-advisements.*')}
        show={hasPermission('show propertyAdvisements')}
      />
      <SidebarMenuItem
        to={CarAdvisementController.index().url}
        title={t('car_advisements')}
        icon="notepad-bookmark"
        fontIcon="bi-file-earmark-text"
        isActive={matchComponents('dashboard.car-advisements.*')}
        show={hasPermission('show carAdvisements')}
      />
      <SidebarMenuItem
        to={ElectronicAdvisementController.index().url}
        title={t('electronic_advisements')}
        icon="devices"
        fontIcon="bi-laptop"
        isActive={matchComponents('dashboard.electronic-advisements.*')}
        show={hasPermission('show electronicAdvisements')}
      />
      <SidebarMenuItem
        to={InstituteAdvisementController.index().url}
        title={t('institute_advisements')}
        icon="building"
        fontIcon="bi-building"
        isActive={matchComponents('dashboard.institute-advisements.*')}
        show={hasPermission('show instituteAdvisements')}
      />

      {/* 11. Opportunities & Guarantor */}
      {hasAnyPermission(['show opportunities', 'show guarantors']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('opportunities_guarantor')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={OpportunityController.index().url}
        title={t('opportunities')}
        icon="briefcase"
        fontIcon="bi-briefcase"
        isActive={matchComponents('dashboard.opportunities.*')}
        show={hasPermission('show opportunities')}
      />
      <SidebarMenuItem
        to={GuarantorDashboardController.index().url}
        title={t('guarantor.module_title')}
        icon="shield-tick"
        fontIcon="bi-shield-check"
        isActive={matchComponents('dashboard.guarantor.*')}
        show={hasPermission('show guarantors')}
      />

      {/* 12. Finance */}
      {hasAnyPermission(['show topUpRequests', 'show withdrawRequests']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('finance')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={TopUpRequestController.index().url}
        title={t('top_up_requests')}
        icon="wallet"
        fontIcon="bi-wallet2"
        isActive={matchComponents('dashboard.top-up-requests.*')}
        show={hasPermission('show topUpRequests')}
      />
      <SidebarMenuItem
        to={WithdrawRequestController.index().url}
        title={t('withdraw_requests')}
        icon="dollar"
        fontIcon="bi-cash-stack"
        isActive={matchComponents('dashboard.withdraw-requests.*')}
        show={hasPermission('show withdrawRequests')}
      />

      {/* 13. Content / CMS → Banners, Pages, Questions, Messages */}
      <div className="menu-item">
        <div className="menu-content pt-8 pb-2">
          <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('content_cms')}</span>
        </div>
      </div>
      <SidebarMenuItem
        to={BannerController.index().url}
        title={t('banners')}
        icon="picture"
        fontIcon="bi-image"
        isActive={matchComponents('dashboard.banners.*')}
        show={hasPermission('show banners')}
      />
      <SidebarMenuItem
        to={PageController.index().url}
        title={t('pages')}
        icon="document"
        fontIcon="bi-file-earmark-text"
        isActive={matchComponents('dashboard.pages.*')}
      />
      <SidebarMenuItem
        to={QuestionController.index().url}
        title={t('questions')}
        icon="question-2"
        fontIcon="bi-question-circle"
        isActive={matchComponents('dashboard.questions.*')}
      />
      <SidebarMenuItem
        to={MessageController.index().url}
        title={t('messages')}
        icon="sms"
        fontIcon="bi-chat-dots"
        isActive={matchComponents('dashboard.messages.*')}
      />

      {/* 14. Support → Tickets */}
      <div className="menu-item">
        <div className="menu-content pt-8 pb-2">
          <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('support')}</span>
        </div>
      </div>
      <SidebarMenuItem
        to={SupportController.index().url}
        title={t('tickets')}
        icon="message-question"
        fontIcon="bi-headset"
        isActive={matchComponents('dashboard.tickets.*')}
      />

      {/* 15. Quality & System → Reviews, Settings, Pan Analytics */}
      {hasAnyPermission(['show reviews', 'show settings', 'show panAnalytics']) && (
        <div className="menu-item">
          <div className="menu-content pt-8 pb-2">
            <span className="menu-section text-muted text-uppercase fs-8 ls-1">{t('quality_system')}</span>
          </div>
        </div>
      )}
      <SidebarMenuItem
        to={ReviewController.index().url}
        title={t('reviews')}
        icon="star"
        fontIcon="bi-star"
        isActive={matchComponents('dashboard.reviews.*')}
        show={hasPermission('show reviews')}
      />
      <SidebarMenuItem
        to={SettingController.index().url}
        title={t('settings')}
        icon="setting-2"
        fontIcon="bi-gear"
        isActive={matchComponents('dashboard.settings.*')}
        show={hasPermission('show settings')}
      />
      <SidebarMenuItem
        to={PanAnalyticsController.index().url}
        title={t('pan_analytics')}
        icon="chart-simple"
        fontIcon="bi-bar-chart"
        isActive={matchComponents('dashboard.pan-analytics.*')}
        show={hasPermission('show panAnalytics')}
      />
    </>
  );
};

export { SidebarMenuMain };
